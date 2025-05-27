<?php include "../../components/header.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Product Category Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/homestyle.css">
    <link rel="stylesheet" href="../css/product_page.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .filter-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .sort-box {
            flex: 1;
            min-width: 200px;
        }
        
        .price-range-box {
            flex: 2;
            min-width: 300px;
        }
        
        .filter-select, .range-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }
        
        .range-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .range-input {
            flex: 1;
        }
        
        .apply-btn, .reset-btn {
            padding: 10px 15px;
            background-color: #f89c54;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .apply-btn:hover, .reset-btn:hover {
            background-color: #e78c3f;
        }
        
        .reset-btn {
            background-color: #6c757d;
        }
        
        .reset-btn:hover {
            background-color: #5a6268;
        }
        
        .shop-category-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f89c54;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .shops-btn {
    display: block;
    text-align: center;
    text-decoration: none;
    background-color: #f89c54;
    color: white;
    margin-top: 3px;
    padding: 10px;
    border-radius: 4px;
    font-weight: 500;
    transition: background-color 0.3s;
}

.shops-btn:hover {
    background-color: #e78c3f;
}
        
        .shop-name {
            font-style: italic;
            color: #666;
            margin-bottom: 8px;
        }
        
        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px 0;
            color: #666;
        }
        
        .empty i {
            margin-bottom: 15px;
            color: #999;
        }
        
        .reset-filters {
            display: inline-block;
            margin-top: 15px;
            background-color: #f89c54;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .reset-filters:hover {
            background-color: #e78c3f;
        }
        
        .active-filter-summary {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 10px 0 20px;
            font-size: 14px;
        }
        
        .filter-tag {
            display: inline-block;
            background-color: #f89c54;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            
            .sort-box, .price-range-box {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <section class="deals-section">
        <h2 class="section-title">Our Products</h2>
        
        <div class="filter-box">
            <form action="product_page.php" method="GET" class="filter-form">
                <div class="sort-box">
                    <label for="sort">Sort by price:</label>
                    <select name="sort" id="sort" class="filter-select">
                        <option value="default" <?= ($_GET['sort'] ?? '') === 'default' ? 'selected' : '' ?>>Default</option>
                        <option value="low_to_high" <?= ($_GET['sort'] ?? '') === 'low_to_high' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="high_to_low" <?= ($_GET['sort'] ?? '') === 'high_to_low' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </div>
                
                <div class="sort-box">
                    <label for="shop">Shop Category:</label>
                    <select name="shop" id="shop" class="filter-select">
                        <option value="default" <?= ($_GET['shop'] ?? '') === 'default' ? 'selected' : '' ?>>All Shops</option>
                        <option value="butcher" <?= ($_GET['shop'] ?? '') === 'butcher' ? 'selected' : '' ?>>Butcher</option>
                        <option value="fishmonger" <?= ($_GET['shop'] ?? '') === 'fishmonger' ? 'selected' : '' ?>>Fishmonger</option>
                        <option value="bakery" <?= ($_GET['shop'] ?? '') === 'bakery' ? 'selected' : '' ?>>Bakery</option>
                        <option value="greengrocer" <?= ($_GET['shop'] ?? '') === 'greengrocer' ? 'selected' : '' ?>>Greengrocer</option>
                        <option value="delicatessen" <?= ($_GET['shop'] ?? '') === 'delicatessen' ? 'selected' : '' ?>>Delicatessen</option>
                    </select>
                </div>
                
                <div class="price-range-box">
                    <label>Price Range:</label>
                    <div class="range-inputs">
                        <input type="number" name="min" placeholder="Min" class="range-input" value="<?= $_GET['min'] ?? '' ?>">
                        <span>to</span>
                        <input type="number" name="max" placeholder="Max" class="range-input" value="<?= $_GET['max'] ?? '' ?>">
                        <button type="submit" class="apply-btn">Apply Filters</button>
                        <a href="product_page.php" class="reset-btn">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php
        // Display active filters summary
        $activeFilters = [];
        if (isset($_GET['sort']) && $_GET['sort'] !== 'default') {
            $sortText = $_GET['sort'] === 'low_to_high' ? 'Price: Low to High' : 'Price: High to Low';
            $activeFilters[] = "Sorted by: $sortText";
        }
        
        if (isset($_GET['shop']) && $_GET['shop'] !== 'default') {
            $activeFilters[] = "Shop: " . ucfirst($_GET['shop']);
        }
        
        if (isset($_GET['min']) && $_GET['min'] !== '') {
            $activeFilters[] = "Min Price: $ " . $_GET['min'];
        }
        
        if (isset($_GET['max']) && $_GET['max'] !== '') {
            $activeFilters[] = "Max Price: $ " . $_GET['max'];
        }
        
        if (!empty($activeFilters)) {
            echo '<div class="active-filter-summary">Active filters: ';
            foreach ($activeFilters as $filter) {
                echo '<span class="filter-tag">' . $filter . '</span>';
            }
            echo '</div>';
        }
        ?>
        
        <?php include "product_list.php"; ?>
    </section>

    <!-- FOOTER -->
    <?php include "../../components/footer.php"; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Function to show customized toast notifications
        function showToast(message, type) {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: type === 'success' ? '#4CAF50' : '#f44336',
                close: true
            }).showToast();
        }

        // Handle quantity controls
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.product-card').forEach(card => {
                const minus = card.querySelector('.minus');
                const plus = card.querySelector('.plus');
                const qtyDisplay = card.querySelector('.qty-display');
                const qtyHidden = card.querySelector('.qty-hidden');
                const addToCartBtn = card.querySelector('.add-to-cart');
                const productId = card.querySelector('.product-id').value;

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

                // AJAX for adding to cart
                addToCartBtn.addEventListener('click', function() {
                    // Create form data object
                    const formData = new FormData();
                    formData.append('add_to_cart', 'true');
                    formData.append('product_id', productId);
                    formData.append('qty', qty);

                    // Create AJAX request
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Show notification based on response
                        showToast(data.message, data.status);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast("Error adding to cart!", "error");
                    });
                });
            });
        });
    </script>
</body>

</html>