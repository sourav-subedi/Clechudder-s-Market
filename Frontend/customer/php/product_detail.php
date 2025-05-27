<?php
require "../../../Backend/connect.php";

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: product_list.php");
    exit();
}

// Get user ID from session if available
$user_id = $_SESSION['user_id'] ?? null;

// Fetch product details
$product_sql = "SELECT p.*, s.shop_name, s.shop_category 
                FROM product p 
                JOIN shops s ON p.shop_id = s.shop_id 
                WHERE p.product_id = :product_id";
$stmt = oci_parse($conn, $product_sql);
oci_bind_by_name($stmt, ":product_id", $product_id);
oci_execute($stmt);
$product = oci_fetch_array($stmt, OCI_ASSOC);
oci_free_statement($stmt);

if (!$product) {
    // Product not found
    header("Location: product_list.php");
    exit();
}

// Get product description
$description = $product['DESCRIPTION'];
if ($description instanceof OCILob) {
    $description = $description->load();
}

// Get product category for related products
$product_category = $product['PRODUCT_CATEGORY_NAME'];

// Fetch related products (3 random products from the same category, excluding current product)
$related_products_sql = "SELECT * FROM (
                            SELECT p.*, s.shop_name 
                            FROM product p
                            JOIN shops s ON p.shop_id = s.shop_id
                            WHERE p.product_category_name = :category 
                            AND p.product_id != :product_id
                            ORDER BY DBMS_RANDOM.VALUE
                        ) WHERE ROWNUM <= 3";
$stmt = oci_parse($conn, $related_products_sql);
oci_bind_by_name($stmt, ":category", $product_category);
oci_bind_by_name($stmt, ":product_id", $product_id);
oci_execute($stmt);

$related_products = [];
while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
    $related_description = $row['DESCRIPTION'];
    if ($related_description instanceof OCILob) {
        $related_description = $related_description->load();
    }
    $row['DESCRIPTION_TEXT'] = $related_description;
    $related_products[] = $row;
}
oci_free_statement($stmt);

// Check if there's a discount for this product
$discount_sql = "SELECT discount_percentage FROM discount WHERE product_id = :product_id";
$stmt = oci_parse($conn, $discount_sql);
oci_bind_by_name($stmt, ":product_id", $product_id);
oci_execute($stmt);
$discount = oci_fetch_array($stmt, OCI_ASSOC);
oci_free_statement($stmt);

$discount_percentage = 0;
$original_price = $product['PRICE'];
$current_price = $original_price;

if ($discount) {
    $discount_percentage = $discount['DISCOUNT_PERCENTAGE'];
    $current_price = $original_price - ($original_price * ($discount_percentage / 100));
}

// Fetch reviews for this product
$reviews_sql = "SELECT r.*, u.full_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                WHERE r.product_id = :product_id 
                ORDER BY r.review_date DESC";
$stmt = oci_parse($conn, $reviews_sql);
oci_bind_by_name($stmt, ":product_id", $product_id);
oci_execute($stmt);

$reviews = [];
$total_rating = 0;
$review_count = 0;

while ($review = oci_fetch_array($stmt, OCI_ASSOC)) {
    $review_text = $review['REVIEW'];
    if ($review_text instanceof OCILob) {
        $review_text = $review_text->load();
    }
    $review['REVIEW_TEXT'] = $review_text;
    $reviews[] = $review;
    $total_rating += $review['REVIEW_RATING'];
    $review_count++;
}
oci_free_statement($stmt);

$average_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

// Handle Add to Cart functionality
$message = "";
$messageType = "";

