<?php
require "../../../Backend/connect.php";
session_start();

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'] ?? null;

// Redirect if user is not logged in
if (!$user_id) {
    $_SESSION['message'] = "Please login to view your wishlist!";
    $_SESSION['messageType'] = "error";
    header("Location: ../../loginRegister/php/login.php");
    exit();
}

// Check if the user has a wishlist
$check_wishlist_sql = "SELECT * FROM wishlist WHERE user_id = :user_id";
$stmt = oci_parse($conn, $check_wishlist_sql);
oci_bind_by_name($stmt, ":user_id", $user_id);
oci_execute($stmt);
$wishlist = oci_fetch_array($stmt, OCI_ASSOC);
oci_free_statement($stmt);

if (!$wishlist) {
    // First verify that the user exists
    $check_user_sql = "SELECT user_id FROM users WHERE user_id = :user_id";
    $stmt = oci_parse($conn, $check_user_sql);
    oci_bind_by_name($stmt, ":user_id", $user_id);
    oci_execute($stmt);
    $user_exists = oci_fetch_array($stmt, OCI_ASSOC);
    oci_free_statement($stmt);

    if ($user_exists) {
        // Create wishlist if user exists
        $create_wishlist_sql = "INSERT INTO wishlist (wishlist_id, no_of_items, user_id) VALUES (wishlist_seq.NEXTVAL, 0, :user_id)";
        $stmt = oci_parse($conn, $create_wishlist_sql);
        oci_bind_by_name($stmt, ":user_id", $user_id);
        $success = oci_execute($stmt);
        oci_free_statement($stmt);
        
        if ($success) {
            // Get the newly created wishlist
            $stmt = oci_parse($conn, $check_wishlist_sql);
            oci_bind_by_name($stmt, ":user_id", $user_id);
            oci_execute($stmt);
            $wishlist = oci_fetch_array($stmt, OCI_ASSOC);
            oci_free_statement($stmt);
        } else {
            $_SESSION['message'] = "Error creating wishlist. Please try again.";
            $_SESSION['messageType'] = "error";
            header("Location: product_page.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "User account not found. Please log in again.";
        $_SESSION['messageType'] = "error";
        header("Location: ../../loginRegister/php/login.php");
        exit();
    }
}

if (!$wishlist) {
    $_SESSION['message'] = "Error accessing wishlist. Please try again.";
    $_SESSION['messageType'] = "error";
    header("Location: product_page.php");
    exit();
}

$wishlist_id = $wishlist['WISHLIST_ID'];
$item_count = $wishlist['NO_OF_ITEMS'];

// Handle remove from wishlist
if (isset($_POST['remove_from_wishlist'])) {
    $product_id = (int) $_POST['product_id'];
    
    // Delete the product from wishlist
    $delete_sql = "DELETE FROM wishlist_product WHERE wishlist_id = :wishlist_id AND product_id = :product_id";
    $stmt = oci_parse($conn, $delete_sql);
    oci_bind_by_name($stmt, ":wishlist_id", $wishlist_id);
    oci_bind_by_name($stmt, ":product_id", $product_id);
    oci_execute($stmt);
    oci_free_statement($stmt);
    
    // Update the number of items in wishlist
    $update_count_sql = "UPDATE wishlist SET no_of_items = (SELECT COUNT(*) FROM wishlist_product WHERE wishlist_id = :wishlist_id) WHERE wishlist_id = :wishlist_id";
    $stmt = oci_parse($conn, $update_count_sql);
    oci_bind_by_name($stmt, ":wishlist_id", $wishlist_id);
    oci_execute($stmt);
    oci_free_statement($stmt);
    
    $_SESSION['message'] = "Product removed from wishlist!";
    $_SESSION['messageType'] = "success";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

include_once "../../components/header.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Wishlist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/homestyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;
        }

        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .add-to-cart, .remove-wishlist {
            flex: 1;
            padding: 8px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .add-to-cart {
            background-color: #4CAF50;
        }

        .add-to-cart:hover {
            background-color: #45a049;
        }

        .remove-wishlist {
            background-color: #f44336;
        }

        .remove-wishlist:hover {
            background-color: #d32f2f;
        }

        .shops-btn {
            display: block;
            text-align: center;
            margin-top: 10px;
            text-decoration: none;
            background-color: #f89c54;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .shops-btn:hover {
            background-color: #e78c3f;
        }

        .empty {
            text-align: center;
            grid-column: 1 / -1;
            padding: 50px;
            font-size: 18px;
            color: #666;
        }

        .page-title {
            text-align: center;
            margin: 20px 0;
            color: #333;
            font-size: 32px;
        }

        .wishlist-info {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }

        @media (max-width: 1200px) {
            .grid-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .grid-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .grid-container {
                grid-template-columns: repeat(1, 1fr);
            }
        }

        .product-card {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .heart-icon {
            color: #f44336;
            margin-right: 5px;
        }

        .back-to-products {
            display: block;
            width: 200px;
            margin: 20px auto;
            text-align: center;
            padding: 10px;
            background-color: #333;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-to-products:hover {
            background-color: #555;
        }
        
        /* Custom Toastify style */
        .toast-close {
            background: transparent;
            border: none;
            color: white;
            font-size: 14px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        /* Loading spinner */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <h1 class="page-title">My Wishlist</h1>
    
    <p class="wishlist-info">You have <strong><?= $item_count ?></strong> items in your wishlist</p>
    
    <a href="product_page.php" class="back-to-products"><i class="fas fa-arrow-left"></i> Back to Products</a>

    <section class="recommended-section">
        <div class="grid-container">
            <?php
            // Get products in user's wishlist
            $select_products_sql = "
                SELECT p.* FROM product p
                JOIN wishlist_product wp ON p.product_id = wp.product_id
                WHERE wp.wishlist_id = :wishlist_id
                ORDER BY wp.added_date DESC
            ";
            $stmt = oci_parse($conn, $select_products_sql);
            oci_bind_by_name($stmt, ":wishlist_id", $wishlist_id);
            oci_execute($stmt);

            $products_found = false;
            while ($fetch_product = oci_fetch_array($stmt, OCI_ASSOC)) {
                $products_found = true;
                $description = $fetch_product['DESCRIPTION'];
                if ($description instanceof OCILob) {
                    $description = $description->load();
                }
            ?>
                <div class="card product-card" data-product-id="<?= $fetch_product['PRODUCT_ID']; ?>">
                    <img src="../../trader/php/uploaded_files/<?= $fetch_product['PRODUCT_IMAGE']; ?>" alt="<?= htmlspecialchars($fetch_product['PRODUCT_NAME']); ?>">
                    <div class="card-content product-info">
                        <h3><?= $fetch_product['PRODUCT_NAME']; ?></h3>
                        <p class="description"><?= htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : ''); ?></p>
                        <p class="price">$ <?= $fetch_product['PRICE']; ?></p>

                        <input type="hidden" name="product_id" value="<?= $fetch_product['PRODUCT_ID']; ?>">

                        <label for="qty">Qty:</label>
                        <div class="qty-selector">
                            <button type="button" class="qty-btn minus">-</button>
                            <span class="qty-display" data-value="1">1</span>
                            <input type="hidden" name="qty" value="1" class="qty-hidden">
                            <button type="button" class="qty-btn plus">+</button>
                        </div>

                        <div class="button-container">
                            <button type="button" class="add-to-cart">
                                <i class="fas fa-shopping-basket"></i> Add to Cart
                            </button>
                            <form action="" method="POST" class="remove-form">
                                <input type="hidden" name="product_id" value="<?= $fetch_product['PRODUCT_ID']; ?>">
                                <button type="submit" name="remove_from_wishlist" class="remove-wishlist">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </form>
                        </div>
                        <a href="product_detail.php?id=<?= $fetch_product['PRODUCT_ID']; ?>" class="shops-btn">View Details</a>
                    </div>
                </div>
            <?php }
            oci_free_statement($stmt); ?>

            <?php if (!$products_found): ?>
                <div class="empty">
                    <p>Your wishlist is empty!</p>
                    <p>Browse products and add items to your wishlist.</p>
                    <a href="product_page.php" class="shops-btn" style="display: inline-block; margin-top: 20px;">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Function to show toast notification
        function showToast(message, type) {
            const toast = Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: type === 'success' ? '#4CAF50' : '#f44336',
                close: true,
                stopOnFocus: true
            });
            toast.showToast();
        }
        
        // Show toast notification if message exists in session
        <?php if (isset($_SESSION['message'])): ?>
            showToast("<?= $_SESSION['message'] ?>", "<?= $_SESSION['messageType'] ?>");
            <?php 
            // Clear the message after displaying
            unset($_SESSION['message']);
            unset($_SESSION['messageType']);
            ?>
        <?php endif; ?>

        // Quantity selector functionality
        document.querySelectorAll('.product-card').forEach(card => {
            const minus = card.querySelector('.minus');
            const plus = card.querySelector('.plus');
            const qtyDisplay = card.querySelector('.qty-display');
            const qtyHidden = card.querySelector('.qty-hidden');
            const addToCartBtn = card.querySelector('.add-to-cart');

            let qty = parseInt(qtyDisplay.dataset.value) || 1;

            const updateQty = (newQty) => {
                qty = newQty;
                qtyDisplay.textContent = qty;
                qtyDisplay.dataset.value = qty;
                qtyHidden.value = qty;
            };

            minus.addEventListener('click', () => {
                if (qty > 1) updateQty(qty - 1);
            });

            plus.addEventListener('click', () => {
                updateQty(qty + 1);
            });
            
            // Add to Cart functionality
            addToCartBtn.addEventListener('click', function() {
                const productId = card.getAttribute('data-product-id');
                const quantity = qtyHidden.value;
                
                // Disable button and show loading
                addToCartBtn.disabled = true;
                addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                
                // Create form data
                const formData = new FormData();
                formData.append('add_to_cart', 'true');
                formData.append('product_id', productId);
                formData.append('qty', quantity);
                
                // Send AJAX request
                fetch('add_to_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.status);
                    
                    // Update cart count if successful
                    if (data.status === 'success') {
                        updateCartCount();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast("Error adding to cart!", "error");
                })
                .finally(() => {
                    // Re-enable button
                    addToCartBtn.disabled = false;
                    addToCartBtn.innerHTML = '<i class="fas fa-shopping-basket"></i> Add to Cart';
                });
            });
        });
        
        // Function to update cart count in header
        function updateCartCount() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartCountElement = document.querySelector('.cart-count');
                    if (data.count > 0) {
                        if (!cartCountElement) {
                            // Create the count element if it doesn't exist
                            const cartIcon = document.querySelector('.cart-icon');
                            const countElement = document.createElement('span');
                            countElement.className = 'cart-count';
                            countElement.textContent = data.count;
                            cartIcon.appendChild(countElement);
                        } else {
                            cartCountElement.textContent = data.count;
                        }
                    } else if (cartCountElement) {
                        // Remove the count element if cart is empty
                        cartCountElement.remove();
                    }
                })
                .catch(error => console.error('Error fetching cart count:', error));
        }
    </script>

    <!-- FOOTER -->
    <?php include_once "../../components/footer.php"; ?>
</body>
</html>