<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST['user_type'];
    
    // Store the user type in session
    $_SESSION['user_role'] = $user_type;

    if ($user_type == "customer") {
        header("Location: Customer_sign_up.php");
        exit();
    } elseif ($user_type == "trader") {
        header("Location: Trader_sign_up.php");
        exit();
    } else {
        die("Invalid selection!");
    }
}
?>