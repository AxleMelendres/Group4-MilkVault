<?php
session_start();
require_once '../PHP/dbConnection.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if inputs are received
    if (empty($username) || empty($password)) {
        echo "<script>alert('Please enter username and password.'); window.location.href='../HTML/adminLogin.html';</script>";
        exit;
    }

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT admin_id, username, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // For plain-text passwords
        if ($password === $row['password']) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_username'] = $row['username'];
            header("Location: ../PHP/adminDashboard.php");
            exit;
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='../HTML/adminLogin.html';</script>";
        }
    } else {
        echo "<script>alert('Admin not found.'); window.location.href='../HTML/adminLogin.html';</script>";
    }

    $stmt->close();
    $db->closeConnection();
}
?>
