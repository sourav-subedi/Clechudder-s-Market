<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once "verify-otp.php";

$email = $_POST['email'] ?? '';

echo ($email);

?>