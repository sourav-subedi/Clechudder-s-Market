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
        <h2>GETTING STARTED</h2>
        <p>CREATE A NEW ACCOUNT</p>

        <form action="register_process.php" method="POST" onsubmit="return validatePassword()">
            <input type="text" id="fullname" name="fullname" placeholder="Full Name" required>

            <select name="category" class="custom-select" required>
                <option value="" disabled selected class="placeholder-option">Select a Category</option>
                <option value="butcher">Butcher</option>
                <option value="bakery">Bakery</option>
                <option value="fishmonger">Fishmonger</option>
                <option value="greengrocer">Greengrocer</option>
                <option value="delicatessen">Delicatessen</option>
            </select>

            <input type="email" id="email" name="email" placeholder="Someone@gmail.com" value="<?php echo htmlspecialchars($email); ?>" required>

            <div class="contact-container">
                <input type="text" class="contact-code" placeholder="+977" required readonly>
                <input type="tel" id="phone" name="phone" class="contact-input" placeholder="Enter Contact Number" required>
            </div>

            <p>First Shop Information</p>

            <!-- First Shop -->
            <input type="text" name="shop_name1" placeholder="First Shop Name" required>

            <!-- First Shop Contact & Email -->
            <input type="email" id="email1" name="email1" placeholder="First Shop Email" required>
            <div class="contact-container">
                <input type="text" class="contact-code" placeholder="+977" required readonly>
                <input type="tel" id="phone1" name="phone1" class="contact-input" placeholder="First Shop Contact Number" required>
            </div>

            <p>Second Shop Information</p>

            <!-- Second Shop -->
            <input type="text" name="shop_name2" placeholder="Second Shop Name" required>

            <!-- Second Shop Contact & Email -->
            <input type="email" id="email2" name="email2" placeholder="Second Shop Email" required>
            <div class="contact-container">
                <input type="text" class="contact-code" placeholder="+977" required readonly>
                <input type="tel" id="phone2" name="phone2" class="contact-input" placeholder="Second Shop Contact Number" required>
            </div>

            <!-- Common Password Section -->
            <div class="contact-container">
                <input type="password" id="password" name="password" placeholder="Enter a password"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                    title="Must be at least 8 characters long, Uppercase letters and numbers included"
                    required>
            </div>

            <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>

            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <span>I agree to the <a href="terms.php">Terms of Service and Privacy Policy</a>.</span>
            </div>

            <div>
                <button type="submit" name="signup">Create an Account</button>
            </div>

            <p>OR</p>
            <div class="social-icons">
                <img src="../../image/google.svg" width="10" height="10" alt="Google">
                <img src="../../image/apple.svg" alt="Apple">
                <img src="../../image/facebook.svg" alt="Facebook">
            </div>

            <p>Already have an account? <a href="login.php">Log in</a></p>
        </form>
    </div>
</body>

</html>