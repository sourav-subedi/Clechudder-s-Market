<?php
session_start();
require "../../../Backend/connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Check if required POST data is present
if (!isset($_POST['cart_id']) || !isset($_POST['collection_slot_id']) || !isset($_POST['payment_method']) || !isset($_POST['amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit();
}

// Get and sanitize input data
$user_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'];
$collection_slot_id = $_POST['collection_slot_id'];
$payment_method = $_POST['payment_method'];
$amount = $_POST['amount'];
$coupon_id = isset($_POST['coupon_id']) ? $_POST['coupon_id'] : null;

try {
    // Start transaction
    oci_execute($conn, "BEGIN");

    // Verify collection slot exists
    $slot_check_sql = "SELECT slot_date, slot_time FROM collection_slot WHERE collection_slot_id = :slot_id";
    $slot_check_stmt = oci_parse($conn, $slot_check_sql);
    oci_bind_by_name($slot_check_stmt, ":slot_id", $collection_slot_id);
    
    if (!oci_execute($slot_check_stmt)) {
        throw new Exception("Error verifying collection slot");
    }
    
    $slot_data = oci_fetch_assoc($slot_check_stmt);
    if (!$slot_data) {
        throw new Exception("Collection slot not found");
    }
    oci_free_statement($slot_check_stmt);

    // Calculate total amount (including any discounts)
    $total_amount = $amount;
    if ($coupon_id) {
        // Get coupon discount percentage
        $coupon_sql = "SELECT coupon_discount_percent FROM coupon WHERE coupon_id = :coupon_id";
        $coupon_stmt = oci_parse($conn, $coupon_sql);
        oci_bind_by_name($coupon_stmt, ":coupon_id", $coupon_id);
        
        if (oci_execute($coupon_stmt)) {
            if ($coupon_row = oci_fetch_assoc($coupon_stmt)) {
                $discount_percent = $coupon_row['COUPON_DISCOUNT_PERCENT'];
                $total_amount = $amount * (1 - ($discount_percent / 100));
            }
        }
        oci_free_statement($coupon_stmt);
    }

    // Insert into orders table
    $order_sql = "INSERT INTO orders (
                    order_date,
                    order_amount,
                    total_amount,
                    coupon_id,
                    status,
                    collection_slot_id,
                    user_id,
                    cart_id
                ) VALUES (
                    CURRENT_TIMESTAMP,
                    :order_amount,
                    :total_amount,
                    :coupon_id,
                    'pending',
                    :collection_slot_id,
                    :user_id,
                    :cart_id
                ) RETURNING order_id INTO :order_id";

    $order_stmt = oci_parse($conn, $order_sql);
    if (!$order_stmt) {
        $e = oci_error($conn);
        throw new Exception("Error parsing order SQL: " . $e['message']);
    }

    oci_bind_by_name($order_stmt, ":order_amount", $amount);
    oci_bind_by_name($order_stmt, ":total_amount", $total_amount);
    oci_bind_by_name($order_stmt, ":coupon_id", $coupon_id);
    oci_bind_by_name($order_stmt, ":collection_slot_id", $collection_slot_id);
    oci_bind_by_name($order_stmt, ":user_id", $user_id);
    oci_bind_by_name($order_stmt, ":cart_id", $cart_id);
    oci_bind_by_name($order_stmt, ":order_id", $order_id, 32);

    if (!oci_execute($order_stmt)) {
        $e = oci_error($order_stmt);
        throw new Exception("Error creating order: " . $e['message']);
    }

    // Insert into payment table
    $payment_sql = "INSERT INTO payment (
                        payment_date,
                        amount,
                        payment_method,
                        payment_status,
                        order_id,
                        user_id
                    ) VALUES (
                        CURRENT_TIMESTAMP,
                        :amount,
                        :payment_method,
                        'completed',
                        :order_id,
                        :user_id
                    )";

    $payment_stmt = oci_parse($conn, $payment_sql);
    if (!$payment_stmt) {
        $e = oci_error($conn);
        throw new Exception("Error parsing payment SQL: " . $e['message']);
    }

    oci_bind_by_name($payment_stmt, ":amount", $total_amount);
    oci_bind_by_name($payment_stmt, ":payment_method", $payment_method);
    oci_bind_by_name($payment_stmt, ":order_id", $order_id);
    oci_bind_by_name($payment_stmt, ":user_id", $user_id);

    if (!oci_execute($payment_stmt)) {
        $e = oci_error($payment_stmt);
        throw new Exception("Error creating payment record: " . $e['message']);
    }

    // Update order status to 'paid'
    $update_order_sql = "UPDATE orders SET status = 'paid' WHERE order_id = :order_id";
    $update_order_stmt = oci_parse($conn, $update_order_sql);
    if (!$update_order_stmt) {
        $e = oci_error($conn);
        throw new Exception("Error parsing update order SQL: " . $e['message']);
    }

    oci_bind_by_name($update_order_stmt, ":order_id", $order_id);

    if (!oci_execute($update_order_stmt)) {
        $e = oci_error($update_order_stmt);
        throw new Exception("Error updating order status: " . $e['message']);
    }

    // Clear the cart
    $clear_cart_sql = "DELETE FROM product_cart WHERE cart_id = :cart_id";
    $clear_cart_stmt = oci_parse($conn, $clear_cart_sql);
    if (!$clear_cart_stmt) {
        $e = oci_error($conn);
        throw new Exception("Error parsing clear cart SQL: " . $e['message']);
    }

    oci_bind_by_name($clear_cart_stmt, ":cart_id", $cart_id);

    if (!oci_execute($clear_cart_stmt)) {
        $e = oci_error($clear_cart_stmt);
        throw new Exception("Error clearing cart: " . $e['message']);
    }

    // Commit transaction
    oci_execute($conn, "COMMIT");

    // Clean up
    oci_free_statement($order_stmt);
    oci_free_statement($payment_stmt);
    oci_free_statement($update_order_stmt);
    oci_free_statement($clear_cart_stmt);
    oci_close($conn);

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Order and payment processed successfully',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    oci_execute($conn, "ROLLBACK");
    
    // Clean up
    if (isset($order_stmt)) oci_free_statement($order_stmt);
    if (isset($payment_stmt)) oci_free_statement($payment_stmt);
    if (isset($update_order_stmt)) oci_free_statement($update_order_stmt);
    if (isset($clear_cart_stmt)) oci_free_statement($clear_cart_stmt);
    oci_close($conn);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 