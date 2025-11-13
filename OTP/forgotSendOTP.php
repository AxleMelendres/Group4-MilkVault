<?php
session_start();
require_once '../PHP/dbConnection.php';
require_once '../OTP/sendSms.php';

if (!isset($_POST['contactNumber'])) {
    die("Phone number required.");
}

$phone = $_POST['contactNumber'];

$database = new Database();
$conn = $database->getConnection();

// Check if phone exists
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE contact_number = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "<script>alert('This phone number is not registered.'); window.history.back();</script>";
    exit();
}
$stmt->close();

// Generate OTP
$otp = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", time() + 300); // valid for 5 minutes

// Store OTP
$update = $conn->prepare("UPDATE customers SET otp=?, otp_expiry=? WHERE contact_number=?");
$update->bind_param("sss", $otp, $expiry, $phone);
$update->execute();
$update->close();

// Send via SMS
sendSMS($phone, "Your MilkVault password reset code is: $otp");

// Store phone in session
$_SESSION['reset_phone'] = $phone;

// Redirect to verification page
header("Location: ../OTP/forgotVerify.php");
exit();
?>
