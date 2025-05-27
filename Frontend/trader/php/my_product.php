<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once "../../../Backend/connect.php";
include_once "sidebar.php";

$conn = getDBConnection();
if (!$conn) die("Database connection failed");

$trader_id = $_SESSION['user_id'] ?? null;
if (!$trader_id) die("Unauthorized access.");

// Fetch trader's products
$query = "SELECT p.*, s.shop_name 
          FROM product p 
          INNER JOIN shops s ON p.shop_id = s.shop_id 
          WHERE s.user_id = :user_id
          ORDER BY p.add_date DESC";
$stmt = oci_parse($conn, $query);
oci_bind_by_name($stmt, ":user_id", $trader_id);
oci_execute($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Products</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #cad9e0 0%, #b8ced6 100%);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            height: 100% !important;
            z-index: 999 !important;
        }

        .main-content {
            margin-left: 260px;
            padding: 40px;
            width: calc(100% - 260px);
            min-height: 100vh;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2b7a78;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: #5a6c72;
            font-weight: 400;
        }

        .products-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(43, 122, 120, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            min-width: 150px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2b7a78;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #5a6c72;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            padding: 10px;
            width: 100%;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 
                0 15px 35px rgba(43, 122, 120, 0.1),
                0 1px 0px rgba(255, 255, 255, 0.8) inset;
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2b7a78 0%, #3f9d9a 50%, #f5a04e 100%);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 
                0 25px 50px rgba(43, 122, 120, 0.15),
                0 1px 0px rgba(255, 255, 255, 0.8) inset;
        }

        .card-image {
            position: relative;
            overflow: hidden;
            height: 200px;
        }

        .card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .card:hover img {
            transform: scale(1.05);
        }

        .price-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #f5a04e 0%, #e8941f 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(245, 160, 78, 0.3);
        }

        .card-content {
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            flex-grow: 1;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2b7a78;
            margin-bottom: 5px;
            line-height: 1.3;
        }

        .card-description {
            color: #5a6c72;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        .card-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-top: 1px solid rgba(43, 122, 120, 0.1);
            margin-top: auto;
        }

        .shop-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .shop-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #2b7a78 0%, #3f9d9a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
            font-size: 0.8rem;
        }

        .shop-name {
            color: #2b7a78;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-edit {
            background: linear-gradient(135deg, #2b7a78 0%, #3f9d9a 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(43, 122, 120, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(43, 122, 120, 0.4);
            background: linear-gradient(135deg, #3f9d9a 0%, #2b7a78 100%);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 2px solid rgba(220, 53, 69, 0.2);
        }

        .btn-delete:hover {
            background: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }

        .btn-delete:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 40px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(43, 122, 120, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2b7a78 0%, #3f9d9a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px auto;
            font-size: 2rem;
            color: white;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2b7a78;
            margin-bottom: 10px;
        }

        .empty-text {
            color: #5a6c72;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .btn-add {
            background: linear-gradient(135deg, #f5a04e 0%, #e8941f 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 160, 78, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 160, 78, 0.4);
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .grid-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .products-stats {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .page-title {
                font-size: 2rem;
            }

            .actions {
                flex-direction: column;
            }
        }

        /* Loading animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #2b7a78;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Products</h1>
            <p class="page-subtitle">Manage your product inventory and listings</p>
        </div>

        <?php
        // Count products for stats
        $count_query = "SELECT COUNT(*) as total FROM product p INNER JOIN shops s ON p.shop_id = s.shop_id WHERE s.user_id = :user_id";
        $count_stmt = oci_parse($conn, $count_query);
        oci_bind_by_name($count_stmt, ":user_id", $trader_id);
        oci_execute($count_stmt);
        $count_result = oci_fetch_assoc($count_stmt);
        $total_products = $count_result['TOTAL'];
        oci_free_statement($count_stmt);
        ?>

        <div class="products-stats">
            <div class="stat-card">
                <span class="stat-number" id="total-count"><?= $total_products ?></span>
                <span class="stat-label">Total Products</span>
            </div>
        </div>

        <div class="grid-container" id="products-grid">
            <?php
            $products_found = false;
            while ($product = oci_fetch_assoc($stmt)) {
                $products_found = true;
                $description = $product['DESCRIPTION'];
                if ($description instanceof OCILob) $description = $description->load();
                $short_description = strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
            ?>
                <div class="card" data-product-id="<?= $product['PRODUCT_ID']; ?>">
                    <div class="card-image">
                        <img src="../../trader/php/uploaded_files/<?= $product['PRODUCT_IMAGE']; ?>" alt="<?= htmlspecialchars($product['PRODUCT_NAME']); ?>">
                        <div class="price-badge">$<?= number_format($product['PRICE'], 2); ?></div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($product['PRODUCT_NAME']); ?></h3>
                        <p class="card-description"><?= htmlspecialchars($short_description); ?></p>
                        
                        <div class="card-meta">
                            <div class="shop-info">
                                <div class="shop-icon"><?= strtoupper(substr($product['SHOP_NAME'], 0, 1)); ?></div>
                                <span class="shop-name"><?= htmlspecialchars($product['SHOP_NAME']); ?></span>
                            </div>
                        </div>

                        <div class="actions">
                            <a href="edit_product.php?id=<?= $product['PRODUCT_ID']; ?>" class="btn btn-edit">
                                <span>✏</span> Edit
                            </a>
                            <button class="btn btn-delete" data-id="<?= $product['PRODUCT_ID']; ?>">
                                <span>🗑</span> Delete
                            </button>
                        </div>
                    </div>
                </div>
            <?php }
            oci_free_statement($stmt);
            oci_close($conn);

            if (!$products_found): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3 class="empty-title">No Products Yet</h3>
                    <p class="empty-text">You haven't added any products to your inventory yet. Start by adding your first product!</p>
                    <a href="add_product.php" class="btn-add">
                        <span>+</span> Add Your First Product
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Notification function
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Hide and remove notification
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Update product count
        function updateProductCount() {
            const count = document.querySelectorAll('.card[data-product-id]').length;
            document.getElementById('total-count').textContent = count;
        }

        // Handle delete functionality
        document.querySelectorAll(".btn-delete").forEach(button => {
            button.addEventListener("click", function() {
                const productId = this.dataset.id;
                const card = this.closest('.card');
                
                if (confirm("Are you sure you want to delete this product? This action cannot be undone.")) {
                    // Add loading state
                    card.classList.add('loading');
                    this.disabled = true;
                    this.innerHTML = '<span>⏳</span> Deleting...';
                    
                    fetch('delete_product.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${productId}`
                        })
                        .then(res => res.text())
                        .then(data => {
                            if (data.trim() === "success") {
                                // Smooth removal animation
                                card.style.transform = 'scale(0.8)';
                                card.style.opacity = '0';
                                setTimeout(() => {
                                    card.remove();
                                    updateProductCount();
                                    
                                    // Check if no products left
                                    const remainingCards = document.querySelectorAll('.card[data-product-id]').length;
                                    if (remainingCards === 0) {
                                        location.reload(); // Reload to show empty state
                                    }
                                }, 300);
                                
                                showNotification('Product deleted successfully!', 'success');
                            } else {
                                // Remove loading state and show error
                                card.classList.remove('loading');
                                this.disabled = false;
                                this.innerHTML = '<span>🗑</span> Delete';
                                
                                let errorMessage = 'Failed to delete product. Please try again.';
                                switch(data.trim()) {
                                    case 'unauthorized':
                                        errorMessage = 'Unauthorized access.';
                                        break;
                                    case 'invalid_id':
                                        errorMessage = 'Invalid product ID.';
                                        break;
                                    case 'not_found':
                                        errorMessage = 'Product not found or you don\'t have permission to delete it.';
                                        break;
                                    case 'db_error':
                                        errorMessage = 'Database connection error.';
                                        break;
                                }
                                
                                showNotification(errorMessage, 'error');
                            }
                        })
                        .catch(error => {
                            card.classList.remove('loading');
                            this.disabled = false;
                            this.innerHTML = '<span>🗑</span> Delete';
                            showNotification('Network error occurred. Please try again.', 'error');
                        });
                }
            });
        });
    </script>
</body>

</html>