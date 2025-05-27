<?php
session_start();
require_once "../../../Backend/connect.php";

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: include ../../loginRegister/php/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    $conn = getDBConnection();

    // Fetch current user data
    $stmt = oci_parse($conn, "SELECT user_id, full_name, email, password FROM users WHERE user_id = :user_id");
    oci_bind_by_name($stmt, ":user_id", $user_id);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        // Basic validation
        if (empty($full_name) || empty($email)) {
            throw new Exception("Name and email are required fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check for duplicate email
        $checkEmail = oci_parse($conn, "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
        oci_bind_by_name($checkEmail, ":email", $email);
        oci_bind_by_name($checkEmail, ":user_id", $user_id);
        oci_execute($checkEmail);
        if (oci_fetch_array($checkEmail, OCI_ASSOC)) {
            throw new Exception("This email is already registered.");
        }

        // Begin update
        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception("Current password is required to change your password.");
            }

            if (!password_verify($current_password, $user['PASSWORD'])) {
                throw new Exception("Current password is incorrect.");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords don't match.");
            }

            if (strlen($new_password) < 8) {
                throw new Exception("Password must be at least 8 characters long.");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $updatePassword = oci_parse($conn, "UPDATE users SET password = :password WHERE user_id = :user_id");
            oci_bind_by_name($updatePassword, ":password", $hashed_password);
            oci_bind_by_name($updatePassword, ":user_id", $user_id);
            oci_execute($updatePassword);
        }

        // Update profile info
        $updateProfile = oci_parse($conn, "UPDATE users SET full_name = :full_name, email = :email WHERE user_id = :user_id");
        oci_bind_by_name($updateProfile, ":full_name", $full_name);
        oci_bind_by_name($updateProfile, ":email", $email);
        oci_bind_by_name($updateProfile, ":user_id", $user_id);
        oci_execute($updateProfile);

        $success = "Profile updated successfully!";

        // Refresh user data
        $stmt = oci_parse($conn, "SELECT full_name, email FROM users WHERE user_id = :user_id");
        oci_bind_by_name($stmt, ":user_id", $user_id);
        oci_execute($stmt);
        $refreshedUser = oci_fetch_assoc($stmt);
        $user = array_merge($user, $refreshedUser ?: []);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Shopiverse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/edit_profile.css">
    <link rel="stylesheet" href="../css/homestyle.css">
</head>

<body>
    <div class="background">
        <div class="overlay"></div>
    </div>
    <div class="edit-container">
        <div class="edit-card">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="profileForm">
                <div class="section-title"><i class="fas fa-user"></i> Basic Information</div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                        value="<?php echo isset($user['FULL_NAME']) ? htmlspecialchars($user['FULL_NAME']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?php echo isset($user['EMAIL']) ? htmlspecialchars($user['EMAIL']) : ''; ?>" required>
                </div>

                <div class="section-title"><i class="fas fa-lock"></i> Password Change</div>
                <p class="info-text">Leave blank to keep current password</p>

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control">
                    <span class="password-toggle" onclick="togglePassword('current_password', this)">
                        <i class="far fa-eye"></i> Show
                    </span>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                    <span class="password-toggle" onclick="togglePassword('new_password', this)">
                        <i class="far fa-eye"></i> Show
                    </span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    <span class="password-toggle" onclick="togglePassword('confirm_password', this)">
                        <i class="far fa-eye"></i> Show
                    </span>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="user_Profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, toggleElement) {
            const field = document.getElementById(fieldId);
            const icon = toggleElement.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
                toggleElement.innerHTML = '<i class="far fa-eye-slash"></i> Hide';
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
                toggleElement.innerHTML = '<i class="far fa-eye"></i> Show';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword && !document.getElementById('current_password').value) {
                alert('Please enter your current password to change your password.');
                e.preventDefault();
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                e.preventDefault();
                return;
            }

            if (newPassword && newPassword.length < 8) {
                alert('Password must be at least 8 characters long.');
                e.preventDefault();
                return;
            }
        });
    </script>

    <!-- FOOTER -->
    <?php
    include "../../components/footer.php";
    ?>
</body>

</html>