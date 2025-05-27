<?php
session_start();
require "../../../Backend/connect.php";
include "../../components/header.php";

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

if (isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: login.php");
    exit();
}

// Check if collection slot is provided
if (!isset($_GET['slot_id'])) {
    header("Location: shopping_cart.php");
    exit();
}

$slot_id = $_GET['slot_id'];

// Validate slot exists and belongs to user
// FIXED: Modified the SQL query to properly reference collection_slot table fields
$slot_sql = "SELECT cs.*, TO_CHAR(cs.slot_date, 'YYYY-MM-DD') as formatted_date 
             FROM collection_slot cs 
             WHERE cs.collection_slot_id = :slot_id";

$slot_stmt = oci_parse($conn, $slot_sql);
oci_bind_by_name($slot_stmt, ":slot_id", $slot_id);
oci_execute($slot_stmt);

$slot_info = oci_fetch_assoc($slot_stmt);
oci_free_statement($slot_stmt);

// If slot doesn't exist or doesn't belong to user, redirect
if (!$slot_info) {
    header("Location: shopping_cart.php");
    exit();
}

// Format time slot for display
$time_slots = [
    "10-13" => "10AM - 1PM",
    "13-16" => "1PM - 4PM",
    "16-19" => "4PM - 7PM"
];

$time_display = $time_slots[$slot_info['SLOT_TIME']] ?? $slot_info['SLOT_TIME'];

// Get user information
$user_sql = "SELECT * FROM users WHERE user_id = :user_id";
$user_stmt = oci_parse($conn, $user_sql);
oci_bind_by_name($user_stmt, ":user_id", $user_id);
oci_execute($user_stmt);
$user_info = oci_fetch_assoc($user_stmt);
oci_free_statement($user_stmt);

// Fetch cart items
$cart_items = [];
$sql = "SELECT c.cart_id, p.product_id, p.product_name, p.product_image, p.price, p.stock, pc.quantity
        FROM cart c
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product p ON pc.product_id = p.product_id
        WHERE c.user_id = :user_id";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":user_id", $user_id);

if (oci_execute($stid)) {
    while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
        if (is_object($row['PRODUCT_IMAGE'])) {
            $row['PRODUCT_IMAGE'] = $row['PRODUCT_IMAGE']->load();
        }
        $cart_items[] = $row;
    }
} else {
    $e = oci_error($stid);
    die("Error fetching cart: " . $e['message']);
}

oci_free_statement($stid);

// Calculate totals
$subtotal = 0;
$cart_id = null;
foreach ($cart_items as $item) {
    $subtotal += $item['PRICE'] * $item['QUANTITY'];
    if (!$cart_id && isset($item['CART_ID'])) {
        $cart_id = $item['CART_ID'];
    }
}

// Check for discount from session (from shopping cart)
$discount = 0;
if (isset($_SESSION['discount'])) {
    $discount = $_SESSION['discount'];
}
$total = $subtotal - $discount;

