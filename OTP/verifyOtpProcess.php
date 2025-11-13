<?php
require_once '../PHP/dbConnection.php';

$phone = $_POST['phone'];
$otp = $_POST['otp'];

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT otp, otp_expiry FROM customers WHERE contact_number = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$stmt->bind_result($realOtp, $expiry);
$stmt->fetch();
$stmt->close();

if ($realOtp && $otp == $realOtp && strtotime($expiry) > time()) {
    $update = $conn->prepare("UPDATE customers SET otp=NULL, otp_expiry=NULL, is_verified=1 WHERE contact_number=?");
    $update->bind_param("s", $phone);
    $update->execute();
    $update->close();

    echo "<script>alert('Phone verified successfully! You can now log in.'); window.location.href='../HTML/customerLogin.html';</script>";
} else {
    echo "<script>alert('Invalid or expired OTP. Please try again.'); window.history.back();</script>";
}
?>