if (isset($_POST['add_to_cart'])) {
    if (!$user_id) {
        $message = "Please login to add products to cart!";
        $messageType = "error";
    } else {
        $qty = (int) $_POST['qty'];

        // Check current total quantity in cart
        $total_qty_sql = "SELECT NVL(SUM(quantity), 0) AS total_qty FROM product_cart pc 
                          JOIN cart c ON pc.cart_id = c.cart_id 
                          WHERE c.user_id = :user_id";
        $stmt = oci_parse($conn, $total_qty_sql);
        oci_bind_by_name($stmt, ":user_id", $user_id);
        oci_execute($stmt);
        $total_result = oci_fetch_array($stmt, OCI_ASSOC);
        oci_free_statement($stmt);

        $total_cart_qty = (int) $total_result['TOTAL_QTY'];

        // Check if cart limit would be exceeded
        if ($total_cart_qty + $qty > 10) {
            $message = "Cart limit exceeded! You cannot add more than 10 items in total. Please remove items from cart first.";
            $messageType = "error";
        } else if ($qty < $product['MIN_ORDER'] || $qty > $product['MAX_ORDER']) {
            $message = "Quantity must be between " . $product['MIN_ORDER'] . " and " . $product['MAX_ORDER'];
            $messageType = "error";
        } else if ($qty > $product['STOCK']) {
            $message = "Sorry, only " . $product['STOCK'] . " items are available!";
            $messageType = "error";
        } else {
            // Check if user has a cart
            $sql = "SELECT * FROM cart WHERE user_id = :user_id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":user_id", $user_id);
            oci_execute($stmt);
            $cart = oci_fetch_array($stmt, OCI_ASSOC);
            oci_free_statement($stmt);

            if (!$cart) {
                // Create a new cart
                $insert_cart_sql = "INSERT INTO cart (cart_id, user_id, add_date) VALUES (cart_seq.NEXTVAL, :user_id, SYSDATE) RETURNING cart_id INTO :cart_id";
                $stmt = oci_parse($conn, $insert_cart_sql);
                oci_bind_by_name($stmt, ":user_id", $user_id);
                oci_bind_by_name($stmt, ":cart_id", $cart_id, 20);
                oci_execute($stmt);
                oci_free_statement($stmt);
            } else {
                $cart_id = $cart['CART_ID'];
            }

            // Check if product already in cart
            $check_product_sql = "SELECT * FROM product_cart WHERE cart_id = :cart_id AND product_id = :product_id";
            $stmt = oci_parse($conn, $check_product_sql);
            oci_bind_by_name($stmt, ":cart_id", $cart_id);
            oci_bind_by_name($stmt, ":product_id", $product_id);
            oci_execute($stmt);
            $product_exists = oci_fetch_array($stmt, OCI_ASSOC);
            oci_free_statement($stmt);

            if ($product_exists) {
                // Update quantity if already in cart
                $update_qty_sql = "UPDATE product_cart SET quantity = :qty WHERE cart_id = :cart_id AND product_id = :product_id";
                $stmt = oci_parse($conn, $update_qty_sql);
                oci_bind_by_name($stmt, ":qty", $qty);
                oci_bind_by_name($stmt, ":cart_id", $cart_id);
                oci_bind_by_name($stmt, ":product_id", $product_id);
                oci_execute($stmt);
                oci_free_statement($stmt);
                
                $message = "Cart updated successfully!";
                $messageType = "success";
            } else {
                // Add new product to cart
                $insert_product_sql = "INSERT INTO product_cart (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :qty)";
                $stmt = oci_parse($conn, $insert_product_sql);
                oci_bind_by_name($stmt, ":cart_id", $cart_id);
                oci_bind_by_name($stmt, ":product_id", $product_id);
                oci_bind_by_name($stmt, ":qty", $qty);
                oci_execute($stmt);
                oci_free_statement($stmt);

                $message = "Product added to cart successfully!";
                $messageType = "success";
            }
        }
    }
}

