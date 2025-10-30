<?php
session_start();
include '../PHP/dbConnection.php';

// 🧠 Debug: log session status (you can remove later)
error_log("Customer session ID: " . ($_SESSION['customer_id'] ?? 'none'));

// ✅ Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['product_id'];
$customer_id = $_SESSION['customer_id'];

// ✅ Check if product exists
$checkProduct = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
$checkProduct->bind_param("i", $product_id);
$checkProduct->execute();
$productResult = $checkProduct->get_result();

if ($productResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$product = $productResult->fetch_assoc();
$price = $product['price'];

// ✅ Check if already in cart
$checkCart = $conn->prepare("SELECT * FROM cart WHERE customer_id = ? AND product_id = ?");
$checkCart->bind_param("ii", $customer_id, $product_id);
$checkCart->execute();
$cartResult = $checkCart->get_result();

if ($cartResult->num_rows > 0) {
    // Update quantity if already in cart
    $updateCart = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?");
    $updateCart->bind_param("ii", $customer_id, $product_id);
    $updateCart->execute();
    echo json_encode(['success' => true, 'message' => 'Quantity updated']);
} else {
    // Add new item
    $insertCart = $conn->prepare("INSERT INTO cart (customer_id, product_id, quantity, price) VALUES (?, ?, 1, ?)");
    $insertCart->bind_param("iid", $customer_id, $product_id, $price);
    $insertCart->execute();
    echo json_encode(['success' => true, 'message' => 'Item added']);
}
?>
