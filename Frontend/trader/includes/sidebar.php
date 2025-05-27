<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>Trader Dashboard</h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo $current_page === 'trader_dashboard.php' ? 'active' : ''; ?>">
                <a href="trader_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'add_product.php' ? 'active' : ''; ?>">
                <a href="add_product.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Product</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'manage_products.php' ? 'active' : ''; ?>">
                <a href="manage_products.php">
                    <i class="fas fa-box"></i>
                    <span>Manage Products</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'manage_orders.php' ? 'active' : ''; ?>">
                <a href="manage_orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Manage Orders</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="../../loginRegister/php/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 250px;
    background: var(--gradient-card);
    box-shadow: var(--shadow-medium);
    z-index: 1000;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.sidebar-header h2 {
    margin: 0;
    font-size: 1.5em;
    color: var(--primary);
}

.sidebar-nav {
    padding: 20px 0;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav li {
    margin: 5px 0;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.3s ease;
}

.sidebar-nav a:hover {
    background: rgba(245, 160, 78, 0.1);
    color: var(--primary);
}

.sidebar-nav li.active a {
    background: var(--gradient-primary);
    color: white;
}

.sidebar-nav i {
    width: 20px;
    margin-right: 10px;
    text-align: center;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
}
</style>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> 