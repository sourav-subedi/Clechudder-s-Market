<?php
session_start();
include_once "../../components/header.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Landing page</title>
  <!-- Google Font: Rubik (Example) -->
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600&display=swap" rel="stylesheet" />
  <!-- Link to external CSS -->
  <link rel="stylesheet" href="../css/homestyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-overlay">
      <div class="hero-section">
        <div class="hero-text">
          <h1>Too Busy to Shop?<br>Let Us Deliver to Your Door!</h1>
          <p>Enjoy quality shopping from your favourite local traders. Order now and collect your order at our convenient pickup point.</p>
          <a href="product_page.php" class="shop-btn">SHOP NOW</a>
        </div>
        <div class="hero-image">
          <img src="../../image/bag_cutout.png" alt="Groceries Bag">
        </div>
      </div>
    </div>
  </section>

  <!-- featured product section -->
  <section class="shop-section">
    <h2 class="title">SHOP BY TYPE</h2>
    <div class="card-container">

      <div class="card">
        <a href="category_list.php?category=butcher"><img src="../../image/meat.png" alt="Meats"></a>
        <a href="category_list.php?category=butcher"><div class="label">MEATS</div></a>
      </div>

      <div class="card">
        <a href="category_list.php?category=fishmonger"><img src="../../image/fish.avif" alt="Fish"></a>
        <a href="category_list.php?category=fishmonger"><div class="label">FISH</div></a>
      </div>

      <div class="card">
        <a href="category_list.php?category=greengrocer"><img src="../../image/green.avif" alt="Greens"></a>
        <a href="category_list.php?category=greengrocer"><div class="label">GREENS</div></a>
      </div>

      <div class="card">
        <a href="category_list.php?category=bakery"><img src="../../image/baked.avif" alt="Baked"></a>
        <a href="category_list.php?category=bakery"><div class="label">BAKED</div></a>
      </div>

      <div class="card">
        <a href="category_list.php?category=delicatessen"><img src="../../image/delicatessen.avif" alt="Delicatessen"></a>
        <a href="category_list.php?category=delicatessen"><div class="label">DELICATESSEN</div></a>
      </div>

    </div>
  </section>

  <!-- recomended items -->
  <section class="recommended-section">
    <h2 class="section-title">RECOMMENDED ITEMS</h2>
    <?php include_once "product_list_home.php";?>
  </section>
  <!-- FOOTER -->
  <?php
    include_once "../../components/footer.php";
  ?>
</body>
</html>