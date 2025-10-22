<?php
require_once '../PHP/dbConnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $username = $_POST['username'];
    $address = $_POST['address'];
    $contactNumber = $_POST['contactNumber'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Password match check
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Create database instance and get connection
    $database = new Database();
    $conn = $database->getConnection();

    // ✅ Check if username already exists
    $checkUsernameSql = "SELECT * FROM customers WHERE username = ?";
    $checkUsernameStmt = $conn->prepare($checkUsernameSql);
    $checkUsernameStmt->bind_param("s", $username);
    $checkUsernameStmt->execute();
    $usernameResult = $checkUsernameStmt->get_result();

    if ($usernameResult->num_rows > 0) {
        echo "<script>alert('This username is already taken. Please choose another one.'); window.history.back();</script>";
        $checkUsernameStmt->close();
        $database->closeConnection();
        exit();
    }

    $checkUsernameStmt->close();

    // ✅ Check if contact number already exists
    $checkContactSql = "SELECT * FROM customers WHERE contact_number = ?";
    $checkContactStmt = $conn->prepare($checkContactSql);
    $checkContactStmt->bind_param("s", $contactNumber);
    $checkContactStmt->execute();
    $contactResult = $checkContactStmt->get_result();

    if ($contactResult->num_rows > 0) {
        echo "<script>alert('This contact number is already registered. Please use a different one.'); window.history.back();</script>";
        $checkContactStmt->close();
        $database->closeConnection();
        exit();
    }

    $checkContactStmt->close();

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new customer
    $sql = "INSERT INTO customers (first_name, last_name, username, address, contact_number, password)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $firstName, $lastName, $username, $address, $contactNumber, $hashedPassword);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='../HTML/customerLogin.html';</script>";
    } else {
        echo "<script>alert('Database error occurred. Please try again later.'); window.history.back();</script>";
    }

    // Close connections
    $stmt->close();
    $database->closeConnection();
}
?>