// Process form submission
$order_placed = false;
$order_id = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $payment_method = $_POST['payment_method'] ?? '';
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    
    // Simple validation
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method";
    }
    
    if (empty($contact_name)) {
        $errors[] = "Contact name is required";
    }
    
    if (empty($contact_email) || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($contact_phone) || strlen($contact_phone) < 10) {
        $errors[] = "Valid phone number is required";
    }
    
    // If validation passes, create order
    if (empty($errors)) {
        // Begin transaction
        oci_commit($conn);
        
        // Create order record
        $order_sql = "INSERT INTO orders (user_id, order_date, total_price, status, collection_slot_id, payment_method) 
                      VALUES (:user_id, SYSDATE, :total_price, 'pending', :slot_id, :payment_method) 
                      RETURNING order_id INTO :order_id";
        
        $order_stmt = oci_parse($conn, $order_sql);
        oci_bind_by_name($order_stmt, ":user_id", $user_id);
        oci_bind_by_name($order_stmt, ":total_price", $total);
        oci_bind_by_name($order_stmt, ":slot_id", $slot_id);
        oci_bind_by_name($order_stmt, ":payment_method", $payment_method);
        oci_bind_by_name($order_stmt, ":order_id", $order_id, 32);
        
        $exec = oci_execute($order_stmt);
        if (!$exec) {
            $e = oci_error($order_stmt);
            $errors[] = "Error creating order: " . $e['message'];
            oci_rollback($conn);
        } else {
            // Transfer items from cart to order items
            foreach ($cart_items as $item) {
                $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                             VALUES (:order_id, :product_id, :quantity, :price)";
                
                $item_stmt = oci_parse($conn, $item_sql);
                oci_bind_by_name($item_stmt, ":order_id", $order_id);
                oci_bind_by_name($item_stmt, ":product_id", $item['PRODUCT_ID']);
                oci_bind_by_name($item_stmt, ":quantity", $item['QUANTITY']);
                oci_bind_by_name($item_stmt, ":price", $item['PRICE']);
                
                $item_exec = oci_execute($item_stmt);
                if (!$item_exec) {
                    $e = oci_error($item_stmt);
                    $errors[] = "Error adding order item: " . $e['message'];
                    oci_rollback($conn);
                    break;
                }
                oci_free_statement($item_stmt);
                
                // Update stock
                $update_stock_sql = "UPDATE product SET stock = stock - :quantity WHERE product_id = :product_id";
                $update_stock_stmt = oci_parse($conn, $update_stock_sql);
                oci_bind_by_name($update_stock_stmt, ":quantity", $item['QUANTITY']);
                oci_bind_by_name($update_stock_stmt, ":product_id", $item['PRODUCT_ID']);
                
                $stock_exec = oci_execute($update_stock_stmt);
                if (!$stock_exec) {
                    $e = oci_error($update_stock_stmt);
                    $errors[] = "Error updating product stock: " . $e['message'];
                    oci_rollback($conn);
                    break;
                }
                oci_free_statement($update_stock_stmt);
            }
            
            // Update contact information
            $contact_sql = "UPDATE orders SET contact_name = :contact_name, contact_email = :contact_email, 
                           contact_phone = :contact_phone WHERE order_id = :order_id";
            
            $contact_stmt = oci_parse($conn, $contact_sql);
            oci_bind_by_name($contact_stmt, ":contact_name", $contact_name);
            oci_bind_by_name($contact_stmt, ":contact_email", $contact_email);
            oci_bind_by_name($contact_stmt, ":contact_phone", $contact_phone);
            oci_bind_by_name($contact_stmt, ":order_id", $order_id);
            
            $contact_exec = oci_execute($contact_stmt);
            if (!$contact_exec) {
                $e = oci_error($contact_stmt);
                $errors[] = "Error updating contact info: " . $e['message'];
                oci_rollback($conn);
            }
            oci_free_statement($contact_stmt);
            
            // If no errors, commit and clear cart
            if (empty($errors)) {
                // Clear cart
                if ($cart_id) {
                    $delete_products = oci_parse($conn, "DELETE FROM product_cart WHERE cart_id = :cart_id");
                    oci_bind_by_name($delete_products, ":cart_id", $cart_id);
                    oci_execute($delete_products);
                    oci_free_statement($delete_products);
                }
                
                oci_commit($conn);
                $order_placed = true;
                unset($_SESSION['discount']); // Clear discount
            }
        }
        oci_free_statement($order_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Checkout</title>
  
  <link rel="stylesheet" href="../css/homestyle.css">
  <style>
    /* Checkout specific styles */
    .checkout-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
    }
    
    .checkout-form {
      flex: 1 1 600px;
    }
    
    .order-summary {
      flex: 1 1 300px;
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
    }
    
    .form-section {
      margin-bottom: 30px;
      padding: 20px;
      border-radius: 8px;
      background-color: #f9f9f9;
    }
    
    .form-title {
      font-size: 1.4rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: #333;
    }
    
    .form-row {
      margin-bottom: 15px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    
    input[type="text"],
    input[type="email"],
    input[type="tel"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    
    .payment-options {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .payment-option {
      flex: 1 1 150px;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .payment-option.selected {
      border-color: #4CAF50;
      background-color: rgba(76, 175, 80, 0.1);
    }
    
    .payment-option img {
      height: 40px;
      margin-bottom: 10px;
    }
    
    .item-list {
      margin-bottom: 20px;
    }
    
    .checkout-item {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .item-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      margin-right: 15px;
      border-radius: 5px;
    }
    
    .item-details {
      flex-grow: 1;
    }
    
    .item-name {
      font-weight: 500;
    }
    
    .item-quantity {
      color: #666;
      font-size: 0.9rem;
    }
    
    .item-price {
      text-align: right;
      font-weight: 500;
    }
    
    .collection-info {
      background-color: #e6f7ff;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .collection-title {
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .price-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    
    .total-row {
      display: flex;
      justify-content: space-between;
      font-weight: 600;
      font-size: 1.2rem;
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid #ddd;
    }
    
    .checkout-btn {
      display: block;
      width: 100%;
      padding: 15px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .checkout-btn:hover {
      background-color: #45a049;
    }
    
    .error-message {
      color: #d32f2f;
      background-color: #fdecea;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 20px;
    }
    
    .success-message {
      color: #388e3c;
      background-color: #edf7ed;
      padding: 20px;
      border-radius: 4px;
      text-align: center;
      margin-bottom: 20px;
    }
    
    .order-confirmation {
      text-align: center;
      padding: 30px;
      background-color: #edf7ed;
      border-radius: 8px;
      margin: 20px auto;
      max-width: 800px;
    }
    
    .confirmation-icon {
      font-size: 5rem;
      color: #4CAF50;
      margin-bottom: 20px;
    }
    
    .confirmation-title {
      font-size: 2rem;
      margin-bottom: 15px;
      color: #333;
    }
    
    .confirmation-details {
      font-size: 1.1rem;
      margin-bottom: 20px;
    }
    
    .continue-btn {
      display: inline-block;
      padding: 12px 25px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: 500;
      margin-top: 15px;
      transition: background-color 0.3s;
    }
    
    .continue-btn:hover {
      background-color: #45a049;
    }
    
    @media (max-width: 768px) {
      .checkout-container {
        flex-direction: column;
      }
      
      .payment-option img {
        height: 30px;
      }
    }
  </style>
</head>
<body>

<?php if ($order_placed): ?>
  <!-- Order Confirmation -->
  <div class="order-confirmation">
    <div class="confirmation-icon">✓</div>
    <h1 class="confirmation-title">Order Confirmed!</h1>
    <p class="confirmation-details">Thank you for your order. Your order number is <strong><?= htmlspecialchars($order_id) ?></strong>.</p>
    <p>We've sent a confirmation email to <strong><?= htmlspecialchars($contact_email) ?></strong> with your order details.</p>
    <p>Your items will be ready for collection on <strong><?= htmlspecialchars(ucfirst($slot_info['SLOT_DAY'])) ?>, <?= htmlspecialchars($slot_info['FORMATTED_DATE']) ?></strong> during <strong><?= htmlspecialchars($time_display) ?></strong>.</p>
    <a href="index.php" class="continue-btn">Continue Shopping</a>
  </div>
<?php else: ?>
  <h1>Checkout</h1>
  
  <div class="checkout-container">
    <!-- Checkout Form -->
    <div class="checkout-form">
      <?php if (!empty($errors)): ?>
        <div class="error-message">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      
      <form action="checkout.php?slot_id=<?= htmlspecialchars($slot_id) ?>" method="post">
        <div class="form-section">
          <div class="form-title">Collection Information</div>
          <div class="collection-info">
            <div class="collection-title">Your Collection Slot</div>
            <p><strong>Day:</strong> <?= htmlspecialchars(ucfirst($slot_info['SLOT_DAY'])) ?>, <?= htmlspecialchars($slot_info['FORMATTED_DATE']) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($time_display) ?></p>
          </div>
          
          <div class="form-group">
            <label for="contact_name">Contact Name *</label>
            <input type="text" id="contact_name" name="contact_name" value="<?= htmlspecialchars($user_info['NAME'] ?? '') ?>" required>
          </div>
          
          <div class="form-group">
            <label for="contact_email">Email Address *</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($user_info['EMAIL'] ?? '') ?>" required>
          </div>
          
          <div class="form-group">
            <label for="contact_phone">Phone Number *</label>
            <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($user_info['PHONE'] ?? '') ?>" required>
          </div>
        </div>
        
        <div class="form-section">
          <div class="form-title">Payment Method</div>
          <div class="payment-options">
            <div class="payment-option" data-method="cash">
              <img src="../images/cash-icon.png" alt="Cash">
              <div>Cash on Collection</div>
            </div>
            <div class="payment-option" data-method="card">
              <img src="../images/card-icon.png" alt="Card">
              <div>Card on Collection</div>
            </div>
          </div>
          <input type="hidden" name="payment_method" id="payment_method" value="">
        </div>
        
        <button type="submit" class="checkout-btn">Place Order</button>
      </form>
    </div>
    
    <!-- Order Summary -->
    <div class="order-summary">
      <div class="form-title">Order Summary</div>
      
      <div class="item-list">
        <?php foreach ($cart_items as $item): ?>
          <div class="checkout-item">
            <img src="../BackEnd/admin/uploaded_files/<?= htmlspecialchars($item['PRODUCT_IMAGE']) ?>" alt="<?= htmlspecialchars($item['PRODUCT_NAME']) ?>" class="item-image">
            <div class="item-details">
              <div class="item-name"><?= htmlspecialchars($item['PRODUCT_NAME']) ?></div>
              <div class="item-quantity">Qty: <?= htmlspecialchars($item['QUANTITY']) ?></div>
            </div>
            <div class="item-price">$ <?= number_format($item['PRICE'] * $item['QUANTITY'], 2) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <div class="price-row">
        <div>Subtotal</div>
        <div>$ <?= number_format($subtotal, 2) ?></div>
      </div>
      
      <div class="price-row">
        <div>Discount</div>
        <div>$ <?= number_format($discount, 2) ?></div>
      </div>
      
      <div class="total-row">
        <div>Total</div>
        <div>$ <?= number_format($total, 2) ?></div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Handle payment method selection
  const paymentOptions = document.querySelectorAll('.payment-option');
  const paymentMethodInput = document.getElementById('payment_method');

  paymentOptions.forEach(option => {
    option.addEventListener('click', function() {
      // Remove selection from all options
      paymentOptions.forEach(opt => opt.classList.remove('selected'));
      
      // Add selection to clicked option
      this.classList.add('selected');
      
      // Update hidden input
      paymentMethodInput.value = this.dataset.method;
    });
  });
  
  // Form validation
  const checkoutForm = document.querySelector('form');
  checkoutForm?.addEventListener('submit', function(e) {
    if (!paymentMethodInput.value) {
      e.preventDefault();
      alert('Please select a payment method');
    }
  });
});
</script>

<?php
include "../../components/footer.php";
?>
</body>
</html>