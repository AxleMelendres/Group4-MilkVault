<?php
require_once '../PHP/dbConnection.php';

$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete product image (optional)
    $result = $conn->query("SELECT image FROM products WHERE product_id='$id'");
    if ($result && $row = $result->fetch_assoc()) {
        $image_path = "../img/" . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    $conn->query("DELETE FROM products WHERE product_id='$id'");
    echo "<script>alert('🗑 Product deleted successfully!'); window.location.href='adminDashboard.php';</script>";
}

$db->closeConnection();
?>
