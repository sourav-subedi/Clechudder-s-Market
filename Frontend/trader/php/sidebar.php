<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Products</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #cad9e0;
            background: linear-gradient(135deg, #cad9e0 0%, #b8ced6 100%);
            min-height: 100vh;
        }
        
        a:link {
            text-decoration: none;
        }
        
        .container {
            display: flex;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #2b7a78 0%, #1f5b59 100%);
            color: white;
            height: 100vh;
            padding: 0;
            position: fixed;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo {
            width: 50px;
            height: 50px;
            margin: 30px auto 40px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .logo img {
            width: 28px;
            height: 28px;
            filter: brightness(0) invert(1);
        }
        
        .sidebar-nav {
            padding: 0 15px;
        }
        
        .sidebar-item {
            padding: 18px 24px;
            margin: 8px 0;
            display: flex;
            align-items: center;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            font-weight: 500;
            font-size: 14px;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, rgba(245, 160, 78, 0.3), rgba(245, 160, 78, 0.1));
            transition: width 0.3s ease;
        }
        
        .sidebar-item:hover::before {
            width: 100%;
        }
        
        .sidebar-item:hover {
            background: rgba(63, 157, 154, 0.6);
            color: #f5a04e;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-item.active {
            background: linear-gradient(135deg, #3f9d9a 0%, #2d7471 100%);
            color: #f5a04e;
            box-shadow: 0 4px 20px rgba(63, 157, 154, 0.3);
            border: 1px solid rgba(245, 160, 78, 0.3);
            font-weight: 600;
        }
        
        .sidebar-item.active::before {
            width: 100%;
            background: linear-gradient(90deg, rgba(245, 160, 78, 0.2), transparent);
        }
        
        .sidebar-item:active {
            background-color: #357f7c;
            transform: translateX(2px);
        }
        
        .sidebar-item-icon {
            margin-right: 12px;
            font-size: 16px;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .sidebar-item:hover .sidebar-item-icon {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .sidebar-item.active .sidebar-item-icon {
            opacity: 1;
            color: #f5a04e;
        }
        
        /* Sign out special styling */
        .sidebar-item.sign-out {
            margin-top: auto;
            margin-bottom: 30px;
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .sidebar-item.sign-out:hover {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b7d;
            border-color: rgba(220, 53, 69, 0.5);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar:hover {
                width: 260px;
            }
            
            .sidebar-item {
                white-space: nowrap;
            }
            
            .logo {
                margin: 20px auto;
                width: 40px;
                height: 40px;
            }
        }
        
        /* Smooth scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="../../image/logo.svg" alt="Cleckhudders Market Logo" />
            </div>
            
            <div class="sidebar-nav">
                <?php
                // Get the current page filename
                $currentPage = basename($_SERVER['PHP_SELF']);
                ?>
                
                <a href="trader_dashboard.php">
                    <div class="sidebar-item <?php echo ($currentPage == 'trader_dashboard.php') ? 'active' : ''; ?>">
                        <span class="sidebar-item-icon">■</span>
                        Dashboard
                    </div>
                </a>
                
                <a href="add_product.php">
                    <div class="sidebar-item <?php echo ($currentPage == 'add_product.php') ? 'active' : ''; ?>">
                        <span class="sidebar-item-icon">+</span>
                        Add Products
                    </div>
                </a>
                
                <a href="my_product.php">
                    <div class="sidebar-item <?php echo ($currentPage == 'my_product.php') ? 'active' : ''; ?>">
                        <span class="sidebar-item-icon">□</span>
                        My Products
                    </div>
                </a>
                
                <a href="order.php">
                    <div class="sidebar-item <?php echo ($currentPage == 'order.php') ? 'active' : ''; ?>">
                        <span class="sidebar-item-icon">○</span>
                        Orders
                    </div>
                </a>
                
                <a href="trader_profile.php">
                    <div class="sidebar-item <?php echo ($currentPage == 'trader_profile.php' && !isset($_GET['section'])) ? 'active' : ''; ?>">
                        <span class="sidebar-item-icon">●</span>
                        View Profile
                    </div>
                </a>
                
                <a href="view_report.php">
                    <div class="sidebar-item <?php echo ($currentPage == 'trader_profile.php' && isset($_GET['section']) && $_GET['section'] == 'weekly') ? 'active' : ''; ?>">
                        <span class="sidebar-item-icon">▲</span>
                        View Report
                    </div>
                </a>
                
                <a href="../../loginRegister/php/logout.php">
                    <div class="sidebar-item sign-out">
                        <span class="sidebar-item-icon">×</span>
                        Sign Out
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>