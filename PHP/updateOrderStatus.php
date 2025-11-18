<?php
require_once 'dbConnection.php';

$database = new Database();
$conn = $database->getConnection();

// Get order ID from the QR code
$order_id_to_fetch = $_GET['order_id'] ?? null;
$message = "";
$order = null;
$order_items = [];

if ($order_id_to_fetch) {
    try {
        // ✅ Get current order status
        $stmt = $conn->prepare("SELECT status, customer_id, total_price, order_date FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id_to_fetch);
        $stmt->execute();
        $order_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order_data) {
            $message = "<div class='message error'>❌ Order not found.</div>";
        } elseif ($order_data['status'] !== 'Delivered') {
            // ✅ Update order status to Delivered
            $update = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE order_id = ?");
            $update->bind_param("i", $order_id_to_fetch);
            $update->execute();
            $update->close();
            $message = "<div class='message success'>✅ Order #$order_id_to_fetch has been marked as **Delivered**!</div>";
        } else {
            $message = "<div class='message info'>ℹ️ Order #$order_id_to_fetch is already Delivered.</div>";
        }

        // ✅ Fetch full order details
        $fetch = $conn->prepare("
            SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.customer_id
            WHERE o.order_id = ?
        ");
        $fetch->bind_param("i", $order_id_to_fetch);
        $fetch->execute();
        $order = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        // ✅ Fetch order items
        $details = $conn->prepare("
            SELECT od.product_name, od.quantity, od.sub_total
            FROM order_details od
            WHERE od.order_id = ?
        ");
        $details->bind_param("i", $order_id_to_fetch);
        $details->execute();
        $order_items = $details->get_result()->fetch_all(MYSQLI_ASSOC);
        $details->close();

    } catch (Exception $e) {
        $message = "<div class='message error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    $message = "<div class='message error'>❌ Invalid QR code. Please scan a valid order QR code.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Delivery Confirmation</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/updateOrderStatus.css">
</head>
<body>
<div class="container">
    <h1><span style="color:#f9a825;">📦</span> Delivery Status</h1>
    <?= $message ?>
    <?php if ($order): ?>
        <h2>Order #<?= htmlspecialchars($order['order_id']) ?></h2>
        <div class="order-details">
            <div class="detail-item">
                <strong>Customer Name:</strong>
                <span class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
            <div class="detail-item">
                <strong>Status:</strong>
                <span class="detail-value status-<?= strtolower(htmlspecialchars($order['status'])) ?>">
                    <?= htmlspecialchars($order['status']) ?>
                </span>
            </div>
            <div class="detail-item">
                <strong>Order Date:</strong>
                <span class="detail-value"><?= htmlspecialchars(date('M d, Y', strtotime($order['order_date']))) ?></span>
            </div>
        </div>

        <h3>🛒 Items Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grandTotal = 0;
                foreach ($order_items as $item): 
                    $grandTotal += $item['sub_total'];
                ?>
                <tr>
                    <td data-label="Product Name"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td data-label="Quantity"><?= htmlspecialchars($item['quantity']) ?></td>
                    <td data-label="Subtotal">₱<?= number_format($item['sub_total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total-container">
            <span class="total-label">Grand Total:</span>
            <span class="total">₱<?= number_format($grandTotal, 2) ?></span>
        </div>
    <?php endif; ?>
    <p class="footer-text">Thank you for confirming the successful delivery of this order.</p>
</div>
</body>
</html>