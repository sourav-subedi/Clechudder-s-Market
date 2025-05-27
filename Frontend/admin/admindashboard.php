<?php
session_start();
require_once "../backend/database/dbconnection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = getDBConnection();
if (!$db) {
    die("Database connection failed.");
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trader actions
    if (isset($_POST['approve_trader'])) {
        $stmt = $db->prepare("UPDATE users SET status = 'approved' WHERE user_id = ?");
        $stmt->execute([$_POST['user_id']]);
    } elseif (isset($_POST['reject_trader'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
    
    // Customer actions
    if (isset($_POST['update_customer'])) {
        // Handle customer updates
    } elseif (isset($_POST['delete_customer'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
}

// Fetch data
$pendingTraders = $db->query("
    SELECT u.user_id, u.full_name, u.email, u.created_date, 
           GROUP_CONCAT(s.shop_category, ': ', s.shop_name SEPARATOR ', ') AS shop_details
    FROM users u
    LEFT JOIN shops s ON u.user_id = s.user_id
    WHERE u.role = 'trader' AND u.status = 'pending'
    GROUP BY u.user_id
")->fetchAll();

$allTraders = $db->query("
    SELECT u.user_id, u.full_name, u.email, u.created_date, u.status,
           GROUP_CONCAT(s.shop_category, ': ', s.shop_name SEPARATOR ', ') AS shop_details
    FROM users u
    LEFT JOIN shops s ON u.user_id = s.user_id
    WHERE u.role = 'trader'
    GROUP BY u.user_id
")->fetchAll();

$customers = $db->query("
    SELECT user_id, full_name, email,created_date, status
    FROM users
    WHERE role = 'customer'
    ORDER BY created_date DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cleckhudders Market</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">Cleckhudders Market Admin</div>
            <div>
                <span>Welcome, Admin</span>
                <a href="logout.php" style="margin-left: 15px; color: var(--primary);">Logout</a>
            </div>
        </header>
        
        <div class="search-bar">
            <input type="text" placeholder="Search users...">
            <button><i class="fas fa-search"></i> Search</button>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="switchTab('customers')">Customers</div>
            <div class="tab" onclick="switchTab('pending-traders')">Pending Traders</div>
            <div class="tab" onclick="switchTab('all-traders')">All Traders</div>
        </div>
        
        <!-- Customers Tab -->
        <div id="customers-tab" class="tab-content active">
            <h2><i class="fas fa-users"></i> Customer Management</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Joined On</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['user_id']) ?></td>
                            <td><?= htmlspecialchars($customer['full_name']) ?></td>
                            <td><?= htmlspecialchars($customer['email']) ?></td>
                            <td><?= date('M d, Y', strtotime($customer['created_date'])) ?></td>
                            <td class="status-<?= strtolower($customer['status'] ?? 'active') ?>">
                                <?= ucfirst(htmlspecialchars($customer['status'] ?? 'active')) ?>
                            </td>
                            <td class="action-buttons">
                                <button onclick="openEditModal(<?= $customer['user_id'] ?>)" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $customer['user_id'] ?>">
                                    <button type="submit" name="delete_customer" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pending Traders Tab -->
        <div id="pending-traders-tab" class="tab-content">
            <h2><i class="fas fa-user-clock"></i> Traders Pending Approval</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Shops</th>
                        <th>Joined On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingTraders as $trader): ?>
                        <tr>
                            <td><?= htmlspecialchars($trader['user_id']) ?></td>
                            <td><?= htmlspecialchars($trader['full_name']) ?></td>
                            <td><?= htmlspecialchars($trader['email']) ?></td>
                            <td><?= htmlspecialchars($trader['shop_details'] ?? 'No shops') ?></td>
                            <td><?= date('M d, Y', strtotime($trader['created_date'])) ?></td>
                            <td class="action-buttons">
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                                    <button type="submit" name="approve_trader" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                                    <button type="submit" name="reject_trader" class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pendingTraders)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No traders pending approval</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- All Traders Tab -->
        <div id="all-traders-tab" class="tab-content">
            <h2><i class="fas fa-user-tie"></i> All Traders</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Shops</th>
                        <th>Status</th>
                        <th>Joined On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allTraders as $trader): ?>
                        <tr>
                            <td><?= htmlspecialchars($trader['user_id']) ?></td>
                            <td><?= htmlspecialchars($trader['full_name']) ?></td>
                            <td><?= htmlspecialchars($trader['email']) ?></td>
                            <td><?= htmlspecialchars($trader['shop_details'] ?? 'No shops') ?></td>
                            <td class="status-<?= strtolower($trader['status']) ?>">
                                <?= ucfirst(htmlspecialchars($trader['status'])) ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($trader['created_date'])) ?></td>
                            <td class="action-buttons">
                                <button onclick="openEditModal(<?= $trader['user_id'] ?>)" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                                    <button type="submit" name="reject_trader" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Edit User</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="update_customer" value="1">
                
                <div class="form-group">
                    <label for="editFullName">Full Name</label>
                    <input type="text" id="editFullName" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editStatus">Status</label>
                    <select id="editStatus" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${tabName}-tab`).classList.add('active');
        }
        
        // Modal functions
        function openEditModal(userId) {
            // In a real implementation, you would fetch user data via AJAX
            // For now we'll just show the modal with the user ID
            document.getElementById('modalUserId').value = userId;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>