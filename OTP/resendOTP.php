<?php
require_once '../PHP/dbConnection.php';
require_once '../OTP/sendSms.php';

$phone = $_GET['phone'] ?? '';

if (!$phone) {
    die("Invalid request");
}

$database = new Database();
$conn = $database->getConnection();

$otp = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", time() + 300); // valid for 5 minutes

$stmt = $conn->prepare("UPDATE customers SET otp=?, otp_expiry=? WHERE contact_number=?");
$stmt->bind_param("sss", $otp, $expiry, $phone);
$stmt->execute();
$stmt->close();

sendSMS($phone, "Your new MilkVault verification code is: $otp");

echo "<script>alert('A new OTP has been sent!'); window.location.href='verifyOTP.php?phone=" . urlencode($phone) . "';</script>";
?>
