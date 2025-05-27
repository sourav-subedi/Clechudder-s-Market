<?php
session_start();
include_once "sidebar.php";

// Check if the user is logged in and is a trader
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trader') {
    header("Location: ../../loginRegister/php/login.php");
    exit();
}

// Include the OCI connection
include "../../../Backend/connect.php";
$conn = getDBConnection();

$user_id = $_SESSION['user_id'];

try {
    // Get trader info
    $query = "SELECT * FROM users WHERE user_id = :user_id AND role = 'trader'";
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ':user_id', $user_id);
    oci_execute($stmt);
    $userData = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$userData) {
        throw new Exception("Trader data not found");
    }

    // Get recent orders (last 5)
    $ordersQuery = "SELECT * FROM (
                        SELECT o.*, u.full_name AS customer_name, u.email AS customer_email 
                        FROM orders o
                        JOIN users u ON o.user_id = u.user_id
                        ORDER BY o.order_date DESC
                    ) WHERE ROWNUM <= 5";
    $ordersStmt = oci_parse($conn, $ordersQuery);
    oci_execute($ordersStmt);

    $recentOrders = [];
    while ($row = oci_fetch_assoc($ordersStmt)) {
        $recentOrders[] = $row;
    }
    oci_free_statement($ordersStmt);

    // Get order statistics
    $statsQuery = "SELECT 
        COUNT(*) AS total_orders,
        SUM(total_amount) AS total_sales,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders
        FROM orders";
    $statsStmt = oci_parse($conn, $statsQuery);
    oci_execute($statsStmt);
    $orderStats = oci_fetch_assoc($statsStmt);
    oci_free_statement($statsStmt);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    try {
        $order_id = (int) $_POST['order_id'];
        $newStatus = '';
        switch ($_POST['action']) {
            case 'process':
                $newStatus = 'processing';
                break;
            case 'complete':
                $newStatus = 'completed';
                break;
            case 'cancel':
                $newStatus = 'cancelled';
                break;
        }

        $updateQuery = "UPDATE orders SET status = :status WHERE order_id = :order_id";
        $updateStmt = oci_parse($conn, $updateQuery);
        oci_bind_by_name($updateStmt, ':status', $newStatus);
        oci_bind_by_name($updateStmt, ':order_id', $order_id);
        oci_execute($updateStmt);
        oci_free_statement($updateStmt);

        header("Location: trader_dashboard.php");
        exit();
    } catch (Exception $e) {
        die("Update failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Dashboard - Cleckhudders Market</title>
    <link rel="stylesheet" href="../css/trader_dashboard.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Cleckhudders Market</h1>
        <h2>Trader Dashboard</h2>
        
        <div class="welcome-message">
            <h3>Welcome back, <?php echo htmlspecialchars($userData['FULL_NAME']); ?>!</h3>
            <p>Ready to manage your shop and keep things running smoothly.</p>
            <a href="add_product.php" class="edit-btn" style="margin-top: 20px;">Add Product</a>
        </div>
        
        <div class="trader-info">
            <h3>Your Account Information</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($userData['FULL_NAME']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userData['EMAIL']); ?></p>
            <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($userData['CREATED_DATE'])); ?></p>
        </div>
        
        <div class="metrics">
            <div class="metric-card">
                <h3>Total Orders</h3>
                <div class="metric-value"><?php echo $orderStats['TOTAL_ORDERS'] ?? 0; ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Total Sales</h3>
                <div class="metric-value">$<?php echo number_format($orderStats['TOTAL_SALES'] ?? 0, 2); ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Completed Orders</h3>
                <div class="metric-value"><?php echo $orderStats['COMPLETED_ORDERS'] ?? 0; ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Pending Orders</h3>
                <div class="metric-value"><?php echo $orderStats['PENDING_ORDERS'] ?? 0; ?></div>
            </div>
        </div>
        
        <h2>Recent Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentOrders)): ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['ORDER_ID'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($order['CUSTOMER_NAME'] ?? 'N/A'); ?></td>
                            <td><?php echo isset($order['ORDER_DATE']) ? date('m/d/Y', strtotime($order['ORDER_DATE'])) : 'N/A'; ?></td>
                            <td>$<?php echo isset($order['TOTAL_AMOUNT']) ? number_format($order['TOTAL_AMOUNT'], 2) : '0.00'; ?></td>
                            <td class="status-<?php echo htmlspecialchars($order['STATUS'] ?? 'unknown'); ?>">
                                <?php echo ucfirst(htmlspecialchars($order['STATUS'] ?? 'Unknown')); ?>
                            </td>
                            <td>
                                <?php if (isset($order['STATUS']) && $order['STATUS'] == 'pending'): ?>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $order['ORDER_ID']; ?>">
                                        <input type="hidden" name="action" value="process">
                                        <button type="submit" class="btn btn-process">Process</button>
                                    </form>
                                <?php elseif (isset($order['STATUS']) && $order['STATUS'] == 'processing'): ?>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $order['ORDER_ID']; ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="btn btn-complete">Complete</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (isset($order['STATUS']) && !in_array($order['STATUS'], ['completed', 'cancelled'])): ?>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $order['ORDER_ID']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-cancel">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No recent orders found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
