<?php
session_start();
require_once "../../../Backend/connect.php";
include "../../components/header.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../loginRegister/php/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

if (!$conn) {
    echo "Database connection failed.";
    exit();
}

$sql = "SELECT full_name, email, role, created_date, status FROM users WHERE user_id = :user_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":user_id", $user_id);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    echo "Error fetching user: " . $e['message'];
    exit();
}

$user = oci_fetch_assoc($stid);
oci_free_statement($stid);

if (!$user) {
    echo "User not found.";
    exit();
}

$homePage = ($user["ROLE"] === "trader") ? "../../loginRegister/php/trader_dashboard.php" : "home.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Customer Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../css/user_Profile.css" />
  <link rel="stylesheet" href="../css/homestyle.css">
</head>
<body>

<div class="background">
    <div class="overlay"></div>
</div>

<!-- PROFILE SECTION (as card like purchases) -->
<section class="recent-purchases">
  <h2>My Profile</h2>
  <div class="purchase-item">
    <div class="item-text">
      <h3><?php echo htmlspecialchars($user['FULL_NAME']); ?> <span class="price"><?php echo ucfirst(htmlspecialchars($user['ROLE'])); ?></span></h3>
      <p class="description">Email: <?php echo htmlspecialchars($user['EMAIL']); ?></p>
      <p class="info">Created On: <?php echo htmlspecialchars($user['CREATED_DATE']); ?> | Status: <?php echo htmlspecialchars($user['STATUS']); ?></p>

      <div style="margin-top: 1rem;">
        <a href="edit_profile.php" class="review-btn">Edit</a>
        <a href="../../loginRegister/php/logout.php" class="review-btn">Logout</a>
        <a href="<?php echo $homePage; ?>" class="review-btn">Home</a>
      </div>
    </div>
  </div>
</section>

  <!-- FOOTER -->
  <?php
    include "../../components/footer.php";
  ?>
</body>
</html>
