<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

if (isset($_GET['email']) && !empty($_GET['email'])) {
    $email = $_GET['email'];
    $otp = generateOTP();

    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300;


    $mail = new PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'utkristashrestha984@gmail.com';
        $mail->Password = 'smvt hrux xvhu hgmr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('utkristashrestha984@gmail.com', 'CheckHudders Market');
        $mail->addAddress($email);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Your OTP code is: $otp";

        $mail->send();
        
    // Ensuring correct redirection
    header("Location: otp_verify_page.php");
    exit();
} 
catch (Exception $e) {
   die("Mailer Error: " . $mail->ErrorInfo);
}
}
?>