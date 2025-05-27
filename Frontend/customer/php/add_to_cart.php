<?php
require "../../../Backend/connect.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first!']);
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

if (isset($_POST['add_to_cart'])) {
    $product_id = (int) $_POST['product_id'];
    $qty = (int) $_POST['qty'];

    // Get user's cart or create a new one
    $sql = "SELECT * FROM cart WHERE user_id = :user_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":user_id", $user_id);
    oci_execute($stmt);
    $cart = oci_fetch_array($stmt, OCI_ASSOC);
    oci_free_statement($stmt);

    if (!$cart) {
        $insert_cart_sql = "INSERT INTO cart (cart_id, user_id, add_date) VALUES (cart_seq.NEXTVAL, :user_id, SYSDATE) RETURNING cart_id INTO :cart_id";
        $stmt = oci_parse($conn, $insert_cart_sql);
        oci_bind_by_name($stmt, ":user_id", $user_id);
        oci_bind_by_name($stmt, ":cart_id", $cart_id, 20);
        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        
        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create cart']);
            exit;
        }
    } else {
        $cart_id = $cart['CART_ID'];
    }

    // Calculate current total quantity in cart
    $total_qty_sql = "SELECT SUM(quantity) AS total_qty FROM product_cart WHERE cart_id = :cart_id";
    $stmt = oci_parse($conn, $total_qty_sql);
    oci_bind_by_name($stmt, ":cart_id", $cart_id);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    $current_total_qty = $row['TOTAL_QTY'] ?? 0;
    oci_free_statement($stmt);

    // Check if adding this quantity exceeds the limit
    if ($current_total_qty + $qty > 10) {
        echo json_encode(['status' => 'limit_exceeded', 'message' => 'Cart limit exceeded. Cannot add more than 10 items.']);
        exit;
    }

    // Check if product already exists in cart
    $check_product_sql = "SELECT * FROM product_cart WHERE cart_id = :cart_id AND product_id = :product_id";
    $stmt = oci_parse($conn, $check_product_sql);
    oci_bind_by_name($stmt, ":cart_id", $cart_id);
    oci_bind_by_name($stmt, ":product_id", $product_id);
    oci_execute($stmt);
    $product_exists = oci_fetch_array($stmt, OCI_ASSOC);
    oci_free_statement($stmt);

    if ($product_exists) {
        $response = ['status' => 'error', 'message' => 'Product already in cart!'];
    } else {
        $insert_product_sql = "INSERT INTO product_cart (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :qty)";
        $stmt = oci_parse($conn, $insert_product_sql);
        oci_bind_by_name($stmt, ":cart_id", $cart_id);
        oci_bind_by_name($stmt, ":product_id", $product_id);
        oci_bind_by_name($stmt, ":qty", $qty);
        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        
        if ($result) {
            $response = ['status' => 'success', 'message' => 'Product added to cart successfully!'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to add product to cart'];
        }
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request'];
}

echo json_encode($response);
exit;
