<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>
    <link rel="stylesheet" href="../css/homestyle.css">
    <style>
        .cancel-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cancel-icon {
            color: #dc3545;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .cancel-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-icon">✕</div>
        <div class="cancel-message">Your payment has been cancelled. You can try again or choose a different payment method.</div>
        <a href="../php/shopping_cart.php" class="back-btn">Back to Shopping Cart</a>
    </div>
</body>
</html> 