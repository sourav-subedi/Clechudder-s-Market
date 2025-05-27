<?php
session_start();
require "../../../Backend/connect.php";

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

if (!isset($_SESSION['user_id'])) {
    echo "User not logged in";
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart_id for the user
$cart_id = null;
$cart_query = oci_parse($conn, "SELECT cart_id FROM cart WHERE user_id = :user_id");
oci_bind_by_name($cart_query, ":user_id", $user_id);
oci_execute($cart_query);
if ($row = oci_fetch_assoc($cart_query)) {
    $cart_id = $row['CART_ID'];
}
oci_free_statement($cart_query);

if ($cart_id) {
    // Begin transaction for data consistency
    oci_execute(oci_parse($conn, "BEGIN NULL; END;"));
    
    try {
        // Delete from all child tables that reference the cart
        // Delete from orders table first (if it exists and references cart)
        $delete_orders = oci_parse($conn, "DELETE FROM orders WHERE cart_id = :cart_id");
        oci_bind_by_name($delete_orders, ":cart_id", $cart_id);
        oci_execute($delete_orders);
        oci_free_statement($delete_orders);
        
        // Delete from product_cart
        $delete_products = oci_parse($conn, "DELETE FROM product_cart WHERE cart_id = :cart_id");
        oci_bind_by_name($delete_products, ":cart_id", $cart_id);
        oci_execute($delete_products);
        oci_free_statement($delete_products);

        // Finally delete the cart
        $delete_cart = oci_parse($conn, "DELETE FROM cart WHERE cart_id = :cart_id");
        oci_bind_by_name($delete_cart, ":cart_id", $cart_id);
        oci_execute($delete_cart);
        oci_free_statement($delete_cart);

        // Commit the transaction
        oci_commit($conn);
        echo "success";
        
    } catch (Exception $e) {
        // Rollback on error
        oci_rollback($conn);
        echo "Failed to clear cart: " . $e->getMessage();
    }
} else {
    echo "Cart not found";
}

oci_close($conn);
?>