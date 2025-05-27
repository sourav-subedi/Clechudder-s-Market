<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

// Handle OTP submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['otp']) || !is_array($_POST['otp'])) {
        $error = "Invalid OTP format.";
    } else {
        // Combine the 6 digits into one OTP string
        $entered_otp = implode("", array_map('trim', $_POST['otp']));

        if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry'])) {
            if (time() > $_SESSION['otp_expiry']) {
                $error = "OTP has expired. Please request a new one.";
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expiry']);
            } elseif ($entered_otp === $_SESSION['otp']) {
                $_SESSION['otp_verified'] = true;

                // Use hidden form to redirect to register_process.php with POST
                echo '<form id="otp-redirect" method="POST" action="register_process.php">';
                echo '<input type="hidden" name="otp_verified" value="true">';
                echo '</form>';
                echo '<script>document.getElementById("otp-redirect").submit();</script>';
                exit();
            } else {
                $error = "Invalid OTP. Please try again.";
            }
        } else {
            $error = "No OTP session found. Please request a new one.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/otp_verify_page.css">
</head>

<body>

    <div class="background">
        <div class="overlay"></div>
    </div>

    <div class="otp-container">
        <img src="../../image/otp-image.png" alt="OTP Image" class="otp-image">
        <h2>Verify Your Account</h2>
        <p>Enter the 6-digit OTP sent to your email.</p>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="otp_verify_page.php">
            <div class="otp-inputs">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" name="otp[]" maxlength="1" pattern="\d" required>
                <?php endfor; ?>
            </div>
            <button type="submit">Verify</button>
        </form>

        <p class="resend">Didn't receive the OTP? <a href="resend_otp.php">Resend OTP</a></p>
    </div>

    <script>
        const inputs = document.querySelectorAll(".otp-inputs input");

        inputs.forEach((input, index) => {
            input.addEventListener("input", (e) => {
                const value = e.target.value;
                if (value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener("keydown", (e) => {
                if (e.key === "Backspace" && index > 0 && !e.target.value) {
                    inputs[index - 1].focus();
                }
            });
        });

        inputs[0].focus(); // Auto-focus on first input
    </script>

</body>

</html>