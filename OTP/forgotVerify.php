<?php
session_start();
if (!isset($_SESSION['reset_phone'])) {
    die("Session expired. Please restart the process.");
}
$phone = $_SESSION['reset_phone'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - MilkVault</title>
    <link rel="stylesheet" href="../CSS/customerRegistration.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Verify Reset Code</h2>
            <form action="forgotProcess.php" method="POST">
                <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">
                <div class="form-group mb-3">
                    <label for="otp">Enter OTP</label>
                    <input type="text" id="otp" name="otp" placeholder="6-digit code" required>
                </div>
                <button type="submit" class="btn btn-auth w-100">Verify</button>
            </form>
            <br>
            <a href="resendOTP.php?phone=<?= urlencode($phone) ?>">Resend Code</a>
        </div>
    </div>
</body>
</html>
