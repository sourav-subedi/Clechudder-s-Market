<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../../loginRegister/php/login.php");
    exit();
}

// Check if user is a trader
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'trader') {
    // Redirect to unauthorized page or homepage
    header("Location: ../../unauthorized.php");
    exit();
}
?>