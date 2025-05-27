<?php
session_start();
require "../../../Backend/connect.php";
include_once "../../components/header.php";

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;

// Get the category from the URL parameter
$category = isset($_GET['category']) ? strtolower($_GET['category']) : null;
$category_name = ucfirst($category);

if (isset($_POST['add_to_cart'])) {
    $product_id = (int) $_POST['product_id'];
    $qty = (int) $_POST['qty'];

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
        oci_execute($stmt);
        oci_free_statement($stmt);
    } else {
        $cart_id = $cart['CART_ID'];
    }

    $check_product_sql = "SELECT * FROM product_cart WHERE cart_id = :cart_id AND product_id = :product_id";
    $stmt = oci_parse($conn, $check_product_sql);
    oci_bind_by_name($stmt, ":cart_id", $cart_id);
    oci_bind_by_name($stmt, ":product_id", $product_id);
    oci_execute($stmt);
    $product_exists = oci_fetch_array($stmt, OCI_ASSOC);
    oci_free_statement($stmt);

   if ($product_exists) {
    $existing_qty = $product_exists['QUANTITY'];
    if ($existing_qty + $qty > 10) {
        $_SESSION['message'] = "Cart limit exceeded! You can't add more than 10 of this product.";
        $_SESSION['messageType'] = "error";
    } else {
        $new_qty = $existing_qty + $qty;
        $update_sql = "UPDATE product_cart SET quantity = :new_qty WHERE cart_id = :cart_id AND product_id = :product_id";
        $stmt = oci_parse($conn, $update_sql);
        oci_bind_by_name($stmt, ":new_qty", $new_qty);
        oci_bind_by_name($stmt, ":cart_id", $cart_id);
        oci_bind_by_name($stmt, ":product_id", $product_id);
        oci_execute($stmt);
        oci_free_statement($stmt);

        $_SESSION['message'] = "Product quantity updated in cart!";
        $_SESSION['messageType'] = "success";
    }
} else {
    if ($qty > 10) {
        $_SESSION['message'] = "Cart limit exceeded! You can't add more than 10 of this product.";
        $_SESSION['messageType'] = "error";
    } else {
        $insert_product_sql = "INSERT INTO product_cart (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :qty)";
        $stmt = oci_parse($conn, $insert_product_sql);
        oci_bind_by_name($stmt, ":cart_id", $cart_id);
        oci_bind_by_name($stmt, ":product_id", $product_id);
        oci_bind_by_name($stmt, ":qty", $qty);
        oci_execute($stmt);
        oci_free_statement($stmt);

        $_SESSION['message'] = "Product added to cart successfully!";
        $_SESSION['messageType'] = "success";
    }
}

    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?category=" . urlencode($category));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $category_name ?> Products</title>
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

        .category-title {
            grid-column: 1 / -1;
            text-align: center;
            margin: 20px 0;
            font-size: 2rem;
            color: #333;
        }

        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 20px;
            background-color: #f89c54;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #e78c3f;
        }

        @media (max-width: 1200px) {
            .grid-container {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 900px) {
            .grid-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 600px) {
            .grid-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 400px) {
            .grid-container {
                grid-template-columns: 1fr;
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
    <a href="Product_Catagory.php" class="back-btn">Back to Categories</a>
    
    <section class="recommended-section">
        <div class="grid-container">
            <h1 class="category-title"><?= $category_name ?> Products</h1>
            
            <?php
            // Correct SQL query to filter products by shop category
            $select_products_sql = "SELECT p.* 
                                  FROM product p
                                  JOIN shops s ON p.shop_id = s.shop_id
                                  WHERE LOWER(s.shop_category) = :category";
            
            $stmt = oci_parse($conn, $select_products_sql);
            oci_bind_by_name($stmt, ":category", $category);
            oci_execute($stmt);

            $products_found = false;
            while ($fetch_product = oci_fetch_array($stmt, OCI_ASSOC)) {
                $products_found = true;
                $description = $fetch_product['DESCRIPTION'];
                if ($description instanceof OCILob) {
                    $description = $description->load();
                }
            ?>
                <form action="" method="POST">
                    <div class="card product-card">
                        <img src="../../trader/php/uploaded_files/<?= $fetch_product['PRODUCT_IMAGE']; ?>" alt="<?= htmlspecialchars($fetch_product['PRODUCT_NAME']); ?>">
                        <div class="card-content product-info">
                            <h3><?= $fetch_product['PRODUCT_NAME']; ?></h3>
                            <p class="description"><?= htmlspecialchars($description) ?></p>
                            <p class="price">$ <?= $fetch_product['PRICE']; ?></p>

                            <input type="hidden" name="product_id" value="<?= $fetch_product['PRODUCT_ID']; ?>">

                            <label for="qty">Qty:</label>
                            <div class="qty-selector">
                                <button type="button" class="qty-btn minus">-</button>
                                <span class="qty-display" data-value="1">1</span>
                                <input type="hidden" name="qty" value="1" class="qty-hidden">
                                <button type="button" class="qty-btn plus">+</button>
                            </div>

                            <button type="submit" name="add_to_cart" class="add-to-cart">
                                <i class="fas fa-shopping-basket"></i> Add to Cart
                            </button>
                            <a href="product_detail.php?id=<?= $fetch_product['PRODUCT_ID']; ?>" class="shops-btn">View Details</a>
                        </div>
                    </div>
                </form>
            <?php }
            oci_free_statement($stmt); ?>

            <?php if (!$products_found): ?>
                <p class="empty">No products found in the <?= $category_name ?> category!</p>
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

     <!-- FOOTER -->
  <?php
    include_once "../../components/footer.php";
  ?>

</body>

</html>