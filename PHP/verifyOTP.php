<?php
$phone = $_GET['phone'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP - MilkVault</title>
    <link rel="stylesheet" href="../CSS/customerRegistration.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Phone Verification</h2>
            <p>We’ve sent a 6-digit code to your phone number.</p>
            <form action="verifyOtpProcess.php" method="POST">
                <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">
                <label>Enter OTP:</label>
                <input type="text" name="otp" required>
                <button type="submit" class="btn btn-auth">Verify</button>
            </form>

            <br>
            <a href="resendOTP.php?phone=<?= urlencode($phone) ?>">Resend OTP</a>
        </div>
    </div>
</body>
</html>
