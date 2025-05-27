<?php
// This file should be included in your checkout process
// after the order is successfully placed

/**
 * Marks a coupon as used by a user
 * @param int $user_id The user ID
 * @param int $coupon_id The coupon ID
 * @param int $order_id The order ID
 * @return bool Success status
 */
function markCouponAsUsed($user_id, $coupon_id, $order_id) {
    $conn = getDBConnection();
    if (!$conn) {
        return false;
    }
    
    // Insert record into user_coupon table
    $sql = "INSERT INTO user_coupon (user_id, coupon_id, order_id, used_date) 
            VALUES (:user_id, :coupon_id, :order_id, CURRENT_DATE)";
    
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":user_id", $user_id);
    oci_bind_by_name($stid, ":coupon_id", $coupon_id);
    oci_bind_by_name($stid, ":order_id", $order_id);
    
    if (oci_execute($stid)) {
        oci_free_statement($stid);
        return true;
    } else {
        $e = oci_error($stid);
        error_log("Error marking coupon as used: " . $e['message']);
        oci_free_statement($stid);
        return false;
    }
}

/**
 * Example usage in checkout process:
 * 
 * // After successful order creation
 * if (isset($_SESSION['applied_coupon']) && $order_id) {
 *     $coupon_id = $_SESSION['applied_coupon']['coupon_id'];
 *     markCouponAsUsed($_SESSION['user_id'], $coupon_id, $order_id);
 *     
 *     // Clear the applied coupon from session
 *     unset($_SESSION['applied_coupon']);
 * }
 */
?>