// Handle Add to Wishlist functionality
if (isset($_POST['add_to_wishlist'])) {
    if (!$user_id) {
        $message = "Please login to add products to wishlist!";
        $messageType = "error";
    } else {
        // Check if user has a wishlist
        $sql = "SELECT * FROM wishlist WHERE user_id = :user_id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":user_id", $user_id);
        oci_execute($stmt);
        $wishlist = oci_fetch_array($stmt, OCI_ASSOC);
        oci_free_statement($stmt);

        if (!$wishlist) {
            // Create a new wishlist
            $insert_wishlist_sql = "INSERT INTO wishlist (wishlist_id, no_of_items, user_id) VALUES (wishlist_seq.NEXTVAL, 1, :user_id) RETURNING wishlist_id INTO :wishlist_id";
            $stmt = oci_parse($conn, $insert_wishlist_sql);
            oci_bind_by_name($stmt, ":user_id", $user_id);
            oci_bind_by_name($stmt, ":wishlist_id", $wishlist_id, 20);
            oci_execute($stmt);
            oci_free_statement($stmt);
        } else {
            $wishlist_id = $wishlist['WISHLIST_ID'];
            
            // Update count
            $update_count_sql = "UPDATE wishlist SET no_of_items = no_of_items + 1 WHERE wishlist_id = :wishlist_id";
            $stmt = oci_parse($conn, $update_count_sql);
            oci_bind_by_name($stmt, ":wishlist_id", $wishlist_id);
            oci_execute($stmt);
            oci_free_statement($stmt);
        }

        // Check if product already in wishlist
        $check_product_sql = "SELECT * FROM wishlist_product WHERE wishlist_id = :wishlist_id AND product_id = :product_id";
        $stmt = oci_parse($conn, $check_product_sql);
        oci_bind_by_name($stmt, ":wishlist_id", $wishlist_id);
        oci_bind_by_name($stmt, ":product_id", $product_id);
        oci_execute($stmt);
        $product_exists = oci_fetch_array($stmt, OCI_ASSOC);
        oci_free_statement($stmt);

        if ($product_exists) {
            $message = "Product already in wishlist!";
            $messageType = "info";
        } else {
            // Add product to wishlist
            $insert_product_sql = "INSERT INTO wishlist_product (wishlist_id, product_id, added_date) VALUES (:wishlist_id, :product_id, SYSDATE)";
            $stmt = oci_parse($conn, $insert_product_sql);
            oci_bind_by_name($stmt, ":wishlist_id", $wishlist_id);
            oci_bind_by_name($stmt, ":product_id", $product_id);
            oci_execute($stmt);
            oci_free_statement($stmt);

            $message = "Product added to wishlist successfully!";
            $messageType = "success";
        }
    }
}

// Close the database connection
oci_close($conn);
?>

