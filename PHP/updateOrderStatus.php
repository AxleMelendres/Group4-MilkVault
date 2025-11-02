<?php
include 'dbConnection.php';
$database = new Database();
$conn = $database->getConnection();

$order_id_to_fetch = $_GET['order_id'] ?? null;
$message = "";
$order = null;
$order_items = [];

if ($order_id_to_fetch) {
    try {
        // ✅ Get current status
        $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id_to_fetch);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_data = $result->fetch_assoc();
        $stmt->close();

        if (!$order_data) {
            $message = "<div class='message error'>❌ Order not found.</div>";
        } elseif ($order_data['status'] !== 'Delivered') {
            $update = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE order_id = ?");
            $update->bind_param("i", $order_id_to_fetch);
            $update->execute();
            $update->close();
            $message = "<div class='message success'>✅ Order #$order_id_to_fetch has been marked as Delivered!</div>";
        } else {
            $message = "<div class='message info'>ℹ️ Order #$order_id_to_fetch is already Delivered.</div>";
        }

        // ✅ Fetch updated order with full customer name using CONCAT
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

        // ✅ Fetch order items (product name + quantity + total)
        $details = $conn->prepare("
            SELECT p.product_name, od.quantity, (p.price * od.quantity) AS total
            FROM order_details od
            JOIN products p ON od.product_id = p.product_id
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
    $message = "<div class='message error'>❌ Invalid access. Please scan a valid Order QR code.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivery Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f8; text-align: center; margin: 0; padding: 40px; }
        .container { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 550px; margin: auto; padding: 30px; }
        h1 { color: #1976d2; margin-bottom: 20px; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background-color: #e8f5e9; color: #2e7d32; }
        .error { background-color: #ffebee; color: #c62828; }
        .info { background-color: #fffde7; color: #ff8f00; }
        .order-details { text-align: left; margin-top: 20px; }
        .order-details p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f0f0f0; }
        .total { font-weight: bold; text-align: right; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Order Delivery Confirmation</h1>
        <?= $message ?>
        <?php if ($order): ?>
            <div class="order-details">
                <p><strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
                <p><strong>Order Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
            </div>

            <h3>🛒 Ordered Items</h3>
            <table>
                <tr>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
                <?php 
                $grandTotal = 0;
                foreach ($order_items as $item): 
                    $grandTotal += $item['total'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td>₱<?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <p class="total">Total Price: ₱<?= number_format($grandTotal, 2) ?></p>

        <?php endif; ?>
        <p style="margin-top:25px;color:#777;">This transaction confirms receipt of your order.</p>
    </div>
</body>
</html>
