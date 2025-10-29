<?php
require_once 'dbConnection.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['product_id']);
    $name = $_POST['product_name'];
    $stock = intval($_POST['stock']);
    $low_level = intval($_POST['low_level']);
    $min_level = intval($_POST['min_level']);
    $price = floatval($_POST['price']);
    $expiration_date = $_POST['expiration_date'];

    // Determine product status
    if ($stock <= $min_level) {
        $status = "Critical";
    } elseif ($stock <= $low_level) {
        $status = "Low Stock";
    } else {
        $status = "In Stock";
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "../img/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

        $stmt = $conn->prepare("UPDATE products 
            SET product_name=?, stock=?, low_level=?, min_level=?, price=?, expiration_date=?, image=?, status=? 
            WHERE product_id=?");
        $stmt->bind_param("siiidsssi", $name, $stock, $low_level, $min_level, $price, $expiration_date, $image_name, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products 
            SET product_name=?, stock=?, low_level=?, min_level=?, price=?, expiration_date=?, status=? 
            WHERE product_id=?");
        $stmt->bind_param("siiidssi", $name, $stock, $low_level, $min_level, $price, $expiration_date, $status, $id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('✅ Product updated successfully!'); window.location.href='adminDashboard.php';</script>";
    } else {
        echo "<script>alert('❌ Error updating product: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