<?php include_once "../../components/header.php"; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['PRODUCT_NAME']) ?> - <?= htmlspecialchars($product['SHOP_NAME']) ?></title>
    <link rel="stylesheet" href="../css/product_detail.css">
    <link rel="stylesheet" href="../css/homestyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .review-section {
            margin-top: 40px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .review-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-author {
            font-weight: bold;
        }
        
        .review-date {
            color: #777;
            font-size: 0.9em;
        }
        
        .product-metadata {
            margin: 15px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
        
        .metadata-item {
            margin-bottom: 8px;
        }
        
        .metadata-label {
            font-weight: bold;
            margin-right: 10px;
        }
        
        .shop-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: 8px;
        }
        
        .shop-name {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .shop-category {
            display: inline-block;
            padding: 3px 8px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        /* Related Products Styles */
        .related-products {
            margin: 40px 0;
        }
        
        .related-products h2 {
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.8rem;
            color: #333;
        }
        
        .related-products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .related-product-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: white;
        }
        
        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 2;
        }
        
        .like-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 2;
        }
        
        .like-btn i {
            color: #ff6b6b;
            font-size: 1.2rem;
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .related-product-card .product-info {
            padding: 15px;
        }
        
        .related-product-card .product-title {
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #333;
        }
        
        .related-product-card .product-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
        }
        
        .related-product-card .product-price {
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .related-product-card .product-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .view-details, .add-to-cart {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        
        .view-details {
            background-color: transparent;
            border: 1px solid #f89c54;
            color: #f89c54;
        }
        
        .view-details:hover {
            background-color: #f89c54;
            color: white;
        }
        
        .add-to-cart {
            background-color: #f89c54;
            color: white;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .add-to-cart:hover {
            background-color: #e78c3f;
        }
        
        .add-to-cart i {
            font-size: 0.9rem;
        }
        
        @media (max-width: 900px) {
            .related-products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .related-products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Product Detail Section -->
    <section class="product-detail-section">
        <div class="container">
            <div class="breadcrumb">
                <a href="../index.php">Home</a> &gt; 
                <a href="product_list.php">Products</a> &gt; 
                <span><?= htmlspecialchars($product['PRODUCT_NAME']) ?></span>
            </div>
            
            <div class="product-detail-container">
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="main-image">
                        <img src="../../trader/php/uploaded_files/<?= $product['PRODUCT_IMAGE']; ?>" alt="<?= htmlspecialchars($product['PRODUCT_NAME']); ?>" id="mainProductImage">
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="product-info">
                    <h1 class="product-title"><?= htmlspecialchars($product['PRODUCT_NAME']) ?></h1>
                    
                    <div class="shop-info">
                        <span class="shop-name"><?= htmlspecialchars($product['SHOP_NAME']) ?></span>
                        <span class="shop-category"><?= ucfirst(htmlspecialchars($product['SHOP_CATEGORY'])) ?></span>
                    </div>
                    
                    <div class="product-meta">
                        <div class="product-rating">
                            <div class="stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= floor($average_rating)): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif($i - 0.5 <= $average_rating): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="review-count">(<?= $review_count ?> reviews)</span>
                        </div>
                        <span class="product-availability">
                            <?php if($product['STOCK'] > 0): ?>
                                In Stock (<?= $product['STOCK'] ?> available)
                            <?php else: ?>
                                Out of Stock
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="product-price">
                        <?php if($discount_percentage > 0): ?>
                            <span class="current-price">$ <?= number_format($current_price, 2) ?></span>
                            <span class="original-price">$ <?= number_format($original_price, 2) ?></span>
                            <span class="discount-badge">Save <?= $discount_percentage ?>%</span>
                        <?php else: ?>
                            <span class="current-price">$ <?= number_format($current_price, 2) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <p><?= nl2br(htmlspecialchars($description)) ?></p>
                    </div>
                    
                    <div class="product-metadata">
                        <div class="metadata-item">
                            <span class="metadata-label">Minimum Order:</span> <?= $product['MIN_ORDER'] ?>
                        </div>
                        <div class="metadata-item">
                            <span class="metadata-label">Maximum Order:</span> <?= $product['MAX_ORDER'] ?>
                        </div>
                        <div class="metadata-item">
                            <span class="metadata-label">Added On:</span> <?= date('M d, Y', strtotime($product['ADD_DATE'])) ?>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="product-actions">
                            <div class="quantity-selector">
                                <button type="button" class="quantity-btn minus"><i class="fas fa-minus"></i></button>
                                <input type="number" name="qty" class="quantity-input" value="<?= $product['MIN_ORDER'] ?>" 
                                       min="<?= $product['MIN_ORDER'] ?>" max="<?= min($product['MAX_ORDER'], $product['STOCK']) ?>">
                                <button type="button" class="quantity-btn plus"><i class="fas fa-plus"></i></button>
                            </div>
                            
                            <button type="submit" name="add_to_cart" class="add-to-cart-btn" <?= $product['STOCK'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-basket"></i> Add to Cart
                            </button>
                            
                            <button type="submit" name="add_to_wishlist" class="wishlist-btn">
                                <i class="far fa-heart"></i> Add to Wishlist
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Related Products -->
            <div class="related-products">
                <h2>You May Also Like</h2>
                <div class="related-products-grid">
                    <?php if (count($related_products) > 0): ?>
                        <?php foreach ($related_products as $related): ?>
                            <div class="related-product-card">
                                <?php if (rand(1, 5) === 1): // Random "Chef's Pick" badge for some products ?>
                                    <span class="product-badge">Chef's Pick</span>
                                <?php endif; ?>
                                
                                <form action="" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $related['PRODUCT_ID']; ?>">
                                    <input type="hidden" name="qty" value="1">
                                    
                                    <button type="submit" name="add_to_wishlist" class="like-btn">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    
                                    <img src="../../trader/php/uploaded_files/<?= $related['PRODUCT_IMAGE']; ?>" 
                                         alt="<?= htmlspecialchars($related['PRODUCT_NAME']); ?>" 
                                         class="product-image">
                                    
                                    <div class="product-info">
                                        <h3 class="product-title"><?= htmlspecialchars($related['PRODUCT_NAME']); ?></h3>
                                        <p class="product-description">
                                            <?= htmlspecialchars(substr($related['DESCRIPTION_TEXT'], 0, 60)) . (strlen($related['DESCRIPTION_TEXT']) > 60 ? '...' : ''); ?>
                                        </p>
                                        <p class="product-price">$ <?= number_format($related['PRICE'], 2); ?></p>
                                        <div class="product-actions">
                                            <a href="product_detail.php?id=<?= $related['PRODUCT_ID']; ?>">
                                                <button type="button" class="view-details">View Details</button>
                                            </a>
                                            <button type="submit" name="add_to_cart" class="add-to-cart">
                                                <i class="fas fa-shopping-basket"></i>Add To Cart
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No related products found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Review Section -->
            <div class="review-section">
                <h2>Customer Reviews</h2>
                
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="review-author"><?= htmlspecialchars($review['FULL_NAME']) ?></span>
                                <span class="review-date"><?= date('M d, Y', strtotime($review['REVIEW_DATE'])) ?></span>
                            </div>
                            <div class="review-rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $review['REVIEW_RATING']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="review-content">
                                <p><?= nl2br(htmlspecialchars($review['REVIEW_TEXT'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No reviews yet. Be the first to review this product!</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Include Footer -->
    <?php include_once "../../components/footer.php"; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qtyInput = document.querySelector('.quantity-input');
            const minusBtn = document.querySelector('.minus');
            const plusBtn = document.querySelector('.plus');
            
            // Min and max values from PHP
            const minOrder = <?= $product['MIN_ORDER'] ?>;
            const maxOrder = <?= min($product['MAX_ORDER'], $product['STOCK']) ?>;
            
            minusBtn.addEventListener('click', function() {
                const currentValue = parseInt(qtyInput.value);
                if (currentValue > minOrder) {
                    qtyInput.value = currentValue - 1;
                }
            });
            
            plusBtn.addEventListener('click', function() {
                const currentValue = parseInt(qtyInput.value);
                if (currentValue < maxOrder) {
                    qtyInput.value = currentValue + 1;
                }
            });
            
            // Validate input to stay within min-max range
            qtyInput.addEventListener('change', function() {
                const currentValue = parseInt(qtyInput.value);
                if (currentValue < minOrder) {
                    qtyInput.value = minOrder;
                } else if (currentValue > maxOrder) {
                    qtyInput.value = maxOrder;
                }
            });
            
            // Only show toast notification if there's an actual message from form submission
            <?php if (!empty($message) && isset($messageType)): ?>
                Toastify({
                    text: "<?= $message ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "<?= $messageType === 'success' ? '#4CAF50' : ($messageType === 'info' ? '#2196F3' : '#f44336') ?>",
                    close: true,
                }).showToast();
            <?php endif; ?>
        });
    </script>
</body>
</html>