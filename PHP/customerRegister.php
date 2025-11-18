<?php
require_once '../PHP/dbConnection.php';
require_once '../OTP/sendSms.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $username = $_POST['username'];
    $address = $_POST['address'];
    $contactNumber = $_POST['contactNumber'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Check username uniqueness
    $checkUsername = $conn->prepare("SELECT * FROM customers WHERE username = ?");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $userResult = $checkUsername->get_result();
    if ($userResult->num_rows > 0) {
        echo "<script>alert('Username already taken.'); window.history.back();</script>";
        exit();
    }
    $checkUsername->close();

    // Check contact number uniqueness
    $checkContact = $conn->prepare("SELECT * FROM customers WHERE contact_number = ?");
    $checkContact->bind_param("s", $contactNumber);
    $checkContact->execute();
    $contactResult = $checkContact->get_result();
    if ($contactResult->num_rows > 0) {
        echo "<script>alert('Contact number already registered.'); window.history.back();</script>";
        exit();
    }
    $checkContact->close();

    // Prepare OTP
    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", time() + 300); // 5 min expiry
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert customer (unverified)
    $insert = $conn->prepare("INSERT INTO customers 
        (first_name, last_name, username, address, contact_number, password, otp, otp_expiry, is_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $insert->bind_param("ssssssss", $firstName, $lastName, $username, $address, $contactNumber, $hashedPassword, $otp, $expiry);

    if ($insert->execute()) {
        // Send OTP via SMS
        sendSMS($contactNumber, "Your MilkVault verification code is: $otp");

        // Redirect to verification page
        header("Location: ../OTP/verifyOTP.php?phone=" . urlencode($contactNumber));
        exit();
    } else {
        echo "<script>alert('Registration failed. Please try again.'); window.history.back();</script>";
    }

    $insert->close();
    $database->closeConnection();
}
?>
