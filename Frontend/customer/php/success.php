<?php
session_start();
require "../../../Backend/connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../loginRegister/php/login.php");
    exit();
}

// Get all necessary parameters
$cart_id = $_GET['cart_id'] ?? null;
$payment_method = $_GET['payment_method'] ?? null;
$amount = $_GET['amount'] ?? null;
$payer_id = $_GET['PayerID'] ?? null;
$collection_slot_id = $_GET['collection_slot_id'] ?? null;

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

try {
    // Get cart items
    $cart_sql = "SELECT pc.*, p.product_name, p.price, p.product_image 
                 FROM product_cart pc 
                 JOIN product p ON pc.product_id = p.product_id 
                 WHERE pc.cart_id = :cart_id";
    $cart_stmt = oci_parse($conn, $cart_sql);
    oci_bind_by_name($cart_stmt, ":cart_id", $cart_id);
    oci_execute($cart_stmt);
    
    $cart_items = [];
    while ($row = oci_fetch_assoc($cart_stmt)) {
        $cart_items[] = $row;
    }
    oci_free_statement($cart_stmt);

    if (empty($cart_items)) {
        throw new Exception("No items found in cart");
    }

    // Get user information
    $user_sql = "SELECT * FROM users WHERE user_id = :user_id";
    $user_stmt = oci_parse($conn, $user_sql);
    oci_bind_by_name($user_stmt, ":user_id", $_SESSION['user_id']);
    oci_execute($user_stmt);
    $user_data = oci_fetch_assoc($user_stmt);
    oci_free_statement($user_stmt);

    // Create order
    $order_sql = "INSERT INTO orders (
                    order_date,
                    order_amount,
                    total_amount,
                    status,
                    user_id,
                    cart_id,
                    collection_slot_id
                ) VALUES (
                    CURRENT_TIMESTAMP,
                    :order_amount,
                    :total_amount,
                    'pending',
                    :user_id,
                    :cart_id,
                    :collection_slot_id
                ) RETURNING order_id INTO :order_id";

    $order_stmt = oci_parse($conn, $order_sql);
    oci_bind_by_name($order_stmt, ":order_amount", $amount);
    oci_bind_by_name($order_stmt, ":total_amount", $amount);
    oci_bind_by_name($order_stmt, ":user_id", $_SESSION['user_id']);
    oci_bind_by_name($order_stmt, ":cart_id", $cart_id);
    oci_bind_by_name($order_stmt, ":collection_slot_id", $collection_slot_id);
    oci_bind_by_name($order_stmt, ":order_id", $order_id, 32);

    if (!oci_execute($order_stmt)) {
        throw new Exception("Error creating order");
    }
    oci_free_statement($order_stmt);

    // Create payment record
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
    oci_bind_by_name($payment_stmt, ":amount", $amount);
    oci_bind_by_name($payment_stmt, ":payment_method", $payment_method);
    oci_bind_by_name($payment_stmt, ":order_id", $order_id);
    oci_bind_by_name($payment_stmt, ":user_id", $_SESSION['user_id']);

    if (!oci_execute($payment_stmt)) {
        throw new Exception("Error creating payment record");
    }
    oci_free_statement($payment_stmt);

    // Update order status
    $update_order_sql = "UPDATE orders SET status = 'pending' WHERE order_id = :order_id";
    $update_order_stmt = oci_parse($conn, $update_order_sql);
    oci_bind_by_name($update_order_stmt, ":order_id", $order_id);
    oci_execute($update_order_stmt);
    oci_free_statement($update_order_stmt);

    // Clear cart
    $clear_cart_sql = "DELETE FROM product_cart WHERE cart_id = :cart_id";
    $clear_cart_stmt = oci_parse($conn, $clear_cart_sql);
    oci_bind_by_name($clear_cart_stmt, ":cart_id", $cart_id);
    oci_execute($clear_cart_stmt);
    oci_free_statement($clear_cart_stmt);

    // Generate invoice HTML
    $invoice_html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice #' . $order_id . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .invoice-container { max-width: 800px; margin: 0 auto; }
            .invoice-header { text-align: center; margin-bottom: 30px; }
            .invoice-details { margin-bottom: 30px; }
            .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .invoice-table th { background-color: #f5f5f5; }
            .invoice-total { text-align: right; margin-top: 20px; }
            .invoice-footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="invoice-header">
                <h1>INVOICE</h1>
                <p>Order #' . $order_id . '</p>
                <p>Date: ' . date('Y-m-d H:i:s') . '</p>
            </div>
            
            <div class="invoice-details">
                <h3>Customer Information</h3>
                <p>Name: ' . htmlspecialchars($user_data['FULL_NAME']) . '</p>
                <p>Email: ' . htmlspecialchars($user_data['EMAIL']) . '</p>
                <p>Phone: ' . htmlspecialchars($user_data['CONTACT_NO']) . '</p>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($cart_items as $item) {
        $item_total = $item['QUANTITY'] * $item['PRICE'];
        $invoice_html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['PRODUCT_NAME']) . '</td>
                        <td>' . $item['QUANTITY'] . '</td>
                        <td>$' . number_format($item['PRICE'], 2) . '</td>
                        <td>$' . number_format($item_total, 2) . '</td>
                    </tr>';
    }

    $invoice_html .= '
                </tbody>
            </table>

            <div class="invoice-total">
                <h3>Total Amount: $' . number_format($amount, 2) . '</h3>
                <p>Payment Method: ' . ucfirst($payment_method) . '</p>
                <p>Payment Status: Completed</p>
            </div>

            <div class="invoice-footer">
                <p>Thank you for your business!</p>
                <p>This is a computer-generated invoice, no signature required.</p>
            </div>
        </div>
    </body>
    </html>';

    // Save invoice to file
    $invoice_dir = "../invoices/";
    if (!file_exists($invoice_dir)) {
        mkdir($invoice_dir, 0777, true);
    }
    $invoice_file = $invoice_dir . "invoice_" . $order_id . ".html";
    file_put_contents($invoice_file, $invoice_html);

    // Display success page with invoice link
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Successful</title>
        <link rel="stylesheet" href="../css/homestyle.css">
        <style>
            .success-container {
                max-width: 600px;
                margin: 50px auto;
                padding: 30px;
                text-align: center;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .success-icon {
                color: #4CAF50;
                font-size: 64px;
                margin-bottom: 20px;
            }
            .success-message {
                font-size: 24px;
                color: #333;
                margin-bottom: 20px;
            }
            .order-details {
                margin: 20px 0;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 4px;
            }
            .order-id {
                font-weight: bold;
                color: #2196F3;
            }
            .button-group {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                justify-content: center;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background: #2196F3;
                color: white;
                text-decoration: none;
                border-radius: 4px;
            }
            .button:hover {
                background: #1976D2;
            }
            .button.secondary {
                background: #4CAF50;
            }
            .button.secondary:hover {
                background: #388E3C;
            }
        </style>
    </head>
    <body>
        <?php include "../../components/header.php"; ?>
        
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1 class="success-message">Order Successful!</h1>
            <div class="order-details">
                <p>Your order has been placed successfully.</p>
                <p>Order ID: <span class="order-id"><?php echo htmlspecialchars($order_id); ?></span></p>
                <p>Amount Paid: $<?php echo number_format($amount, 2); ?></p>
                <p>Payment Method: <?php echo ucfirst($payment_method); ?></p>
                <p>Thank you for your purchase!</p>
            </div>
            <div class="button-group">
                <a href="../php/home.php" class="button">Return to Home</a>
                <a href="../invoices/invoice_<?php echo $order_id; ?>.html" class="button secondary" target="_blank">View Invoice</a>
            </div>
        </div>

        <?php include "../../components/footer.php"; ?>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    // Display error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Error</title>
        <link rel="stylesheet" href="../css/homestyle.css">
        <style>
            .error-container {
                max-width: 600px;
                margin: 50px auto;
                padding: 30px;
                text-align: center;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .error-icon {
                color: #f44336;
                font-size: 64px;
                margin-bottom: 20px;
            }
            .error-message {
                font-size: 24px;
                color: #333;
                margin-bottom: 20px;
            }
            .back-button {
                display: inline-block;
                padding: 10px 20px;
                background: #2196F3;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
            }
            .back-button:hover {
                background: #1976D2;
            }
        </style>
    </head>
    <body>
        <?php include "../../components/header.php"; ?>
        
        <div class="error-container">
            <div class="error-icon">✕</div>
            <h1 class="error-message">Order Error</h1>
            <p>There was an error processing your order: <?php echo htmlspecialchars($e->getMessage()); ?></p>
            <p>Please try again or contact support.</p>
            <a href="shopping_cart.php" class="back-button">Return to Cart</a>
        </div>

        <?php include "../../components/footer.php"; ?>
    </body>
    </html>
    <?php
} finally {
    // Close database connection
    if (isset($conn)) {
        oci_close($conn);
    }
}
?>
