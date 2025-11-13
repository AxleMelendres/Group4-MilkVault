<?php
session_start();
require_once '../PHP/dbConnection.php';

if (!isset($_POST['phone'], $_POST['otp'])) {
    die("Invalid request.");
}

$phone = $_POST['phone'];
$otp = $_POST['otp'];

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT otp, otp_expiry FROM customers WHERE contact_number=?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$stmt->bind_result($realOtp, $expiry);
$stmt->fetch();
$stmt->close();

if ($realOtp && $otp === $realOtp && strtotime($expiry) > time()) {
    // Clear OTP and allow reset
    $update = $conn->prepare("UPDATE customers SET otp=NULL, otp_expiry=NULL WHERE contact_number=?");
    $update->bind_param("s", $phone);
    $update->execute();
    $update->close();

    $_SESSION['verified_reset_phone'] = $phone;
    header("Location: ../OTP/resetPassword.php");
    exit();
} else {
    echo "<script>alert('Invalid or expired OTP.'); window.history.back();</script>";
    exit();
}
?>
