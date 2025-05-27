<?php
// Check if a session is already active before starting one
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../Backend/connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../loginRegister/php/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

if (!$conn) {
    echo "Database connection failed.";
    exit();
}

// Fetch user data
$sql = "SELECT full_name, email, role, created_date, status FROM users WHERE user_id = :user_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":user_id", $user_id);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    echo "Error fetching user: " . $e['message'];
    exit();
}

$user = oci_fetch_assoc($stid);
oci_free_statement($stid);

if (!$user) {
    echo "User not found.";
    exit();
}

$homePage = ($user["ROLE"] === "trader") ? "../../loginRegister/php/trader_dashboard.php" : "home.php";

// Include sidebar after all PHP processing but before HTML output
include_once "sidebar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Trader Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #cad9e0 0%, #b8ced6 100%);
            min-height: 100vh;
            display: block !important;
            align-items: initial !important;
        }

        .sidebar {
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            height: 100% !important;
            z-index: 999 !important;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .background {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(135deg, #cad9e0 0%, #b8ced6 50%, #a4bcc4 100%);
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 20%, rgba(43, 122, 120, 0.1) 0%, transparent 50%);
        }

        .profile-container {
            max-width: 900px;
            width: 100%;
            margin-top: 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .profile-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2b7a78;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-subtitle {
            font-size: 1.1rem;
            color: #5a6c72;
            font-weight: 400;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 
                0 20px 40px rgba(43, 122, 120, 0.1),
                0 1px 0px rgba(255, 255, 255, 0.8) inset;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2b7a78 0%, #3f9d9a 50%, #f5a04e 100%);
        }

        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 25px 50px rgba(43, 122, 120, 0.15),
                0 1px 0px rgba(255, 255, 255, 0.8) inset;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #2b7a78 0%, #3f9d9a 100%);
            border-radius: 50%;
            margin: 0 auto 30px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(43, 122, 120, 0.3);
            position: relative;
        }

        .profile-avatar::after {
            content: '';
            position: absolute;
            inset: -3px;
            background: linear-gradient(45deg, #f5a04e, #2b7a78, #f5a04e);
            border-radius: 50%;
            z-index: -1;
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .profile-info {
            text-align: center;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 600;
            color: #2b7a78;
            margin-bottom: 8px;
        }

        .profile-role {
            display: inline-block;
            background: linear-gradient(135deg, #f5a04e 0%, #e8941f 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(245, 160, 78, 0.3);
        }

        .profile-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(43, 122, 120, 0.1);
        }

        .detail-item {
            text-align: left;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #5a6c72;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #2b7a78;
            font-weight: 600;
            word-break: break-all;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-active {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(43, 122, 120, 0.1);
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2b7a78 0%, #3f9d9a 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(43, 122, 120, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(43, 122, 120, 0.4);
            background: linear-gradient(135deg, #3f9d9a 0%, #2b7a78 100%);
        }

        .btn-secondary {
            background: rgba(43, 122, 120, 0.1);
            color: #2b7a78;
            border: 2px solid rgba(43, 122, 120, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(43, 122, 120, 0.2);
            border-color: rgba(43, 122, 120, 0.3);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .profile-card {
                padding: 30px 20px;
            }

            .profile-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .profile-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }

            .profile-name {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="background">
        <div class="overlay"></div>
    </div>

    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <h1 class="profile-title">Trader Profile</h1>
                <p class="profile-subtitle">Manage your account information and settings</p>
            </div>

            <div class="profile-card">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['FULL_NAME'], 0, 1)); ?>
                </div>

                <div class="profile-info">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['FULL_NAME']); ?></h2>
                    <span class="profile-role"><?php echo ucfirst(htmlspecialchars($user['ROLE'])); ?></span>

                    <div class="profile-details">
                        <div class="detail-item">
                            <div class="detail-label">Email Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['EMAIL']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Account Status</div>
                            <div class="detail-value">
                                <span class="status-badge <?php echo strtolower($user['STATUS']) === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo htmlspecialchars($user['STATUS']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Member Since</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($user['CREATED_DATE'])); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Account Type</div>
                            <div class="detail-value"><?php echo ucfirst(htmlspecialchars($user['ROLE'])); ?> Account</div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="edit_trader_profile.php" class="btn btn-primary">
                            <span>✏</span>
                            Edit Profile
                        </a>
                        <a href="trader_dashboard.php" class="btn btn-secondary">
                            <span>←</span>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>