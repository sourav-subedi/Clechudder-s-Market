<?php
session_start();
require "../../../Backend/connect.php";
include "../../components/header.php";

// PayPal Configuration
$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
$paypalID = 'sb-k8xa4741152020@business.example.com';

$conn = getDBConnection();
if (!$conn) {
  die("Database connection failed");
}
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
} else {
  header("Location:  ../../loginRegister/php/login.php");
  exit();
}

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

$total_price = 0;
$cart_id = null;
foreach ($cart_items as $item) {
  $total_price += $item['PRICE'] * $item['QUANTITY'];
  if (!$cart_id && isset($item['CART_ID'])) {
    $cart_id = $item['CART_ID'];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Shopping Cart</title>

  <link rel="stylesheet" href="../css/shopping_cart.css">
  <link rel="stylesheet" href="../css/homestyle.css">

  <style>
    /* PayPal Integration Styles */
    .payment-options {
      margin-top: 20px;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
    }

    .payment-header {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 15px;
      color: #333;
    }

    .payment-methods {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .payment-option {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
      background: white;
    }

    .payment-option:hover {
      border-color: #007cba;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .payment-option.selected {
      border-color: #007cba;
      background: #f0f8ff;
    }

    .payment-radio {
      margin-right: 12px;
    }

    .payment-label {
      display: flex;
      align-items: center;
      gap: 10px;
      flex: 1;
    }

    .payment-icon {
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .payment-text {
      font-weight: 500;
      color: #333;
    }

    .paypal-icon {
      background: linear-gradient(135deg, #0070ba 0%, #003087 100%);
      color: white;
      border-radius: 4px;
      font-weight: bold;
      font-size: 12px;
      padding: 6px 8px;
      margin-right: 10px;
    }



    .checkout-btn {
      margin-top: 15px;
    }

    @media (max-width: 768px) {
      .payment-methods {
        gap: 8px;
      }

      .payment-option {
        padding: 10px 12px;
      }

      .payment-icon {
        width: 28px;
        height: 28px;
      }

      .paypal-icon {
        font-size: 10px;
        padding: 4px 6px;
      }
    }
  </style>
</head>

<body>

  <h1>Your cart</h1>

  <div class="cart-container">
    <?php if (empty($cart_items)): ?>
      <div class="empty-cart">
        <p>Your cart is empty</p>
      </div>
    <?php else: ?>
      <?php foreach ($cart_items as $item):
        $subtotal = $item['PRICE'] * $item['QUANTITY'];
      ?>
        <div class="cart-item" data-product-id="<?= htmlspecialchars($item['PRODUCT_ID']) ?>">
          <img src="../../trader/php/uploaded_files/<?= htmlspecialchars($item['PRODUCT_IMAGE']) ?>" alt="<?= htmlspecialchars($item['PRODUCT_NAME']) ?>" class="item-image">
          <div class="item-details">
            <div class="item-name"><?= htmlspecialchars($item['PRODUCT_NAME']) ?></div>
            <div class="item-price">$ <span class="price-value"><?= number_format($item['PRICE'], 2) ?></span></div>
          </div>
          <div class="quantity-actions">
            <div class="quantity-selector">
              <button class="quantity-btn minus-btn">-</button>
              <div class="quantity"><?= htmlspecialchars($item['QUANTITY']) ?></div>
              <button class="quantity-btn plus-btn">+</button>
            </div>
          </div>
          <button class="remove-btn">×</button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($cart_items)): ?>
      <button id="clear-cart-btn" class="clear-cart-btn">Clear Cart</button>
    <?php endif; ?>

  </div>

  <div class="collection-slot">
    <div class="collection-header">
      <div class="collection-label">Collection Slot</div>
      <div id="slot-rules-toggle" class="slot-rules-toggle" style="cursor: pointer;">ⓘ</div>
    </div>

    <div id="collection-info" class="collection-info" style="display: none;">
      <p>Collection slots available Wednesday to Friday.</p>
      <p>Time slots: 10AM-1PM, 1PM-4PM, 4PM-7PM.</p>
      <p>Must be selected at least 24 hours in advance.</p>
      <p>Limited to 20 orders per slot.</p>
    </div>

    <div class="dropdown-container">
      <select id="day-select" class="dropdown">
        <option value="" disabled selected>Select Day</option>
      </select>
      <select id="time-select" class="dropdown" disabled>
        <option value="" disabled selected>Select Time</option>
        <option value="10-13">10AM - 1PM</option>
        <option value="13-16">1PM - 4PM</option>
        <option value="16-19">4PM - 7PM</option>
      </select>
    </div>

    <div id="slot-availability" class="slot-availability"></div>
    <div id="collection-error" class="collection-error"></div>
    <div id="collection-success" class="collection-success"></div>
  </div>

  <div class="summary-container">
    <div class="summary-header">Order Summary</div>

    <div class="promo-container">
      <input type="text" class="promo-input" placeholder="Add promo code">
      <button class="apply-btn">Apply</button>
    </div>

    <div class="summary-row">
      <div>Subtotal</div>
      <div>$ <span id="subtotal"><?= number_format($total_price) ?></span></div>
    </div>

    <div class="summary-row">
      <div>Discount</div>
      <div>$ <span id="discount">0.00</span></div>
    </div>

    <div class="summary-total">
      <div>Total</div>
      <div>$ <span id="total"><?= number_format($total_price) ?></span></div>
    </div>

    <!-- Payment Options Section -->
    <div class="payment-options">
      <div class="payment-header">Payment Method</div>
      <div class="payment-methods">
        <div class="payment-option selected" data-method="paypal">
          <input type="radio" name="payment_method" value="paypal" class="payment-radio" id="paypal-payment" checked>
          <label for="paypal-payment" class="payment-label">
            <div class="payment-icon">
              <div class="paypal-icon">PayPal</div>
            </div>
            <span class="payment-text">Pay with PayPal</span>
          </label>
        </div>
      </div>
    </div>

    <?php if (!empty($cart_items)): ?>
      <form id="paypal-form" action="<?php echo $paypalURL; ?>" method="post" style="display: none;">
        <input type="hidden" name="business" value="<?php echo $paypalID; ?>">
        <input type="hidden" name="cmd" value="_cart">
        <input type="hidden" name="upload" value="1">
        <input type="hidden" name="currency_code" value="USD">
        
        <?php 
        $item_counter = 1;
        foreach ($cart_items as $item): 
            $item_price = $item['PRICE'];
        ?>
            <input type="hidden" name="item_name_<?php echo $item_counter; ?>" value="<?php echo htmlspecialchars($item['PRODUCT_NAME']); ?>">
            <input type="hidden" name="item_number_<?php echo $item_counter; ?>" value="<?php echo $item['PRODUCT_ID']; ?>">
            <input type="hidden" name="amount_<?php echo $item_counter; ?>" value="<?php echo number_format($item_price, 2, '.', ''); ?>">
            <input type="hidden" name="quantity_<?php echo $item_counter; ?>" value="<?php echo $item['QUANTITY']; ?>">
        <?php 
            $item_counter++;
        endforeach; 
        ?>
        
        <input type="hidden" name="no_shipping" value="1">
        <input type="hidden" name="no_note" value="1">
        <input type="hidden" name="lc" value="US">
        <input type="hidden" name="bn" value="PP-BuyNowBF">
        <input type="hidden" name="return" value="http://localhost/Implementation_And_Coding/Frontend/customer/php/success.php?cart_id=<?php echo $cart_id; ?>&payment_method=paypal&amount=<?php echo $total_price; ?>&collection_slot_id=">
        <input type="hidden" name="cancel_return" value="http://localhost/Implementation_And_Coding/Frontend/customer/php/cancel.php?cart_id=<?php echo $cart_id; ?>">
        <input type="hidden" name="notify_url" value="http://localhost/Implementation_And_Coding/Frontend/customer/php/ipn.php?cart_id=<?php echo $cart_id; ?>">
        <input type="hidden" name="custom" value="<?php echo $user_id . '|' . $cart_id; ?>">
      </form>
      <button id="checkout-btn" class="checkout-btn">Proceed to Checkout</button>
    <?php endif; ?>
  </div>

  <!-- Add a hidden input to store cart_id -->
  <input type="hidden" id="cart-id" value="<?= htmlspecialchars($cart_id) ?>">

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Payment method selection
      document.querySelectorAll('.payment-option').forEach(option => {
        option.addEventListener('click', function() {
          // Remove selected class from all options
          document.querySelectorAll('.payment-option').forEach(opt => {
            opt.classList.remove('selected');
          });

          // Add selected class to clicked option
          this.classList.add('selected');

          // Check the radio button
          const radio = this.querySelector('.payment-radio');
          radio.checked = true;
        });
      });

      // Set initial selected state for PayPal
      document.querySelector('.payment-option[data-method="paypal"]').classList.add('selected');

      // Collection slot system
      const daySelect = document.getElementById('day-select');
      const timeSelect = document.getElementById('time-select');
      const slotAvailability = document.getElementById('slot-availability');
      const collectionError = document.getElementById('collection-error');
      const collectionSuccess = document.getElementById('collection-success');
      const slotRulesToggle = document.getElementById('slot-rules-toggle');
      const collectionInfo = document.getElementById('collection-info');
      const cartId = document.getElementById('cart-id').value;

      // Current date and time for 24-hour validation
      const now = new Date();

      let selectedSlotId = null;

      // Populate available days (starting from tomorrow, only Wed-Fri)
      populateAvailableDays();

      // Toggle collection info display
      slotRulesToggle.addEventListener('click', function() {
        if (collectionInfo.style.display === 'none') {
          collectionInfo.style.display = 'block';
        } else {
          collectionInfo.style.display = 'none';
        }
      });

      // Enable time selection after day is selected
      daySelect.addEventListener('change', function() {
        timeSelect.disabled = false;
        timeSelect.selectedIndex = 0;
        slotAvailability.textContent = '';
        collectionError.style.display = 'none';
        collectionSuccess.style.display = 'none';
      });

      // Check slot availability when time is selected
      timeSelect.addEventListener('change', function() {
        const selectedDay = daySelect.value;
        const selectedTimeSlot = timeSelect.value;

        if (!selectedDay || !selectedTimeSlot) return;

        const dayParts = selectedDay.split('-');
        const dayName = dayParts[0];
        const selectedDate = `${dayParts[1]}-${dayParts[2]}-${dayParts[3]}`; // YYYY-MM-DD
        const selectedDayOption = daySelect.options[daySelect.selectedIndex];
        const selectedDateStr = selectedDayOption.dataset.fullDate;

        // In a real system, we would check actual availability from the server
        checkSlotAvailability(selectedDateStr, dayName, selectedTimeSlot);
      });

      // Function to check slot availability from server
      function checkSlotAvailability(dateStr, dayName, timeSlot) {
        // For now, we'll simulate slot availability
        // This would be replaced with an actual AJAX call to check availability
        const availableSlots = Math.floor(Math.random() * 21); // Random number between 0-20

        // Display availability with appropriate styling
        displaySlotAvailability(availableSlots, dateStr, dayName);
      }

      // Function to populate available days
      function populateAvailableDays() {
        // Clear existing options except the default
        while (daySelect.options.length > 1) {
          daySelect.remove(1);
        }

        // Get the day names
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Start from tomorrow and look ahead for 14 days
        for (let i = 1; i <= 14; i++) {
          const futureDate = new Date(now);
          futureDate.setDate(now.getDate() + i);

          const dayOfWeek = futureDate.getDay();
          const dayName = dayNames[dayOfWeek];

          // Only include Wednesday (3), Thursday (4), and Friday (5)
          if (dayOfWeek >= 3 && dayOfWeek <= 5) {
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const month = monthNames[futureDate.getMonth()];
            const day = futureDate.getDate();
            const year = futureDate.getFullYear();
            const monthNum = futureDate.getMonth() + 1;

            const option = document.createElement('option');
            option.value = `${dayName.toLowerCase()}-${year}-${monthNum}-${day}`;
            option.textContent = `${dayName}, ${day} ${month}`;

            // Add data attribute for full date (YYYY-MM-DD)
            const formattedMonth = monthNum < 10 ? `0${monthNum}` : monthNum;
            const formattedDay = day < 10 ? `0${day}` : day;
            option.dataset.fullDate = `${year}-${formattedMonth}-${formattedDay}`;

            daySelect.appendChild(option);
          }
        }
      }

      // Function to display slot availability
      function displaySlotAvailability(availableSlots, dateStr, dayName) {
        slotAvailability.innerHTML = '';

        // Create span element for availability text
        const availabilityText = document.createElement('span');
        availabilityText.textContent = 'Availability: ';
        slotAvailability.appendChild(availabilityText);

        // Create span element for the count badge
        const countBadge = document.createElement('span');
        countBadge.textContent = availableSlots > 0 ? availableSlots : 'FULL';
        countBadge.classList.add('slot-count');

        // Style based on availability
        if (availableSlots > 10) {
          countBadge.classList.add('slot-available');
        } else if (availableSlots > 0) {
          countBadge.classList.add('slot-limited');
        } else {
          countBadge.classList.add('slot-full');
        }

        slotAvailability.appendChild(countBadge);

        // Validation for checkout
        const selectedDayOption = daySelect.options[daySelect.selectedIndex];
        const selectedDateStr = selectedDayOption.dataset.fullDate;
        const selectedDate = new Date(selectedDateStr);

        // Check if selected date is at least 24 hours from now
        const is24HoursAhead = selectedDate.getTime() - now.getTime() >= 24 * 60 * 60 * 1000;

        if (!is24HoursAhead) {
          collectionError.textContent = 'Collection time must be at least 24 hours from now.';
          collectionError.style.display = 'block';
          collectionSuccess.style.display = 'none';
        } else if (availableSlots <= 0) {
          collectionError.textContent = 'This slot is fully booked. Please select another time.';
          collectionError.style.display = 'block';
          collectionSuccess.style.display = 'none';
        } else {
          collectionError.style.display = 'none';
          collectionSuccess.textContent = 'Collection slot available! Continue to checkout.';
          collectionSuccess.style.display = 'block';
        }
      }

      // Quantity and remove functionality
      document.querySelectorAll('.minus-btn').forEach(button => {
        button.addEventListener('click', function() {
          const quantityElement = this.nextElementSibling;
          let quantity = parseInt(quantityElement.textContent);
          if (quantity > 1) {
            quantity--;
            updateQuantity(this.closest('.cart-item'), quantity);
          }
        });
      });

      document.querySelectorAll('.plus-btn').forEach(button => {
        button.addEventListener('click', function() {
          const quantityElement = this.previousElementSibling;
          let quantity = parseInt(quantityElement.textContent);
          quantity++;
          updateQuantity(this.closest('.cart-item'), quantity);
        });
      });

      document.querySelectorAll('.remove-btn').forEach(button => {
        button.addEventListener('click', function() {
          const cartItem = this.closest('.cart-item');
          if (confirm('Are you sure you want to remove this item?')) {
            removeItem(cartItem);
          }
        });
      });

      // Apply promo code
      document.querySelector('.apply-btn').addEventListener('click', function() {
        const promoInput = document.querySelector('.promo-input');
        if (promoInput.value.trim() !== '') {
          // In a real app, this would validate with the server
          applyDiscount(10); // 10% discount for demo
          promoInput.value = '';
        } else {
          alert('Please enter a promo code');
        }
      });

      // Checkout button
      document.querySelector('#checkout-btn').addEventListener('click', function() {
        // Validate collection slot is selected
        if (daySelect.selectedIndex === 0 || timeSelect.selectedIndex === 0) {
          collectionError.textContent = 'Please select a collection day and time before checkout.';
          collectionError.style.display = 'block';
          collectionSuccess.style.display = 'none';
          return;
        }

        // Check if any errors are displayed
        if (collectionError.style.display === 'block') {
          alert('Please fix the collection slot issues before proceeding to checkout.');
          return;
        }

        // Get the selected payment method
        const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

        if (selectedPaymentMethod === 'paypal') {
          // Get the selected slot information
          const selectedDayOption = daySelect.options[daySelect.selectedIndex];
          const selectedTimeOption = timeSelect.options[timeSelect.selectedIndex];
          const selectedDay = daySelect.value.split('-')[0]; // Get day name
          const selectedDate = selectedDayOption.dataset.fullDate; // YYYY-MM-DD
          const selectedTime = timeSelect.value; // e.g. "10-13"

          // Save the collection slot to the database
          saveCollectionSlot(selectedDate, selectedDay, selectedTime, function(success, message, slotId) {
            if (success) {
              // Update the return URL with the collection slot ID
              const returnUrl = document.querySelector('input[name="return"]');
              returnUrl.value += slotId;

              // Store the payment method
              const paymentMethodInput = document.createElement('input');
              paymentMethodInput.type = 'hidden';
              paymentMethodInput.name = 'payment_method';
              paymentMethodInput.value = 'paypal';
              document.getElementById('paypal-form').appendChild(paymentMethodInput);

              // Store the total amount
              const amountInput = document.createElement('input');
              amountInput.type = 'hidden';
              amountInput.name = 'amount';
              amountInput.value = document.getElementById('total').textContent;
              document.getElementById('paypal-form').appendChild(amountInput);

              // Store the cart ID
              const cartIdInput = document.createElement('input');
              cartIdInput.type = 'hidden';
              cartIdInput.name = 'cart_id';
              cartIdInput.value = document.getElementById('cart-id').value;
              document.getElementById('paypal-form').appendChild(cartIdInput);

              // Submit the PayPal form
              document.getElementById('paypal-form').submit();
            } else {
              // If error, show to user
              alert(`Error: ${message}`);
            }
          });
        } else {
          // Handle other payment methods if needed
          window.location.href = 'checkout.php';
        }
      });

      // Function to save collection slot to the database
      function saveCollectionSlot(slotDate, slotDay, slotTime, callback) {
        const formData = new FormData();
        formData.append('slot_date', slotDate);
        formData.append('slot_day', slotDay);
        formData.append('slot_time', slotTime);

        fetch('save_collection_slot.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              callback(true, 'Collection slot saved successfully', data.collection_slot_id);
            } else {
              callback(false, data.message || 'Unknown error occurred', null);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            callback(false, 'Network error, please try again', null);
          });
      }

      // Function to update quantity via AJAX
      function updateQuantity(cartItem, newQuantity) {
        const productId = cartItem.dataset.productId;
        const quantityElement = cartItem.querySelector('.quantity');
        const priceElement = cartItem.querySelector('.price-value');
        const price = parseFloat(priceElement.textContent);

        // Update UI immediately
        quantityElement.textContent = newQuantity;
        updateTotals();

        // Send update to server
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', newQuantity);

        fetch('update_quantity.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            if (data.trim() !== 'success') {
              console.error('Error updating quantity:', data);
            }
          })
          .catch(error => console.error('Error:', error));
      }

      // Function to remove item via AJAX
      function removeItem(cartItem) {
        const productId = cartItem.dataset.productId;

        // Fade out animation
        cartItem.style.opacity = '0';
        setTimeout(() => {
          cartItem.remove();
          updateTotals();

          // Check if cart is empty
          if (document.querySelectorAll('.cart-item').length === 0) {
            const cartContainer = document.querySelector('.cart-container');
            cartContainer.innerHTML = '<div class="empty-cart"><p>Your cart is empty</p></div>';
          }
        }, 300);

        // Send removal to server
        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('remove_item.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            if (data.trim() !== 'success') {
              console.error('Error removing item:', data);
            }
          })
          .catch(error => console.error('Error:', error));
      }

      // Function to update all totals
      function updateTotals() {
        let subtotal = 0;

        document.querySelectorAll('.cart-item').forEach(item => {
          const quantity = parseInt(item.querySelector('.quantity').textContent);
          const price = parseFloat(item.querySelector('.price-value').textContent);
          subtotal += quantity * price;
        });

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);

        // Apply discount if any
        const discountElement = document.getElementById('discount');
        const discount = parseFloat(discountElement.textContent) || 0;
        const total = subtotal - discount;

        document.getElementById('total').textContent = total.toFixed(2);
      }

      // Function to apply discount
      function applyDiscount(percent) {
        const subtotal = parseFloat(document.getElementById('subtotal').textContent);
        const discount = subtotal * (percent / 100);
        const total = subtotal - discount;

        document.getElementById('discount').textContent = discount.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
      }

      document.getElementById('clear-cart-btn')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear the entire cart?')) {
          fetch('clear_cart.php', {
              method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
              if (data.trim() === 'success') {
                // Clear UI
                document.querySelector('.cart-container').innerHTML = '<div class="empty-cart"><p>Your cart is empty</p></div>';
                document.getElementById('subtotal').textContent = '0.00';
                document.getElementById('discount').textContent = '0.00';
                document.getElementById('total').textContent = '0.00';
              } else {
                alert('Failed to clear cart: ' + data);
              }
            })
            .catch(err => {
              console.error(err);
              alert('An error occurred while clearing the cart.');
            });
        }
      });
    });
  </script>

  <!-- FOOTER -->
  <?php
  include "../../components/footer.php";
  ?>
</body>

</html>