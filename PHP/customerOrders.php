<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../PHP/customerLogin.html");
    exit();
}

// Include DB connection and queries
require_once '../PHP/dbConnection.php';
require_once __DIR__ . '/customerQuery.php';

// Create DB connection
$db = new Database();
$conn = $db->getConnection();

$customer_id = $_SESSION['customer_id'];
$orders = getCustomerOrders($conn, $customer_id);

// Close connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Milk Vault</title>
    <!-- Added Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/customerOrders.css">
</head>
<body>
    <!-- Added elegant header with navigation -->
    <nav class="navbar navbar-light bg-light border-bottom">
        <div class="container-lg">
            <a href="../PHP/customerDashboard.php" class="btn btn-sm btn-outline-primary rounded-pill d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Shop</span>
            </a>
            <span class="text-muted small">Milk Vault 🥛</span>
        </div>
    </nav>

    <!-- Main content with Bootstrap grid layout -->
    <main class="py-5 py-md-6">
        <div class="container-lg">
            <!-- Page Title -->
            <div class="mb-5">
                <h1 class="display-5 fw-bold mb-2">Your Order History</h1>
                <p class="text-muted lead">Track your Milk Vault purchases and delivery status</p>
            </div>

            <?php if (!empty($orders)): ?>
                <!-- Grid layout for responsive order cards -->
                <div class="row g-4">
                    <?php foreach ($orders as $order): ?>
                        <div class="col-12">
                            <div class="order-card">
                                <div class="row g-0 align-items-center">
                                    <!-- Product Image -->
                                    <div class="col-md-3 col-lg-2">
                                        <div class="order-image-wrapper">
                                            <img src="<?php echo htmlspecialchars($order['product_image_path'] ?? '../IMAGES/placeholder.png'); ?>" 
                                                alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                                class="img-fluid rounded">
                                        </div>
                                    </div>
                                    
                                    <!-- Order Details -->
                                    <div class="col-md-6 col-lg-7">
                                        <div class="ps-3 ps-md-4">
                                            <div class="d-flex align-items-start gap-2 mb-2">
                                                <h5 class="mb-0 fw-600">
                                                    <?php echo htmlspecialchars($order['product_name']); ?>
                                                </h5>
                                                <span class="badge bg-light text-dark badge-quantity">
                                                    ×<?php echo htmlspecialchars($order['quantity']); ?>
                                                </span>
                                            </div>
                                            
                                            <p class="text-muted small mb-3">
                                                <i class="bi bi-calendar-event"></i>
                                                Ordered: <?php echo date("M d, Y", strtotime(htmlspecialchars($order['order_date']))); ?>
                                            </p>
                                            
                                            <!-- Status badge with color coding -->
                                            <div>
                                                <?php 
                                                    $status = htmlspecialchars(strtolower($order['status']));
                                                    $statusClass = match($status) {
                                                        'delivered' => 'status-delivered',
                                                        'pending' => 'status-pending',
                                                        'processing' => 'status-processing',
                                                        'cancelled' => 'status-cancelled',
                                                        default => 'status-pending'
                                                    };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <i class="bi bi-dot"></i> 
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Price and Actions -->
                                    <div class="col-md-3 col-lg-3 text-end">
                                        <div class="ps-3">
                                            <h5 class="fw-700 text-success mb-3">
                                                ₱<?php echo number_format((float)$order['total_price'], 2); ?>
                                            </h5>
                                            <button class="btn btn-sm btn-outline-primary rounded-pill" title="View Order Details">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty state design -->
                <div class="empty-state text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-bag-heart" style="font-size: 4rem; color: var(--color-accent);"></i>
                    </div>
                    <h4 class="fw-600 mb-2">No Orders Yet</h4>
                    <p class="text-muted mb-4">Start shopping and track your Milk Vault purchases here</p>
                    <a href="../PHP/customerDashboard.php" class="btn btn-primary rounded-pill">
                        <i class="bi bi-shop"></i> Continue Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light border-top mt-5 py-4">
        <div class="container-lg text-center text-muted small">
            <p class="mb-0">&copy; 2025 Milk Vault. All rights reserved.</p>
        </div>
    </footer>

    <!-- Added Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
