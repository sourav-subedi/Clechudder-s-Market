<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once "../../../Backend/connect.php";

// Set content type for JSON response
header('Content-Type: text/plain');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "error";
    exit;
}

// Check if user is logged in
$trader_id = $_SESSION['user_id'] ?? null;
if (!$trader_id) {
    echo "unauthorized";
    exit;
}

// Get product ID from POST data
$product_id = $_POST['id'] ?? null;
if (!$product_id || !is_numeric($product_id)) {
    echo "invalid_id";
    exit;
}

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    echo "db_error";
    exit;
}

try {
    // First, verify that the product belongs to the current user
    $verify_query = "SELECT p.product_id, p.product_image 
                     FROM product p 
                     INNER JOIN shops s ON p.shop_id = s.shop_id 
                     WHERE p.product_id = :product_id AND s.user_id = :user_id";
    
    $verify_stmt = oci_parse($conn, $verify_query);
    oci_bind_by_name($verify_stmt, ":product_id", $product_id);
    oci_bind_by_name($verify_stmt, ":user_id", $trader_id);
    
    if (!oci_execute($verify_stmt)) {
        echo "query_error";
        exit;
    }
    
    $product_data = oci_fetch_assoc($verify_stmt);
    oci_free_statement($verify_stmt);
    
    if (!$product_data) {
        echo "not_found";
        exit;
    }
    
    // Delete the product from database
    $delete_query = "DELETE FROM product WHERE product_id = :product_id";
    $delete_stmt = oci_parse($conn, $delete_query);
    oci_bind_by_name($delete_stmt, ":product_id", $product_id);
    
    if (oci_execute($delete_stmt)) {
        // Commit the transaction
        oci_commit($conn);
        
        // Optional: Delete the product image file from server
        $image_path = __DIR__ . "/uploaded_files/" . $product_data['PRODUCT_IMAGE'];
        if ($product_data['PRODUCT_IMAGE'] && file_exists($image_path)) {
            unlink($image_path);
        }
        
        echo "success";
    } else {
        // Rollback the transaction
        oci_rollback($conn);
        echo "delete_failed";
    }
    
    oci_free_statement($delete_stmt);
    
} catch (Exception $e) {
    // Rollback transaction on error
    oci_rollback($conn);
    error_log("Delete product error: " . $e->getMessage());
    echo "exception";
} finally {
    // Close database connection
    if ($conn) {
        oci_close($conn);
    }
}
?>