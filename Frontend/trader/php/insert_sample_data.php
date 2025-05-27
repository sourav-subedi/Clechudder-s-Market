<?php
session_start();
require "../../../Backend/connect.php";

// Check if user is logged in and is a trader
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trader') {
    die("Please log in as a trader first.");
}

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed");
}

try {
    // First, let's get the shop_id for the current trader
    $user_id = $_SESSION['user_id'];
    $shop_query = "SELECT shop_id FROM shops WHERE user_id = :user_id";
    $shop_stmt = oci_parse($conn, $shop_query);
    oci_bind_by_name($shop_stmt, ':user_id', $user_id);
    oci_execute($shop_stmt);
    $shop = oci_fetch_assoc($shop_stmt);
    
    if (!$shop) {
        die("No shop found for this user. Please create a shop first.");
    }
    
    $shop_id = $shop['SHOP_ID'];
    
    // Insert sample products
    $products = [
        ['name' => 'Fresh Apples', 'price' => 2.50, 'stock' => 100, 'min_order' => 1, 'max_order' => 10],
        ['name' => 'Organic Bananas', 'price' => 1.75, 'stock' => 150, 'min_order' => 1, 'max_order' => 15],
        ['name' => 'Fresh Milk', 'price' => 3.20, 'stock' => 50, 'min_order' => 1, 'max_order' => 5],
        ['name' => 'Whole Grain Bread', 'price' => 2.80, 'stock' => 30, 'min_order' => 1, 'max_order' => 3]
    ];
    
    foreach ($products as $product) {
        $insert_product = "
            INSERT INTO product (
                product_name, price, stock, min_order, max_order, 
                product_status, shop_id, user_id, product_category_name
            ) VALUES (
                :name, :price, :stock, :min_order, :max_order,
                'active', :shop_id, :user_id, 'greengrocer'
            )
        ";
        
        $stmt = oci_parse($conn, $insert_product);
        oci_bind_by_name($stmt, ':name', $product['name']);
        oci_bind_by_name($stmt, ':price', $product['price']);
        oci_bind_by_name($stmt, ':stock', $product['stock']);
        oci_bind_by_name($stmt, ':min_order', $product['min_order']);
        oci_bind_by_name($stmt, ':max_order', $product['max_order']);
        oci_bind_by_name($stmt, ':shop_id', $shop_id);
        oci_bind_by_name($stmt, ':user_id', $user_id);
        
        if (oci_execute($stmt)) {
            echo "Inserted product: {$product['name']}<br>";
        }
    }
    
    // Get the inserted product IDs
    $get_products = "SELECT product_id FROM product WHERE shop_id = :shop_id";
    $stmt = oci_parse($conn, $get_products);
    oci_bind_by_name($stmt, ':shop_id', $shop_id);
    oci_execute($stmt);
    
    $product_ids = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $product_ids[] = $row['PRODUCT_ID'];
    }
    
    // Create sample orders for the last 7 days
    for ($i = 0; $i < 7; $i++) {
        // Create a cart and get its ID
        $insert_cart = "
            INSERT INTO cart (user_id, add_date) 
            VALUES (:user_id, SYSDATE)
            RETURNING cart_id INTO :cart_id
        ";
        
        $stmt = oci_parse($conn, $insert_cart);
        oci_bind_by_name($stmt, ':user_id', $user_id);
        oci_bind_by_name($stmt, ':cart_id', $cart_id, -1, OCI_B_INT);
        oci_execute($stmt);
        
        // Add random products to cart
        $num_products = rand(1, 3);
        $selected_products = array_rand($product_ids, $num_products);
        
        foreach ($selected_products as $index) {
            $product_id = $product_ids[$index];
            $quantity = rand(1, 5);
            
            $insert_cart_item = "
                INSERT INTO product_cart (cart_id, product_id, quantity)
                VALUES (:cart_id, :product_id, :quantity)
            ";
            
            $stmt = oci_parse($conn, $insert_cart_item);
            oci_bind_by_name($stmt, ':cart_id', $cart_id);
            oci_bind_by_name($stmt, ':product_id', $product_id);
            oci_bind_by_name($stmt, ':quantity', $quantity);
            oci_execute($stmt);
        }
        
        // Create the order
        $insert_order = "
            INSERT INTO orders (
                order_date, order_amount, total_amount, status,
                user_id, cart_id
            ) VALUES (
                SYSDATE - :days_ago,
                (SELECT SUM(p.price * pc.quantity) 
                 FROM product_cart pc 
                 JOIN product p ON pc.product_id = p.product_id 
                 WHERE pc.cart_id = :cart_id),
                (SELECT SUM(p.price * pc.quantity) 
                 FROM product_cart pc 
                 JOIN product p ON pc.product_id = p.product_id 
                 WHERE pc.cart_id = :cart_id),
                'completed',
                :user_id,
                :cart_id
            )
        ";
        
        $stmt = oci_parse($conn, $insert_order);
        oci_bind_by_name($stmt, ':days_ago', $i);
        oci_bind_by_name($stmt, ':cart_id', $cart_id);
        oci_bind_by_name($stmt, ':user_id', $user_id);
        
        if (oci_execute($stmt)) {
            echo "Created order for day {$i}<br>";
        }
    }
    
    echo "<br>Sample data has been inserted successfully!";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

oci_close($conn);
?> 