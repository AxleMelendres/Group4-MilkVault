<?php
session_start();
require_once '../PHP/dbConnection.php';

if (!isset($_SESSION['verified_reset_phone'])) {
    die("Session expired. Please restart the reset process.");
}

$phone = $_SESSION['verified_reset_phone'];
$password = $_POST['password'];
$hashed = password_hash($password, PASSWORD_DEFAULT);

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("UPDATE customers SET password=? WHERE contact_number=?");
$stmt->bind_param("ss", $hashed, $phone);
$stmt->execute();
$stmt->close();

unset($_SESSION['verified_reset_phone']);

echo "<script>alert('Password reset successful! You can now log in.'); window.location.href='../HTML/customerLogin.html';</script>";
?>
