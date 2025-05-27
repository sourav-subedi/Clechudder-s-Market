<?php
session_start();
require "../../../Backend/connect.php";
include_once "sidebar.php";

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$warning_msg = [];
$success_msg = [];
$product = null;

// Get product ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: trader_dashboard.php');
    exit();
}

$product_id = $_GET['id'];

// Fetch existing product data
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get product details
    $product_query = oci_parse($conn, "
        SELECT p.*, d.discount_percentage 
        FROM product p 
        LEFT JOIN discount d ON p.product_id = d.product_id 
        WHERE p.product_id = :product_id AND p.user_id = :user_id
    ");
    oci_bind_by_name($product_query, ":product_id", $product_id);
    oci_bind_by_name($product_query, ":user_id", $user_id);
    oci_execute($product_query);
    
    $product = oci_fetch_assoc($product_query);
    
    if (!$product) {
        $warning_msg[] = 'Product not found or you do not have permission to edit it.';
    } else {
        // Handle CLOB fields - convert OCILob to string
        if (isset($product['DESCRIPTION']) && is_object($product['DESCRIPTION'])) {
            $product['DESCRIPTION'] = $product['DESCRIPTION']->read($product['DESCRIPTION']->size());
        }
    }
    
    // Get user's shops
    $shops = [];
    $shop_query = oci_parse($conn, "SELECT shop_id, shop_name, shop_category FROM shops WHERE user_id = :user_id");
    oci_bind_by_name($shop_query, ":user_id", $user_id);
    oci_execute($shop_query);

    while ($row = oci_fetch_assoc($shop_query)) {
        $shops[] = [
            'shop_id' => $row['SHOP_ID'],
            'shop_name' => $row['SHOP_NAME'],
            'category' => $row['SHOP_CATEGORY']
        ];
    }
} else {
    header('Location: login.php');
    exit();
}

