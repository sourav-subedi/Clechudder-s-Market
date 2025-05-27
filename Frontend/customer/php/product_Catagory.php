<?php
session_start();
include "../../components/header.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shops</title>
    <link rel="stylesheet" href="../css/Product_Catagory.css" />
    <link rel="stylesheet" href="../css/homestyle.css" />
</head>

<body>
    <div class="background">
        <div class="overlay"></div>
    </div>
    <!-- HERO SECTION -->
    <div class="container">
        <div class="cards">
            <div class="card">
                <img src="../../image/meats.jpg" alt="Butcher">
                <h2>Butcher</h2>
                <p>Locally Sourced meats.</p>
                <a href="category_list.php?category=butcher"><button>View Products</button></a>
            </div>
            <div class="card">
                <img src="../../image/fruits-veg.jpg" alt="Greengrocer">
                <h2>Greengrocer</h2>
                <p>Fresh Green Vegetables.</p>
                <a href="category_list.php?category=greengrocer"><button>View Products</button></a>
            </div>
            <div class="card">
                <img src="../../image/fishmonger.jpg" alt="Fishmonger">
                <h2>Fishmonger</h2>
                <p>Locally raised fish.</p>
                <a href="category_list.php?category=fishmonger"><button>View Products</button></a>
            </div>
            <div class="card">
                <img src="../../image/bakery-products.jpg" alt="Bakery">
                <h2>Bakery</h2>
                <p>Freshly baked bread.</p>
                <a href="category_list.php?category=bakery"><button>View Products</button></a>
            </div>
            <div class="card">
                <img src="../../image/fresh produce.jpg" alt="Delicatessen">
                <h2>Delicatessen</h2>
                <p>Variety of Delicatessens.</p>
                <a href="category_list.php?category=delicatessen"><button>View Products</button></a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <?php
        include "../../components/footer.php";
    ?>
</body>

</html>