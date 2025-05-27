<?php
session_start();
require "../../../Backend/connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Check if required POST data is present
if (!isset($_POST['slot_date']) || !isset($_POST['slot_day']) || !isset($_POST['slot_time'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit();
}

// Get and sanitize input data
$slot_date = $_POST['slot_date'];
$slot_day = $_POST['slot_day'];
$slot_time = $_POST['slot_time'];

// Convert slot_time to proper timestamp format
// Assuming slot_time is in format "HH-HH" (e.g., "10-13")
$time_parts = explode('-', $slot_time);
if (count($time_parts) !== 2) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid time format']);
    exit();
}

$start_hour = $time_parts[0];
$end_hour = $time_parts[1];

// Create timestamp for the slot
$slot_timestamp = date('Y-m-d H:i:s', strtotime("$slot_date $start_hour:00:00"));

// Check if slot already exists
$check_sql = "SELECT COUNT(*) as count FROM collection_slot 
              WHERE slot_date = TO_DATE(:slot_date, 'YYYY-MM-DD') 
              AND slot_time = TO_TIMESTAMP(:slot_timestamp, 'YYYY-MM-DD HH24:MI:SS')";

$check_stmt = oci_parse($conn, $check_sql);
oci_bind_by_name($check_stmt, ":slot_date", $slot_date);
oci_bind_by_name($check_stmt, ":slot_timestamp", $slot_timestamp);

if (!oci_execute($check_stmt)) {
    echo json_encode(['status' => 'error', 'message' => 'Error checking slot availability']);
    exit();
}

$row = oci_fetch_assoc($check_stmt);
if ($row['COUNT'] > 0) {
    // Slot exists, update total_order
    $update_sql = "UPDATE collection_slot 
                   SET total_order = total_order + 1 
                   WHERE slot_date = TO_DATE(:slot_date, 'YYYY-MM-DD') 
                   AND slot_time = TO_TIMESTAMP(:slot_timestamp, 'YYYY-MM-DD HH24:MI:SS')";
    
    $update_stmt = oci_parse($conn, $update_sql);
    oci_bind_by_name($update_stmt, ":slot_date", $slot_date);
    oci_bind_by_name($update_stmt, ":slot_timestamp", $slot_timestamp);
    
    if (!oci_execute($update_stmt)) {
        echo json_encode(['status' => 'error', 'message' => 'Error updating slot']);
        exit();
    }
    
    // Get the collection_slot_id
    $get_id_sql = "SELECT collection_slot_id FROM collection_slot 
                   WHERE slot_date = TO_DATE(:slot_date, 'YYYY-MM-DD') 
                   AND slot_time = TO_TIMESTAMP(:slot_timestamp, 'YYYY-MM-DD HH24:MI:SS')";
    
    $get_id_stmt = oci_parse($conn, $get_id_sql);
    oci_bind_by_name($get_id_stmt, ":slot_date", $slot_date);
    oci_bind_by_name($get_id_stmt, ":slot_timestamp", $slot_timestamp);
    
    if (!oci_execute($get_id_stmt)) {
        echo json_encode(['status' => 'error', 'message' => 'Error getting slot ID']);
        exit();
    }
    
    $slot_row = oci_fetch_assoc($get_id_stmt);
    $collection_slot_id = $slot_row['COLLECTION_SLOT_ID'];
    
} else {
    // Slot doesn't exist, create new slot
    $insert_sql = "INSERT INTO collection_slot (slot_date, slot_day, slot_time, total_order) 
                   VALUES (TO_DATE(:slot_date, 'YYYY-MM-DD'), :slot_day, 
                   TO_TIMESTAMP(:slot_timestamp, 'YYYY-MM-DD HH24:MI:SS'), 1)";
    
    $insert_stmt = oci_parse($conn, $insert_sql);
    oci_bind_by_name($insert_stmt, ":slot_date", $slot_date);
    oci_bind_by_name($insert_stmt, ":slot_day", $slot_day);
    oci_bind_by_name($insert_stmt, ":slot_timestamp", $slot_timestamp);
    
    if (!oci_execute($insert_stmt)) {
        echo json_encode(['status' => 'error', 'message' => 'Error creating slot']);
        exit();
    }
    
    // Get the newly created collection_slot_id
    $get_id_sql = "SELECT collection_slot_id FROM collection_slot 
                   WHERE slot_date = TO_DATE(:slot_date, 'YYYY-MM-DD') 
                   AND slot_time = TO_TIMESTAMP(:slot_timestamp, 'YYYY-MM-DD HH24:MI:SS')";
    
    $get_id_stmt = oci_parse($conn, $get_id_sql);
    oci_bind_by_name($get_id_stmt, ":slot_date", $slot_date);
    oci_bind_by_name($get_id_stmt, ":slot_timestamp", $slot_timestamp);
    
    if (!oci_execute($get_id_stmt)) {
        echo json_encode(['status' => 'error', 'message' => 'Error getting slot ID']);
        exit();
    }
    
    $slot_row = oci_fetch_assoc($get_id_stmt);
    $collection_slot_id = $slot_row['COLLECTION_SLOT_ID'];
}

// Clean up
oci_free_statement($check_stmt);
if (isset($update_stmt)) oci_free_statement($update_stmt);
if (isset($insert_stmt)) oci_free_statement($insert_stmt);
if (isset($get_id_stmt)) oci_free_statement($get_id_stmt);
oci_close($conn);

// Return success response with the collection_slot_id
echo json_encode([
    'status' => 'success',
    'message' => 'Collection slot saved successfully',
    'collection_slot_id' => $collection_slot_id
]);
?>