<?php
session_start();
require_once '../PHP/dbConnection.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare SQL (we only select needed columns)
    $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, username, password FROM customers WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // ✅ Verify hashed password
        if (password_verify($password, $row['password'])) {
            $_SESSION['customer_id'] = $row['customer_id'];
            $_SESSION['customer_username'] = $row['username'];
            $_SESSION['customer_name'] = $row['first_name'] . ' ' . $row['last_name'];

            // Debugging info (remove after testing)
            echo "<pre>Session Data:\n";
            print_r($_SESSION);
            echo "</pre>";

            // Redirect to dashboard
            header("Location: ../PHP/customerDashboard.php");
            exit;
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='../HTML/customerLogin.html';</script>";
        }
    } else {
        echo "<script>alert('Username not found.'); window.location.href='../HTML/customerLogin.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
