<?php
// Set content type to JSON to ensure proper response
header('Content-Type: application/json');

// Start output buffering to catch any unwanted output
ob_start();

try {
    session_start();
    
    // Check if the include path is correct
    $connect_path = "../../../Backend/connect.php";
    if (!file_exists($connect_path)) {
        throw new Exception("Database connection file not found at: " . $connect_path);
    }
    
    require $connect_path;

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Check if promo code was submitted
    if (!isset($_POST['promo_code']) || empty($_POST['promo_code'])) {
        throw new Exception('No promo code provided');
    }

    $promo_code = trim($_POST['promo_code']);
    $user_id = $_SESSION['user_id'];

    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if the coupon exists and is valid (using your actual column names)
    $sql = "SELECT coupon_id, coupon_code, coupon_discount_percent, start_date, end_date, description
            FROM coupon 
            WHERE UPPER(coupon_code) = UPPER(:promo_code) 
            AND start_date <= SYSDATE 
            AND end_date >= SYSDATE";

    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($conn);
        throw new Exception('Parse error: ' . $e['message']);
    }

    oci_bind_by_name($stid, ":promo_code", $promo_code);

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        throw new Exception('Query execution error: ' . $e['message']);
    }

    $coupon = oci_fetch_assoc($stid);
    oci_free_statement($stid);

    if (!$coupon) {
        throw new Exception('Invalid promo code or expired coupon');
    }

    // Check if user has already used this coupon (only if user_coupon table exists)
    $sql_check_table = "SELECT COUNT(*) as count FROM user_tables WHERE table_name = 'USER_COUPON'";
    $stid_check = oci_parse($conn, $sql_check_table);
    $user_coupon_exists = false;
    
    if (oci_execute($stid_check)) {
        $table_result = oci_fetch_assoc($stid_check);
        $user_coupon_exists = ($table_result['COUNT'] > 0);
    }
    oci_free_statement($stid_check);

    if ($user_coupon_exists) {
        $sql = "SELECT COUNT(*) AS used_count 
                FROM user_coupon 
                WHERE user_id = :user_id 
                AND coupon_id = :coupon_id";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            throw new Exception('Parse error for usage check: ' . $e['message']);
        }

        oci_bind_by_name($stid, ":user_id", $user_id);
        oci_bind_by_name($stid, ":coupon_id", $coupon['COUPON_ID']);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            throw new Exception('Usage check query error: ' . $e['message']);
        }

        $result = oci_fetch_assoc($stid);
        oci_free_statement($stid);

        if ($result && $result['USED_COUNT'] > 0) {
            throw new Exception('You have already used this coupon');
        }
    }

    // Get subtotal from cart to calculate discount
    $sql = "SELECT SUM(p.price * pc.quantity) as subtotal
            FROM cart c
            JOIN product_cart pc ON c.cart_id = pc.cart_id
            JOIN product p ON pc.product_id = p.product_id
            WHERE c.user_id = :user_id";

    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($conn);
        throw new Exception('Parse error for cart query: ' . $e['message']);
    }

    oci_bind_by_name($stid, ":user_id", $user_id);

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        throw new Exception('Cart query error: ' . $e['message']);
    }

    $cart = oci_fetch_assoc($stid);
    oci_free_statement($stid);

    if (!$cart || !$cart['SUBTOTAL'] || $cart['SUBTOTAL'] <= 0) {
        throw new Exception('Cart is empty or contains invalid items');
    }

    // Calculate discount (using your actual column name)
    $subtotal = floatval($cart['SUBTOTAL']);
    $discount_percent = floatval($coupon['COUPON_DISCOUNT_PERCENT']);
    $discount_amount = $subtotal * ($discount_percent / 100);
    $total = $subtotal - $discount_amount;

    // Store the coupon in session for checkout
    $_SESSION['applied_coupon'] = [
        'coupon_id' => $coupon['COUPON_ID'],
        'code' => $coupon['COUPON_CODE'],
        'discount_percent' => $discount_percent,
        'discount_amount' => $discount_amount,
        'description' => $coupon['DESCRIPTION']
    ];

    // Clear any output buffer before sending JSON
    ob_clean();

    // Return success with discount info
    echo json_encode([
        'status' => 'success',
        'message' => "Coupon applied successfully! {$discount_percent}% discount - {$coupon['DESCRIPTION']}",
        'discount_percent' => $discount_percent,
        'discount_amount' => round($discount_amount, 2),
        'subtotal' => round($subtotal, 2),
        'total' => round($total, 2),
        'description' => $coupon['DESCRIPTION']
    ]);

} catch (Exception $e) {
    // Clear any output buffer before sending error JSON
    ob_clean();
    
    // Return error message
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

exit();
?>