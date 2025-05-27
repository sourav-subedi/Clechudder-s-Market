<?php
session_start();
require_once "../../../Backend/connect.php";

// Debug logger
function debug_log($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, "../logs/register_debug.log");
}

debug_log("Registration process started");
debug_log("SESSION: " . print_r($_SESSION, true));
debug_log("POST: " . print_r($_POST, true));

if (!isset($_SESSION['user_role'])) {
    die("User role not set. Please start from the account selection page.");
}

$role = $_SESSION['user_role'];
$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed.");
}

if (isset($_POST['otp_verified']) && $_POST['otp_verified'] === "true" && isset($_SESSION['reg_data'])) {
    debug_log("OTP verified block entered");

    $data = $_SESSION['reg_data'];
    $status = ($role === 'trader') ? 'pending' : 'active';
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
    $user_id = null;

    try {
        // Check for duplicate email or contact_no
        $checkSQL = "SELECT COUNT(*) AS CNT FROM users WHERE email = :email OR contact_no = :contact_no";
        $checkStmt = oci_parse($conn, $checkSQL);
        oci_bind_by_name($checkStmt, ":email", $data['email']);
        oci_bind_by_name($checkStmt, ":contact_no", $data['contact_no']);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);
        if ($row['CNT'] > 0) {
            die("A user with this email or contact number already exists.");
        }

        // Insert new user
        $insertUserSQL = "
            INSERT INTO users (user_id, full_name, email, contact_no, password, role, status)
            VALUES (user_seq.NEXTVAL, :full_name, :email, :contact_no, :password, :role, :status)
            RETURNING user_id INTO :new_user_id
        ";

        $stmtUser = oci_parse($conn, $insertUserSQL);
        if (!$stmtUser) throw new Exception(oci_error($conn)['message']);

        oci_bind_by_name($stmtUser, ":full_name", $data['full_name']);
        oci_bind_by_name($stmtUser, ":email", $data['email']);
        oci_bind_by_name($stmtUser, ":contact_no", $data['contact_no']);
        oci_bind_by_name($stmtUser, ":password", $hashedPassword);
        oci_bind_by_name($stmtUser, ":role", $role);
        oci_bind_by_name($stmtUser, ":status", $status);
        oci_bind_by_name($stmtUser, ":new_user_id", $user_id, -1, SQLT_INT);

        if (!oci_execute($stmtUser, OCI_NO_AUTO_COMMIT)) {
            throw new Exception(oci_error($stmtUser)['message']);
        }

        debug_log("User inserted, ID: $user_id");

        if (!$user_id) {
            debug_log("Using fallback to get CURRVAL of user_seq");
            $stmtUserId = oci_parse($conn, "SELECT user_seq.CURRVAL AS user_id FROM dual");
            if (!oci_execute($stmtUserId)) throw new Exception(oci_error($stmtUserId)['message']);
            $row = oci_fetch_assoc($stmtUserId);
            if (!$row) throw new Exception("Failed to fetch CURRVAL");
            $user_id = $row['USER_ID'];
            debug_log("Fallback user_id: $user_id");
        }

        // Insert shops if trader
        if ($role === 'trader') {
            $insertShopQuery = "INSERT INTO shops (user_id, shop_name, shop_category, shop_email, contact_no)
                    VALUES (:user_id, :shop_name, :shop_category, :shop_email, :contact_no)";


            $stmtShop1 = oci_parse($conn, $insertShopQuery);
            oci_bind_by_name($stmtShop1, ":user_id", $user_id);
            oci_bind_by_name($stmtShop1, ":shop_category", $data['category']);
            oci_bind_by_name($stmtShop1, ":shop_name", $data['shop_name1']);
            oci_bind_by_name($stmtShop1, ":shop_email", $data['email1']);
            oci_bind_by_name($stmtShop1, ":contact_no", $data['phone1']);
            if (!oci_execute($stmtShop1, OCI_NO_AUTO_COMMIT)) {
                throw new Exception(oci_error($stmtShop1)['message']);
            }

            $stmtShop2 = oci_parse($conn, $insertShopQuery);
            oci_bind_by_name($stmtShop2, ":user_id", $user_id);
            oci_bind_by_name($stmtShop2, ":shop_category", $data['category']);
            oci_bind_by_name($stmtShop2, ":shop_name", $data['shop_name2']);
            oci_bind_by_name($stmtShop2, ":shop_email", $data['email2']);
            oci_bind_by_name($stmtShop2, ":contact_no", $data['phone2']);
            if (!oci_execute($stmtShop2, OCI_NO_AUTO_COMMIT)) {
                throw new Exception(oci_error($stmtShop2)['message']);
            }

            debug_log("Both shops inserted successfully");
        }

        if (!oci_commit($conn)) {
            throw new Exception("Commit failed: " . oci_error($conn)['message']);
        }

        debug_log("Transaction committed. Clearing session and redirecting.");

        unset($_SESSION['reg_data']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['user_role']);

        $msg = ($status === 'pending') ? "Registration pending admin approval." : "Registration successful! Please log in.";
        header("Location: login.php?message=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        oci_rollback($conn);
        debug_log("Registration failed: " . $e->getMessage());
        die("Registration failed: " . $e->getMessage());
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    debug_log("Form submitted, saving registration data and redirecting to OTP");

    // Validate password match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        die("Passwords do not match.");
    }

    $reg_data = [
        'full_name' => $_POST['fullname'],
        'email' => $_POST['email'],
        'contact_no' => $_POST['phone'],
        'password' => $_POST['password']
    ];

    if ($role === 'trader') {
        $reg_data['category'] = strtolower(trim($_POST['category']));
        $reg_data['shop_name1'] = $_POST['shop_name1'];
        $reg_data['email1'] = $_POST['email1'];
        $reg_data['phone1'] = $_POST['phone1'];
        $reg_data['shop_name2'] = $_POST['shop_name2'];
        $reg_data['email2'] = $_POST['email2'];
        $reg_data['phone2'] = $_POST['phone2'];
    }

    $_SESSION['reg_data'] = $reg_data;
    $_SESSION['user_email'] = $_POST['email'];

    header("Location: verify-otp.php?email=" . urlencode($_POST['email']));
    exit();
} else {
    debug_log("Invalid request received");
    die("Invalid request.");
}
