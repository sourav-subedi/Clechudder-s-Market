<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require dirname(__DIR__, 2) . "/Backend/connect.php";


// Function to get cart count
function getCartCount($user_id) {
    if (!$user_id) return 0;
    
    $conn = getDBConnection();
    if (!$conn) return 0;

    $sql = "SELECT SUM(pc.quantity) as total_items 
            FROM cart c 
            JOIN product_cart pc ON c.cart_id = pc.cart_id 
            WHERE c.user_id = :user_id";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":user_id", $user_id);
    oci_execute($stmt);
    
    $row = oci_fetch_array($stmt, OCI_ASSOC);
    oci_free_statement($stmt);
    
    return $row['TOTAL_ITEMS'] ?? 0;
}

// Get cart count for current user
$cart_count = isset($_SESSION['user_id']) ? getCartCount($_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Landing page</title>
  <!-- Google Font: Rubik (Example) -->
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600&display=swap" rel="stylesheet" />
  <!-- Link to external CSS -->
  <link rel="stylesheet" href="../customer/css/homestyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
  <!-- Include Header -->
  <header class="navbar">
    <div class="navbar-left">
      <!-- Logo -->
      <a href="../php/home.php" class="logo">
        <img src="../../image/logo.svg" alt="Cleckhudders Market Logo" />
      </a>
      <!-- Nav Links -->
      <ul class="nav-links">
        <li><a href="product_page.php">PRODUCT</a></li>
        <li><a href="product_Catagory.php">SHOP</a></li>
        <li><a href="contact_us.php">CONTACT US</a></li>
        <li><a href="wish_list.php">WISH LIST</a></li>
        <li><a href="about_us.php">ABOUT US</a></li>
      </ul>
    </div>
    <div class="navbar-right">
      <!-- Search Bar -->
      <div class="search-bar">
        <form action="/Implementation_And_Coding/Frontend/customer/php/search_handler.php" method="GET">
          <input type="text" name="search" placeholder="Search products..." required />
          <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <a href="../../customer/php/shopping_cart.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i>
        <?php if ($cart_count > 0): ?>
          <span class="cart-count"><?php echo $cart_count; ?></span>
        <?php endif; ?>
      </a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <!-- User Profile Icon -->
        <div class="user-icon">
          <a href="user_Profile.php" class="user-icon">
            <i class="fas fa-user"></i>
          </a>
          <a href="../../loginRegister/php/logout.php"><button>Logout</button></a>
        </div>
      <?php else: ?>
        <button onclick="location.href='../../loginRegister/php/user_selection.php'">Sign Up</button>
        <button onclick="location.href='../../loginRegister/php/login.php'">Log In</button>
      <?php endif; ?>
    </div>
  </header>
</body>