<?php
session_start();
$email = $_POST['email'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="stylesheet" href="../css/signup.css">
</head>
<body>
    <div class="background">
        <div class="overlay"></div>
    </div>
    <div class="signup-container">
        <div class="signup-side">
            <div class="logo">
                <!-- <img src="\image\logo.svg" alt="FresGrub Logo"> -->
            </div>
            <h2>GETTING STARTED</h2>
            <p>CREATE A NEW ACCOUNT</p>
            <form action="register_process.php" method="POST" onsubmit="return validatePassword()">

                <input type="text" id="fullname" name="fullname" placeholder="Full Name" required>

                <div class="contact-container">
                    <input type="text" class="contact-code" placeholder="+977" required readonly>
                    <input type="tel" id="phone" name="phone" class="contact-input" placeholder="Enter Contact Number" required>
                 </div>

                 <input type="email" id="email" name="email" placeholder="Someone@gmail.com" value="<?php echo htmlspecialchars($email); ?>" required>
                
                
                <div class="contact-container">
                    <input type="password" id="password" name="password" 
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                           title="Must be at least 8 characters long, Uppercase letters and numbers included" 
                           placeholder="Enter a Password"
                           required>
                </div>
                
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>

                
                <div class="terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <span>I agree to the<a href="terms.php"> Terms of Service and Privacy Policy</a>.</span> 
                </div>
                
                <div>
                    <button type="submit" name="signup">Create an Account</button>
                </div>
            
            <p>OR</p>
        <div class="social-icons">
            <img src="../../image/google.svg" width="10" height="10" alt="Google">
            <img src="../../image/apple.svg"  alt="Apple">
            <img src="../../image/facebook.svg" alt="Facebook">
        </div>

        <p>Already have an account? <a href="login.php">Log in</a></p>
    </form>
    </div>
</body>
</html>