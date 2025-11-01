<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../PHP/customerLogin.html");
    exit();
}

require_once __DIR__ . '/customerQuery.php'; 

$customer_id = $_SESSION['customer_id'];
$orders = getCustomerOrders($conn, $customer_id); 

// ✅ Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Milk Vault</title>
    <link rel="stylesheet" href="../CSS/customerOrders.css">
</head>
<body>
    <div class="container">
        <a href="../PHP/customerDashboard.php" class="back-button">← Back to Home/Shop</a>
        <h1>Your Order History</h1>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="card-left">
                        <!-- Order ID and Date -->
                        <div class="order-header">
                            <h2><?php echo htmlspecialchars($order['product_name']); ?> × 
                            <?php echo htmlspecialchars($order['quantity']); ?>
                            </h2>
                            <p class="order-date">
                                Placed on: 
                                <?php echo date("F j, Y, g:i a", strtotime(htmlspecialchars($order['order_date']))); ?>
                            </p>
                        </div>
                        
                        <!-- Status Badge -->
                        <p>
                            <span class="status-badge status-<?php echo htmlspecialchars(strtolower($order['status'])); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </p>
                        
                        <!-- Total Price -->
                        <div class="order-total">
                            <p><strong>Total:</strong> ₱<?php echo number_format((float)$order['total_price'], 2); ?></p>
                        </div>
                    </div>

                    <div class="card-right">
                        <div class="product-preview-section">
                            <p class="preview-label">Product Preview</p>
                            <img src="<?php echo htmlspecialchars($order['product_image_path'] ?? '../IMAGES/placeholder.png'); ?>" 
                                 alt="Product Preview for Order #<?php echo htmlspecialchars($order['order_id']); ?>">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't placed any orders yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
