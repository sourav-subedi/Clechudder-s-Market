<?php
require_once "../../../Backend/connect.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'] ?? null;

// Default values
$sort_option = $_GET['sort'] ?? 'default';
$shop_category = $_GET['shop'] ?? 'default';
$min_price = $_GET['min'] ?? '';
$max_price = $_GET['max'] ?? '';

// Base query
$query = "SELECT p.*, s.shop_name, s.shop_category 
          FROM product p 
          INNER JOIN shops s ON p.shop_id = s.shop_id 
          WHERE p.product_status = 'In Stock'";

// Add shop category filter
if ($shop_category !== 'default') {
    $query .= " AND s.shop_category = :shop_category";
}

// Add price range filter
if ($min_price !== '' && is_numeric($min_price)) {
    $query .= " AND p.price >= :min_price";
}

if ($max_price !== '' && is_numeric($max_price)) {
    $query .= " AND p.price <= :max_price";
}

// Add sorting
switch ($sort_option) {
    case 'low_to_high':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'high_to_low':
        $query .= " ORDER BY p.price DESC";
        break;
    default:
        $query .= " ORDER BY p.add_date DESC";
        break;
}

// Prepare and execute query
$stmt = oci_parse($conn, $query);

// Bind parameters if they exist
if ($shop_category !== 'default') {
    oci_bind_by_name($stmt, ":shop_category", $shop_category);
}

if ($min_price !== '' && is_numeric($min_price)) {
    oci_bind_by_name($stmt, ":min_price", $min_price);
}

if ($max_price !== '' && is_numeric($max_price)) {
    oci_bind_by_name($stmt, ":max_price", $max_price);
}

oci_execute($stmt);

// Check if products were found
$products_found = false;
?>

<div class="grid-container">
    <?php
    while ($product = oci_fetch_assoc($stmt)) {
        $products_found = true;
        $description = $product['DESCRIPTION'];
        if ($description instanceof OCILob) {
            $description = $description->load();
        }
        
        // Truncate description if too long
        $short_description = strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
        ?>
        <div class="card product-card">
            <div class="shop-category-tag"><?= ucfirst($product['SHOP_CATEGORY']); ?></div>
            <img src="../../trader/php/uploaded_files/<?= $product['PRODUCT_IMAGE']; ?>" alt="<?= htmlspecialchars($product['PRODUCT_NAME']); ?>">
            <div class="card-content product-info">
                <h3><?= $product['PRODUCT_NAME']; ?></h3>
                <p class="description"><?= htmlspecialchars($short_description); ?></p>
                <p class="shop-name">Shop: <?= $product['SHOP_NAME']; ?></p>
                <p class="price">$ <?= $product['PRICE']; ?></p>

                <input type="hidden" name="product_id" value="<?= $product['PRODUCT_ID']; ?>" class="product-id">

                <label for="qty">Qty:</label>
                <div class="qty-selector">
                    <button type="button" class="qty-btn minus">-</button>
                    <span class="qty-display" data-value="1">1</span>
                    <input type="hidden" name="qty" value="1" class="qty-hidden">
                    <button type="button" class="qty-btn plus">+</button>
                </div>

                <button type="button" class="add-to-cart">
                    <i class="fas fa-shopping-basket"></i> Add to Cart
                </button>
                <a href="product_detail.php?id=<?= $product['PRODUCT_ID']; ?>" class="shops-btn">View Details</a>
            </div>
        </div>
    <?php } 
    
    oci_free_statement($stmt);
    oci_close($conn);
    
    if (!$products_found): ?>
        <div class="empty">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No products found matching your criteria!</p>
            <a href="product_page.php" class="reset-filters">Reset Filters</a>
        </div>
    <?php endif; ?>
</div>