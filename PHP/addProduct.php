<?php
require_once '../PHP/dbConnection.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from form fields
    $product_name = $_POST['product_name'];
    $stock = $_POST['stock'];
    $low_level = $_POST['low_level'];
    $min_level = $_POST['min_level'];
    $price = $_POST['price'];
    $expiration_date = $_POST['expiration_date'];

    // Determine product status based on stock levels
    if ($stock == 0) {
        $status = "Out of Stock";
    } elseif ($stock <= $min_level) {
        $status = "Critical";
    } elseif ($stock <= $low_level) {
        $status = "Low Stock";
    } else {
        $status = "In Stock";
    }

    // Handle image upload
    $image_name = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../img/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    // Insert product into the database with status
    $stmt = $conn->prepare("INSERT INTO products (product_name, stock, low_level, min_level, price, expiration_date, image, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiidsss", $product_name, $stock, $low_level, $min_level, $price, $expiration_date, $image_name, $status);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Product added successfully!'); window.location.href='adminDashboard.php';</script>";
    } else {
        echo "<script>alert('❌ Error adding product: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $db->closeConnection();
}
?>
