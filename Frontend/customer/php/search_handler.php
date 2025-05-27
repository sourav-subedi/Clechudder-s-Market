<?php
require "../../../Backend/connect.php";
include_once "../../components/header.php";

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed.");
}

// Handle guest session
if (!isset($_COOKIE['session_id'])) {
    $session_id = uniqid();
    setcookie('session_id', $session_id, time() + (60 * 60 * 24 * 30), "/");
} else {
    $session_id = $_COOKIE['session_id'];
}

// Get the search input
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$products = [];
$categories = ['butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen'];

if (!empty($searchQuery)) {
    $searchTerm = '%' . strtolower($searchQuery) . '%';

    $sql = "SELECT p.*, s.shop_name, s.shop_id 
            FROM product p
            JOIN shops s ON p.shop_id = s.shop_id
            WHERE LOWER(p.product_name) LIKE :query 
               OR LOWER(p.description) LIKE :query 
               OR LOWER(p.product_category_name) LIKE :query
            ORDER BY p.product_name";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":query", $searchTerm);

    if (oci_execute($stmt)) {
        while ($row = oci_fetch_assoc($stmt)) {
            $products[] = $row;
        }
    }

    oci_free_statement($stmt);
}
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Search Results - Cleckhudders Market</title>
    <link rel="stylesheet" href="../css/homestyle.css">
    <style>
        .search-results-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .product-card {
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            overflow: hidden;
            transition: 0.3s ease;
            text-decoration: none;
            background: #fff;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .product-shop,
        .product-price {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .product-price {
            color: #00C12B;
            font-weight: bold;
        }

        .no-results {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-top: 50px;
        }

        .search-suggestions {
            margin-top: 30px;
        }

        .suggestion-title {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .suggestion-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .suggestion-item {
            background: #f0f0f0;
            padding: 6px 16px;
            border-radius: 20px;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background: #e1e1e1;
        }
    </style>
</head>

<body>
    <div class="search-results-container">
        <h1>Search Results</h1>
        <?php if (!empty($searchQuery)): ?>
            <p>Found <?php echo count($products); ?> result(s) for "<?php echo htmlspecialchars($searchQuery); ?>"</p>
        <?php else: ?>
            <p>Please enter a search term.</p>
        <?php endif; ?>

        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="/Implementation_And_Coding/Frontend/customer/php/product_detail.php?product_id=<?php echo $product['PRODUCT_ID']; ?>">
                            <img src="../../trader/php/uploaded_files/<?php echo $product['PRODUCT_IMAGE']; ?>" alt="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>" class="product-image">
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></h3>
                                <p class="product-shop"><?php echo htmlspecialchars($product['SHOP_NAME']); ?></p>
                                <p class="product-price">$<?php echo number_format($product['PRICE'], 2); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($searchQuery)): ?>
            <div class="no-results">
                <p>No products found matching your search.</p>
                <div class="search-suggestions">
                    <p class="suggestion-title">Try searching for:</p>
                    <div class="suggestion-list">
                        <?php foreach ($categories as $category): ?>
                            <div class="suggestion-item" onclick="window.location.href='search_handler.php?search=<?php echo urlencode($category); ?>'">
                                <?php echo ucfirst($category); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once "../../components/footer.php"; ?>
</body>

</html>