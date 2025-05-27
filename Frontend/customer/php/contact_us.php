<?php
session_start();
include "../../../Backend/connect.php";
include "../../components/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="../css/homestyle.css">
    <link rel="stylesheet" href="../css/contact_us.css">
    
</head>
<body>

    <div class="background">
        <div class="overlay"></div>
    </div>
    <div class="contact-card">
        <h1 class="contact-title">Contact Us</h1>
        
        <form>
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Adress</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="message">Your Message</label>
                <textarea id="message" name="message" required></textarea>
            </div>
            
            <button type="submit" class="send-button">SEND YOUR MESSAGE</button>
        </form>
        
        <div class="contact-info">
            <a href="tel:+94498123456">+944 98123456</a>
            <span class="or-text">OR</span>
            <a href="mailto:hudders@gmail.com">hudders@gmail.com</a>
        </div>
    </div>
</body>
  <!-- FOOTER -->
  <?php
    include "../../components/footer.php";
  ?>
</html>