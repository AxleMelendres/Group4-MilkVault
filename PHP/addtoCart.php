<?php
session_start();
header("Content-Type: application/json");

// Database connection
require_once '../PHP/dbConnection.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to add items to cart.'
    ]);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get JSON input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !isset($data['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Product ID missing.'
    ]);
    exit();
}

$product_id  = intval($data['product_id']);
$customer_id = intval($_SESSION['customer_id']);

// Check if product exists
$checkProduct = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
$checkProduct->bind_param("i", $product_id);
$checkProduct->execute();
$result = $checkProduct->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found.'
    ]);
    exit();
}

$product = $result->fetch_assoc();
$price = $product['price'];

// Check if already in cart
$checkCart = $conn->prepare("SELECT cart_id FROM cart WHERE customer_id = ? AND product_id = ?");
$checkCart->bind_param("ii", $customer_id, $product_id);
$checkCart->execute();
$cartResult = $checkCart->get_result();

if ($cartResult->num_rows > 0) {
    // Update quantity
    $update = $conn->prepare(
        "UPDATE cart SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?"
    );
    $update->bind_param("ii", $customer_id, $product_id);
    $update->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Item quantity updated.'
    ]);
    exit();
}

// Add new item
$insert = $conn->prepare(
    "INSERT INTO cart (customer_id, product_id, quantity, price) VALUES (?, ?, 1, ?)"
);
$insert->bind_param("iid", $customer_id, $product_id, $price);
$insert->execute();

echo json_encode([
    'success' => true,
    'message' => 'Item added to cart.'
]);
exit();
?>