// Handle form submission
if (isset($_POST['update']) && $product) {
    $product_name = filter_var($_POST['product_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $discount = isset($_POST['discount']) && $_POST['discount'] !== '' ? filter_var($_POST['discount'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null;
    $stock = filter_var($_POST['stock'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $min_order = filter_var($_POST['min_order'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $max_order = filter_var($_POST['max_order'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $product_status = filter_var($_POST['product_status'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $shop_id = $_POST['shop_id'];
    
    // Get product category from selected shop
    $product_category_name = '';
    foreach ($shops as $shop) {
        if ($shop['shop_id'] == $shop_id) {
            $product_category_name = ucwords(strtolower($shop['category']));
            break;
        }
    }

    $user_id = $_SESSION['user_id'];
    $current_image = $product['PRODUCT_IMAGE'];
    $new_image = $current_image; // Keep current image by default

    // Handle image upload if new image is provided
    if (!empty($_FILES['product_image']['name'])) {
        $image = $_FILES['product_image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $ext = pathinfo($image, PATHINFO_EXTENSION);
        $rename = uniqid('product_') . '.' . $ext;
        $image_tmp_name = $_FILES['product_image']['tmp_name'];
        $image_size = $_FILES['product_image']['size'];
        $image_folder = 'uploaded_files/' . $rename;

        if ($image_size > 2000000) {
            $warning_msg[] = 'Image size is too large!';
        } else {
            $new_image = $rename;
        }
    }

    if (empty($warning_msg)) {
        // Update product
        $sql = "UPDATE product SET 
                    product_name = :product_name,
                    description = :description,
                    price = :price,
                    stock = :stock,
                    min_order = :min_order,
                    max_order = :max_order,
                    product_image = :product_image,
                    product_status = :product_status,
                    shop_id = :shop_id,
                    product_category_name = :product_category_name
                WHERE product_id = :product_id AND user_id = :user_id";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':product_name', $product_name);
        oci_bind_by_name($stmt, ':description', $description);
        oci_bind_by_name($stmt, ':price', $price);
        oci_bind_by_name($stmt, ':stock', $stock);
        oci_bind_by_name($stmt, ':min_order', $min_order);
        oci_bind_by_name($stmt, ':max_order', $max_order);
        oci_bind_by_name($stmt, ':product_image', $new_image);
        oci_bind_by_name($stmt, ':product_status', $product_status);
        oci_bind_by_name($stmt, ':shop_id', $shop_id);
        oci_bind_by_name($stmt, ':product_category_name', $product_category_name);
        oci_bind_by_name($stmt, ':product_id', $product_id);
        oci_bind_by_name($stmt, ':user_id', $user_id);

        $result = oci_execute($stmt);
        
        if ($result) {
            // Upload new image if provided
            if (!empty($_FILES['product_image']['name']) && $new_image !== $current_image) {
                move_uploaded_file($image_tmp_name, $image_folder);
                
                // Delete old image if it exists and is different
                if ($current_image && file_exists('uploaded_files/' . $current_image)) {
                    unlink('uploaded_files/' . $current_image);
                }
            }

            // Handle discount update
            // First, delete existing discount
            $delete_discount_sql = "DELETE FROM discount WHERE product_id = :product_id";
            $delete_discount_stmt = oci_parse($conn, $delete_discount_sql);
            oci_bind_by_name($delete_discount_stmt, ':product_id', $product_id);
            oci_execute($delete_discount_stmt);
            oci_free_statement($delete_discount_stmt);

            // Insert new discount if provided
            if ($discount !== null && is_numeric($discount) && $discount > 0) {
                $discount_sql = "INSERT INTO discount (discount_percentage, product_id) VALUES (:discount_percentage, :product_id)";
                $discount_stmt = oci_parse($conn, $discount_sql);
                oci_bind_by_name($discount_stmt, ':discount_percentage', $discount);
                oci_bind_by_name($discount_stmt, ':product_id', $product_id);
                oci_execute($discount_stmt);
                oci_free_statement($discount_stmt);
            }

            $success_msg[] = 'Product updated successfully!';
            
            // Refresh product data
            oci_execute($product_query);
            $product = oci_fetch_assoc($product_query);
            
            // Handle CLOB fields after refresh
            if (isset($product['DESCRIPTION']) && is_object($product['DESCRIPTION'])) {
                $product['DESCRIPTION'] = $product['DESCRIPTION']->read($product['DESCRIPTION']->size());
            }
            
        } else {
            $e = oci_error($stmt);
            $warning_msg[] = 'Database error: ' . $e['message'];
        }
        oci_free_statement($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../css/sidebar.css">
    <style>
        /* Main Form Container */
        .form-container {
            max-width: 1000px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-left: 365px;
            padding: 20px;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }

        /* Form Header */
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .form-header h3 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        /* Form Grid Layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        /* Form Group Styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group label span {
            color: #e74c3c;
        }

        /* Input Fields */
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: #fff;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* File Input Customization */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            padding: 12px;
            background: #f9f9f9;
            border: 1px dashed #ddd;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            border-color: #3498db;
            background: #f0f7fd;
        }

        /* Current Image Display */
        .current-image {
            margin-top: 10px;
            text-align: center;
        }

        .current-image img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .current-image p {
            margin: 10px 0 0 0;
            font-size: 14px;
            color: #666;
        }

        /* Submit Button */
        .submit-btn {
            grid-column: span 2;
            background-color: rgb(254, 148, 74);
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 15px;
            text-decoration: none;
            text-align: center;
        }

        .submit-btn:hover {
            background-color: grey;
        }

        /* Secondary Button */
        .secondary-btn {
            grid-column: span 2;
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .secondary-btn:hover {
            background-color: #5a6268;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .submit-btn, .secondary-btn {
                grid-column: span 1;
            }
        }

        /* Status Messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success-msg {
            background-color: #d4edda;
            color: #155724;
        }

        .warning-msg {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-header">
            <h3>Edit Product Information</h3>
            <p>Update your product details</p>
        </div>

        <?php if (!empty($warning_msg)): ?>
            <div class="message warning-msg">
                <?php echo $warning_msg[0]; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="message success-msg">
                <?php echo $success_msg[0]; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div>
                    <div class="form-group">
                        <label>Product Name <span>*</span></label>
                        <input type="text" name="product_name" class="form-control" required maxlength="50" 
                               value="<?= htmlspecialchars($product['PRODUCT_NAME']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Description <span>*</span></label>
                        <textarea name="description" class="form-control" required><?= htmlspecialchars($product['DESCRIPTION']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Price <span>*</span></label>
                        <input type="number" name="price" class="form-control" required min="0" step="0.01" 
                               value="<?= htmlspecialchars($product['PRICE']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Discount (%)</label>
                        <input type="number" name="discount" class="form-control" min="0" max="100" step="0.01"
                               value="<?= isset($product['DISCOUNT_PERCENTAGE']) ? htmlspecialchars($product['DISCOUNT_PERCENTAGE']) : '' ?>">
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>Stock Quantity <span>*</span></label>
                        <input type="number" name="stock" class="form-control" required min="0" 
                               value="<?= htmlspecialchars($product['STOCK']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Minimum Order <span>*</span></label>
                        <input type="number" name="min_order" class="form-control" required min="1" 
                               value="<?= htmlspecialchars($product['MIN_ORDER']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Maximum Order <span>*</span></label>
                        <input type="number" name="max_order" class="form-control" required min="1" 
                               value="<?= htmlspecialchars($product['MAX_ORDER']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Product Status <span>*</span></label>
                        <select name="product_status" class="form-control" required>
                            <option value="In Stock" <?= $product['PRODUCT_STATUS'] == 'In Stock' ? 'selected' : '' ?>>In Stock</option>
                            <option value="Out of Stock" <?= $product['PRODUCT_STATUS'] == 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Shop <span>*</span></label>
                        <select name="shop_id" id="shopSelect" class="form-control" required>
                            <option value="">Select Shop</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?= htmlspecialchars($shop['shop_id']) ?>" 
                                        data-category="<?= htmlspecialchars($shop['category']) ?>"
                                        <?= $shop['shop_id'] == $product['SHOP_ID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($shop['shop_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Product Image</label>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i> Choose new image (optional)
                                <input type="file" name="product_image" accept="image/*">
                            </label>
                        </div>
                        <?php if ($product['PRODUCT_IMAGE']): ?>
                        <div class="current-image">
                            <img src="uploaded_files/<?= htmlspecialchars($product['PRODUCT_IMAGE']) ?>" alt="Current Product Image">
                            <p>Current Image</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="submit-btn" name="update">Update Product</button>
                <a href="trader_dashboard.php" class="secondary-btn">Cancel & Go Back</a>
            </div>
        </form>
        <?php else: ?>
            <div class="message warning-msg">
                Product not found or you do not have permission to edit it.
            </div>
            <a href="trader_dashboard.php" class="secondary-btn">Go Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>

</html>