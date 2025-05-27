<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select User Type</title>
    <link rel="stylesheet" href="../css/user_selection.css">
</head>

<body>

    <div class="background">
        <div class="overlay"></div>
    </div>

    <div class="selection-container">
        <h2>SELECT ACCOUNT TYPE</h2>
        <p>Choose how you want to register</p>

        <form action="redirect.php" method="post">
            <div class="radio-group">
                <label>
                    <input type="radio" name="user_type" value="customer" required>
                    <span class="radio-label">Customer</span>
                </label>
                <label>
                    <input type="radio" name="user_type" value="trader" required>
                    <span class="radio-label">Trader</span>
                </label>
            </div>

            <button type="submit" class="submit-btn">OK</button>
        </form>

        <p>Already have an account? <a href="login.php">Log In</a></p>
    </div>

</body>

</html>