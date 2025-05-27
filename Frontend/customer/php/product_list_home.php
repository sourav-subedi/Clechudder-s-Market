<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Products</title>
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

        .add-to-cart {
            width: 100%;
            padding: 8px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
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

        /* Custom toast styles */
        .toastify {
            padding: 12px 20px;
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
        }
    </style>
</head>

<body>

    <section class="recommended-section">
        <div class="grid-container">
            <?php
            require "../../../Backend/connect.php";

            $conn = getDBConnection();
            if (!$conn) {
                die("Database connection failed");
            }

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $user_id = $_SESSION['user_id'] ?? null;

            // Modified query to randomly select 10 products
            $select_products_sql = "SELECT p.PRODUCT_ID, p.PRODUCT_NAME, p.PRICE, p.PRODUCT_IMAGE, 
                DBMS_LOB.SUBSTR(p.DESCRIPTION, 4000, 1) as DESCRIPTION 
                FROM (
                    SELECT * FROM product ORDER BY DBMS_RANDOM.VALUE
                ) p 
                WHERE ROWNUM <= 10";

            $stmt = oci_parse($conn, $select_products_sql);
            oci_execute($stmt);

            $products_found = false;
            while ($fetch_product = oci_fetch_array($stmt, OCI_ASSOC)) {
                $products_found = true;
                $description = isset($fetch_product['DESCRIPTION']) ? $fetch_product['DESCRIPTION'] : '';
                // Truncate description if too long
                $short_description = strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
            ?>
                <div class="card product-card">
                    <img src="../../trader/php/uploaded_files/<?= isset($fetch_product['PRODUCT_IMAGE']) ? htmlspecialchars($fetch_product['PRODUCT_IMAGE']) : 'default_product.jpg'; ?>" 
                         alt="<?= isset($fetch_product['PRODUCT_NAME']) ? htmlspecialchars($fetch_product['PRODUCT_NAME']) : 'Product Image'; ?>">
                    <div class="card-content product-info">
                        <h3><?= isset($fetch_product['PRODUCT_NAME']) ? htmlspecialchars($fetch_product['PRODUCT_NAME']) : 'Unnamed Product'; ?></h3>
                        <p class="description"><?= htmlspecialchars($short_description); ?></p>
                        <p class="price">Rs. <?= isset($fetch_product['PRICE']) ? number_format($fetch_product['PRICE'], 2) : '0.00'; ?></p>

                        <input type="hidden" class="product-id" value="<?= $fetch_product['PRODUCT_ID']; ?>">

                        <label for="qty">Qty:</label>
                        <div class="qty-selector">
                            <button type="button" class="qty-btn minus">-</button>
                            <span class="qty-display" data-value="1">1</span>
                            <input type="hidden" class="qty-hidden" value="1">
                            <button type="button" class="qty-btn plus">+</button>
                        </div>

                        <button type="button" class="add-to-cart">
                            <i class="fas fa-shopping-basket"></i> Add to Cart
                        </button>
                        <a href="product_detail.php?id=<?= $fetch_product['PRODUCT_ID']; ?>" class="shops-btn">View Details</a>
                    </div>
                </div>
            <?php }
            oci_free_statement($stmt); ?>

            <?php if (!$products_found): ?>
                <p class="empty">No products found!</p>
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
                const productId = card.querySelector('.product-id').value;
                const quantity = qtyHidden.value;

                addToCartBtn.disabled = true;
                addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

                const formData = new FormData();
                formData.append('add_to_cart', 'true');
                formData.append('product_id', productId);
                formData.append('qty', quantity);

                fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showToast(data.message, data.status);
                        if (data.status === 'success') {
                            if (typeof updateCartCount === 'function') {
                                updateCartCount();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast("Error adding to cart!", "error");
                    })
                    .finally(() => {
                        addToCartBtn.disabled = false;
                        addToCartBtn.innerHTML = '<i class="fas fa-shopping-basket"></i> Add to Cart';
                    });
            });

        });
    </script>

</body>

</html>