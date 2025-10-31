<?php
// Start the session to get the customer_id
session_start();

// Ensure the customer is logged in
if (!isset($_SESSION['customer_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Include database connection
include 'dbConnection.php';
$database = new Database();
$conn = $database->getConnection();
$customer_id = $_SESSION['customer_id'];

// Query to fetch all orders for the current customer
$orders_query = "
    SELECT 
        order_id, 
        total_price, 
        status, 
        qr_path, 
        order_date 
    FROM orders 
    WHERE customer_id = ?
    ORDER BY order_date DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Milk Vault</title>
    <!-- Basic styling for readability -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .order-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; background-color: #fafafa; }
        .order-info h2 { margin: 0 0 5px 0; color: #007bff; }
        .order-info p { margin: 0; font-size: 0.9em; }
        .status-badge { padding: 5px 10px; border-radius: 12px; font-weight: bold; color: #fff; }
        .status-Pending { background-color: #ffc107; color: #333; }
        .status-Processing { background-color: #17a2b8; }
        .status-Shipped { background-color: #28a745; }
        .status-Delivered { background-color: #007bff; }
        .qr-section { text-align: right; }
        .qr-section img { max-width: 100px; height: auto; border: 1px solid #ccc; padding: 5px; background-color: #fff; }
        .back-button { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">← Back to Home/Shop</a>
        <h1>Your Order History</h1>

        <?php if ($orders_result->num_rows > 0): ?>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-info">
                        <h2>Order #<?php echo htmlspecialchars($order['order_id']); ?></h2>
                        <p><strong>Date:</strong> <?php echo date("F j, Y, g:i a", strtotime(htmlspecialchars($order['order_date']))); ?></p>
                        <p><strong>Total:</strong> $<?php echo number_format(htmlspecialchars($order['total_price']), 2); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span></p>
                    </div>
                    <div class="qr-section">
                        <p>QR Code for Status Update:</p>
                        <img src="<?php echo htmlspecialchars($order['qr_path']); ?>" alt="QR Code for Order #<?php echo htmlspecialchars($order['order_id']); ?>">
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't placed any orders yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
