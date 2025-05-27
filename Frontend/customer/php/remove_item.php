<?php
require"../../../Backend/connect.php";

session_start();

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $conn = getDBConnection();
    if (!$conn) {
        echo "db_error";
        exit;
    }

    $product_id = $_POST['product_id'];

    // Find the cart ID for this user
    $get_cart_sql = "SELECT cart_id FROM cart WHERE user_id = :user_id";
    $get_cart_stmt = oci_parse($conn, $get_cart_sql);
    oci_bind_by_name($get_cart_stmt, ":user_id", $user_id);
    oci_execute($get_cart_stmt);

    $cart_id = null;
    if ($row = oci_fetch_assoc($get_cart_stmt)) {
        $cart_id = $row['CART_ID'];
    } else {
        echo "cart_not_found";
        exit;
    }

    // Remove item from product_cart
    $delete_item_sql = "DELETE FROM product_cart WHERE cart_id = :cart_id AND product_id = :product_id";
    $delete_item_stmt = oci_parse($conn, $delete_item_sql);
    oci_bind_by_name($delete_item_stmt, ":cart_id", $cart_id);
    oci_bind_by_name($delete_item_stmt, ":product_id", $product_id);
    oci_execute($delete_item_stmt);

    // Check if any items remain in product_cart for this cart
    $check_sql = "SELECT COUNT(*) AS ITEM_COUNT FROM product_cart WHERE cart_id = :cart_id";
    $check_stmt = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stmt, ":cart_id", $cart_id);
    oci_execute($check_stmt);

    $row = oci_fetch_assoc($check_stmt);
    if ($row && $row['ITEM_COUNT'] == 0) {
        // If no more items, delete the cart
        $delete_cart_sql = "DELETE FROM cart WHERE cart_id = :cart_id";
        $delete_cart_stmt = oci_parse($conn, $delete_cart_sql);
        oci_bind_by_name($delete_cart_stmt, ":cart_id", $cart_id);
        oci_execute($delete_cart_stmt);
    }

    echo "success";
    exit;
}

echo "invalid_request";
exit;
?>
