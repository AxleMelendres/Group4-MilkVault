<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(["success" => false, "message" => "Authentication failed. Please log in again."]);
    exit();
}

require_once 'dbConnection.php';
require_once 'qrlib/qrlib.php';

$database = new Database();
$conn = $database->getConnection();
$customer_id = $_SESSION['customer_id'];

// IMPORTANT: Replace this with your current LAN IP (same Wi-Fi as phone)
$base_url = 'http://192.168.1.4/MILKVAULTFP/PHP/';

$conn->begin_transaction();

try {
    $totalPrice = 0;
    $cartItems = [];

    // GET CART ITEMS
    $stmt = $conn->prepare("
        SELECT c.product_id, c.quantity, c.price, p.product_name, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.customer_id = ?
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Your cart is empty. Cannot process order.");
    }

    while ($row = $result->fetch_assoc()) {
        if ($row['quantity'] > $row['stock']) {
            throw new Exception("Insufficient stock for " . htmlspecialchars($row['product_name']));
        }
        $cartItems[] = $row;
        $totalPrice += ($row['price'] * $row['quantity']);
    }
    $stmt->close();

    // INSERT INTO THE ORDER TABLE
    $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, total_price, status) VALUES (?, ?, 'Pending')");
    $order_stmt->bind_param("id", $customer_id, $totalPrice);
    $order_stmt->execute();
    $order_id = $conn->insert_id;
    $order_stmt->close();

    // INSERT ORDER DETAILS & UPDATE PRODUCT STOCKS
    $detail_stmt = $conn->prepare("
        INSERT INTO order_details (order_id, product_id, product_name, quantity, unit_price, sub_total)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");

    foreach ($cartItems as $item) {
        $subTotal = $item['price'] * $item['quantity'];

        $detail_stmt->bind_param(
            "iisidd",
            $order_id,
            $item['product_id'],
            $item['product_name'],
            $item['quantity'],
            $item['price'],
            $subTotal
        );
        $detail_stmt->execute();

        $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $stock_stmt->execute();
    }
    $detail_stmt->close();
    $stock_stmt->close();

// GENERATE QR COEDE (AUTO LINK TO DELIVERY CONFIRMATION)
$qrDir = '../qr_codes/';
if (!is_dir($qrDir)) mkdir($qrDir, 0755, true);

// CREATE FULL LINK FOR QR CODE
$qrData = $base_url . 'updateOrderStatus.php?order_id=' . urlencode($order_id);


// File name and path
$qrFileName = 'qr_' . $order_id . '.png';
$qrFilePath = $qrDir . $qrFileName;

// Double-check value before QR generation
if (empty($qrData) || strpos($qrData, 'updateOrderStatus.php') === false) {
    throw new Exception("QR data not valid. Value: " . $qrData);
}

// Generate the QR image
QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 10);

// Save path to database
$updateQR = $conn->prepare("UPDATE orders SET qr_path = ? WHERE order_id = ?");
$updateQR->bind_param("si", $qrFilePath, $order_id);
$updateQR->execute();
$updateQR->close();


    // CLEAR CART
    $clearCart = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
    $clearCart->bind_param("i", $customer_id);
    $clearCart->execute();
    $clearCart->close();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Order $order_id placed successfully!",
        "order_id" => $order_id,
        "qr_path" => $qrFilePath
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
