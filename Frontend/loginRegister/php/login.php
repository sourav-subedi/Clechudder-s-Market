<?php
session_start();
include "../../../Backend/connect.php";

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        $conn = getDBConnection();

        if ($conn) {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":email", $email);

            if (oci_execute($stmt)) {
                $user = oci_fetch_array($stmt, OCI_ASSOC);

                if ($user) {
                    if ($user['STATUS'] === 'pending') {
                        $error_msg = "Please wait for admin approval before logging in.";
                    } elseif (password_verify($password, $user['PASSWORD'])) {
                        // Store session data
                        $_SESSION['user_id'] = $user['USER_ID'];
                        $_SESSION['role'] = $user['ROLE'];

                        // Redirect based on role
                        switch ($user['ROLE']) {
                            case 'admin':
                                header("Location: ../../admin/php/admindashboard.php");
                                break;
                            case 'trader':
                                header("Location: ../../trader/php/trader_dashboard.php");
                                break;
                            case 'customer':
                                header("Location: ../../customer/php/home.php");
                                break;
                            default:
                                $error_msg = "Unknown user role.";
                                break;
                        }
                        exit();
                    } else {
                        $error_msg = "Invalid email or password.";
                    }
                } else {
                    $error_msg = "Invalid email or password.";
                }
            } else {
                $e = oci_error($stmt);
                $error_msg = "Query failed: " . $e['message'];
            }

            oci_free_statement($stmt);
            oci_close($conn);
        } else {
            $error_msg = "Database connection failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>

<div class="background">
    <div class="overlay"></div>
</div>

<div class="login-container">
    <h2>LOGIN</h2>
    <p>LOG IN TO YOUR ACCOUNT</p>

    <?php if (!empty($error_msg)): ?>
        <div style="color: red; text-align:center; margin-bottom: 10px;">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>

        <div class="terms">
            <input type="checkbox" id="keepLoggedIn">
            <span for="keepLoggedIn">Keep me Logged In</span>
            <div class="forgotpw">
                <a href="#">Forgot Password</a>
            </div>
        </div>

        <button type="submit">Log In</button>
    </form>

    <p>OR</p>
    <div class="social-icons">
        <img src="../../image/google.svg" width="20" height="20" alt="Google">
        <img src="../../image/apple.svg" width="20" height="20" alt="Apple">
        <img src="../../image/facebook.svg" width="20" height="20" alt="Facebook">
    </div>

    <p>Don't have an account yet? <a href="user_selection.php">Sign up</a></p>
</div>

</body>
</html>
