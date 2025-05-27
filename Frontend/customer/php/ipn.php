<?php
// PayPal IPN Handler
require "../../../Backend/connect.php";

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/paypal_ipn.log');

// Function to log messages
function log_message($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, dirname(__FILE__) . '/paypal_ipn.log');
}

log_message("IPN received");

// Read POST data
$raw_post_data = file_get_contents('php://input');
log_message("Raw POST data: " . $raw_post_data);

if (empty($raw_post_data)) {
    log_message("Error: Empty POST data");
    exit;
}

$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2) {
        $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
}

// Read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';
foreach ($myPost as $key => $value) {
    $value = urlencode($value);
    $req .= "&$key=$value";
}

log_message("Sending verification request to PayPal");

// Post back to PayPal system for validation
$ch = curl_init('https://ipnpb.sandbox.paypal.com/cgi-bin/webscr');
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSLVERSION, 6);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

$res = curl_exec($ch);

if (!$res) {
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    log_message("Curl error: [$errno] $errstr");
    curl_close($ch);
    exit;
}

$info = curl_getinfo($ch);
curl_close($ch);

log_message("PayPal response: " . $res);

// Check if PayPal verified the transaction
if ($res == "VERIFIED") {
    log_message("Payment verified by PayPal");
    
    // Payment was verified
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
    $txn_id = isset($_POST['txn_id']) ? $_POST['txn_id'] : '';
    $receiver_email = isset($_POST['receiver_email']) ? $_POST['receiver_email'] : '';
    $payment_amount = isset($_POST['mc_gross']) ? $_POST['mc_gross'] : '';
    $payment_currency = isset($_POST['mc_currency']) ? $_POST['mc_currency'] : '';
    $payer_email = isset($_POST['payer_email']) ? $_POST['payer_email'] : '';
    $custom = isset($_POST['custom']) ? $_POST['custom'] : '';

    log_message("Custom data received: " . $custom);

    // Parse custom data (user_id|cart_id)
    list($user_id, $cart_id) = explode('|', $custom);

    log_message("Payment details - Status: $payment_status, Txn ID: $txn_id, Amount: $payment_amount");
    log_message("User ID: $user_id, Cart ID: $cart_id");

    if ($payment_status == "Completed") {
        try {
            // Begin transaction
            oci_execute($conn, "BEGIN");
            log_message("Transaction begun");

            // 1. Create order record
            $order_sql = "INSERT INTO orders (
                order_date,
                order_amount,
                total_amount,
                status,
                user_id,
                cart_id
            ) VALUES (
                SYSDATE,
                :order_amount,
                :total_amount,
                'completed',
                :user_id,
                :cart_id
            ) RETURNING order_id INTO :order_id";

            log_message("Order SQL: " . $order_sql);

            $order_stmt = oci_parse($conn, $order_sql);
            if (!$order_stmt) {
                $e = oci_error($conn);
                log_message("Error parsing order SQL: " . $e['message']);
                throw new Exception("Error parsing order SQL: " . $e['message']);
            }
            
            // Bind parameters
            oci_bind_by_name($order_stmt, ":order_amount", $payment_amount);
            oci_bind_by_name($order_stmt, ":total_amount", $payment_amount);
            oci_bind_by_name($order_stmt, ":user_id", $user_id);
            oci_bind_by_name($order_stmt, ":cart_id", $cart_id);
            oci_bind_by_name($order_stmt, ":order_id", $order_id, 32);

            // Execute order creation
            if (!oci_execute($order_stmt)) {
                $e = oci_error($order_stmt);
                log_message("Error executing order SQL: " . $e['message']);
                throw new Exception("Error executing order SQL: " . $e['message']);
            }

            log_message("Order created successfully. Order ID: " . $order_id);

            // 2. Create payment record
            $payment_sql = "INSERT INTO payment (
                payment_date,
                amount,
                payment_method,
                payment_status,
                order_id,
                user_id
            ) VALUES (
                SYSDATE,
                :amount,
                'PayPal',
                :payment_status,
                :order_id,
                :user_id
            )";

            log_message("Payment SQL: " . $payment_sql);

            $payment_stmt = oci_parse($conn, $payment_sql);
            if (!$payment_stmt) {
                $e = oci_error($conn);
                log_message("Error parsing payment SQL: " . $e['message']);
                throw new Exception("Error parsing payment SQL: " . $e['message']);
            }

            oci_bind_by_name($payment_stmt, ":amount", $payment_amount);
            oci_bind_by_name($payment_stmt, ":payment_status", $payment_status);
            oci_bind_by_name($payment_stmt, ":order_id", $order_id);
            oci_bind_by_name($payment_stmt, ":user_id", $user_id);

            if (!oci_execute($payment_stmt)) {
                $e = oci_error($payment_stmt);
                log_message("Error executing payment SQL: " . $e['message']);
                throw new Exception("Error executing payment SQL: " . $e['message']);
            }

            log_message("Payment record created successfully");

            // 3. Clear the cart
            $clear_cart_sql = "DELETE FROM product_cart WHERE cart_id = :cart_id";
            $clear_cart_stmt = oci_parse($conn, $clear_cart_sql);
            if (!$clear_cart_stmt) {
                $e = oci_error($conn);
                log_message("Error parsing clear cart SQL: " . $e['message']);
                throw new Exception("Error parsing clear cart SQL: " . $e['message']);
            }

            oci_bind_by_name($clear_cart_stmt, ":cart_id", $cart_id);
            if (!oci_execute($clear_cart_stmt)) {
                $e = oci_error($clear_cart_stmt);
                log_message("Error executing clear cart SQL: " . $e['message']);
                throw new Exception("Error executing clear cart SQL: " . $e['message']);
            }

            log_message("Cart cleared successfully");

            // Commit transaction
            oci_execute($conn, "COMMIT");
            log_message("Transaction committed successfully");
            
        } catch (Exception $e) {
            // Rollback transaction on error
            oci_execute($conn, "ROLLBACK");
            log_message("Error processing order: " . $e->getMessage());
            log_message("Stack trace: " . $e->getTraceAsString());
        }
    } else {
        log_message("Payment status not completed: $payment_status");
    }
} else {
    log_message("Payment verification failed");
}

// Always return a 200 OK to PayPal
http_response_code(200);
?> 