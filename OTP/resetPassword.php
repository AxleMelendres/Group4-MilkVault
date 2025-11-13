<?php
session_start();
if (!isset($_SESSION['verified_reset_phone'])) {
    die("Session expired. Please restart the process.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MilkVault</title>
    <link rel="stylesheet" href="../CSS/customerRegistration.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Reset Your Password</h2>
            <form action="resetPasswordSave.php" method="POST">
                <div class="form-group mb-3">
                    <label>New Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-auth w-100">Save Password</button>
            </form>
        </div>
    </div>
</body>
</html>
