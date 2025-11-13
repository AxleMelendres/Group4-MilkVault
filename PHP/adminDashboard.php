<?php
// adminDashboard.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../HTML/adminLogin.html");
    exit();
}

require_once '../PHP/dbConnection.php';
require_once '../PHP/adminQuery.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$summary = getSummaryData($conn);
$inventoryStmt = getInventory($conn);
$ordersStmt = getOrders($conn);
$usersStmt = getUsers($conn);
$alertsStmt = getAlerts($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/adminDashboard.css">
</head>
<body data-admin-id="<?= htmlspecialchars($_SESSION['admin_id']) ?>">
<div class="d-flex">
    <nav class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-chart-line"></i> MILKVAULT ADMIN PANEL</h3>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="#" data-section="overview"><i class="fas fa-home"></i> Overview</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-section="inventory"><i class="fas fa-boxes"></i> Inventory</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-section="orders"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-section="users"><i class="fas fa-users"></i> Users</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-section="messages"><i class="fas fa-comments"></i> Messages</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-section="alerts"><i class="fas fa-exclamation-triangle"></i> Low Stock</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <header class="header d-flex justify-content-between align-items-center">
            <h2 id="page-title">Dashboard Overview</h2>
            <div class="d-flex align-items-center">
                <div class="clock" id="clock">00:00:00</div>
                <div class="user-profile ms-3"><span>Admin</span></div>
            </div>
        </header>

        <!-- OVERVIEW SECTION -->
        <div class="content">
<section id="overview" class="section active">
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="summary-card card-orders">
                <div class="card-icon"><i class="fas fa-receipt"></i></div>
                <div class="card-content">
                    <h6>Total Orders</h6>
                    <h3><?= $summary['totalOrders'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="summary-card card-customers">
                <div class="card-icon"><i class="fas fa-users"></i></div>
                <div class="card-content">
                    <h6>Total Customers</h6>
                    <h3><?= $summary['totalCustomers'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="summary-card card-products">
                <div class="card-icon"><i class="fas fa-bottle-water"></i></div>
                <div class="card-content">
                    <h6>Products in Stock</h6>
                    <h3><?= $summary['totalProducts'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="summary-card card-sales">
                <div class="card-icon"><i class="fas fa-peso-sign"></i></div>
                <div class="card-content">
                    <h6>Total Sales</h6>
                    <h3>₱<?= number_format($summary['totalSales'], 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

   <!-- Low Stock & Near Expiry Alerts -->
<div class="card alert-card">
    <div class="card-header">
        <h5><i class="fas fa-exclamation-circle"></i> Product Alerts</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Min. Level</th>
                        <th>Expiration Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="alert-table">
                <?php 
                $today = new DateTime();
                $alerts = [];

                // Gather all alert rows
                while ($row = $alertsStmt->fetch_assoc()) {
                    $statuses = [];
                    $badgePriority = 999; // Used to pick the most urgent badge color

                    // Check expiration
                    if (!empty($row['expiration_date']) && $row['expiration_date'] !== '0000-00-00') {
                        $expiryDate = new DateTime($row['expiration_date']);
                        $diffDays = (int)$today->diff($expiryDate)->format('%r%a');
                        $isExpired = $expiryDate < $today;
                        $isNearExpiry = (!$isExpired && $diffDays <= 5);

                        if ($isExpired) {
                            $statuses[] = "Expired";
                            $badgePriority = min($badgePriority, 1);
                        } elseif ($isNearExpiry) {
                            $statuses[] = "Near Expiry";
                            $badgePriority = min($badgePriority, 2);
                        }
                    }

                    // Check stock levels
                    if ($row['stock'] == 0) {
                        $statuses[] = "Out of Stock";
                        $badgePriority = min($badgePriority, 1);
                    } elseif ($row['stock'] <= $row['min_level']) {
                        $statuses[] = "Critical Stock";
                        $badgePriority = min($badgePriority, 1);
                    } elseif ($row['stock'] > $row['min_level'] && $row['stock'] <= ($row['min_level'] + 5)) {
                        $statuses[] = "Low Stock";
                        $badgePriority = min($badgePriority, 3);
                    }

                    // Skip items with no alerts
                    if (empty($statuses)) continue;

                    // Combine statuses
                    $row['status'] = implode(', ', $statuses);

                    // Assign badge color based on priority
                    switch ($badgePriority) {
                        case 1:
                            $row['badge'] = 'bg-danger';
                            $row['priority'] = 1;
                            break;
                        case 2:
                            $row['badge'] = 'bg-warning text-dark';
                            $row['priority'] = 2;
                            break;
                        case 3:
                            $row['badge'] = 'bg-warning text-dark';
                            $row['priority'] = 3;
                            break;
                        default:
                            $row['badge'] = 'bg-success';
                            $row['priority'] = 4;
                    }

                    $alerts[] = $row;
                }

                // Sort by priority (1 = highest urgency)
                usort($alerts, fn($a, $b) => $a['priority'] <=> $b['priority']);

                // Display sorted alerts
                foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= htmlspecialchars($alert['product_name']) ?></td>
                        <td><?= htmlspecialchars($alert['stock']) ?></td>
                        <td><?= htmlspecialchars($alert['min_level']) ?></td>
                        <td><?= !empty($alert['expiration_date']) && $alert['expiration_date'] !== '0000-00-00' 
                                ? htmlspecialchars($alert['expiration_date']) 
                                : '<span class="text-muted">N/A</span>' ?></td>
                        <td><span class="badge <?= $alert['badge'] ?>"><?= htmlspecialchars($alert['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>
</div>
</section>


            <!-- Inventory Section -->
            <section id="inventory" class="section">

            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Add Product
            </button>

             <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content p-4">
                <h5 class="mb-3">Add New Product</h5>
                <form action="../PHP/addProduct.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label>Product Name</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Stock</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Low Level</label>
                        <input type="number" name="low_level" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Min Level</label>
                        <input type="number" name="min_level" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Expiration Date</label>
                        <input type="date" name="expiration_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Product Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
            </div>
        </div>
    </div>                           

                <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Inventory Management</h5>
        <input type="text" class="form-control search-box" placeholder="Search products..." id="inventory-search">
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Expiration Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="inventory-table">
                <?php 
                $today = new DateTime();
                while ($product = $inventoryStmt->fetch_assoc()): 
                    // compute expiry info safely (handle empty expiration_date)
                    $isExpired = false;
                    $isNearExpiry = false;
                    if (!empty($product['expiration_date']) && $product['expiration_date'] !== '0000-00-00') {
                        $expiryDate = new DateTime($product['expiration_date']);
                        $diffDays = (int)$today->diff($expiryDate)->format('%r%a'); // signed days
                        $isExpired = $expiryDate < $today;
                        $isNearExpiry = (!$isExpired && $diffDays <= 5);
                    }

                    // Build an array of statuses so we can show multiple labels (e.g. "Low Stock, Near Expiry")
                    $statuses = [];

                    // Expiry statuses first
                    if ($isExpired) {
                        $statuses[] = 'Expired';
                    } elseif ($isNearExpiry) {
                        $statuses[] = 'Near Expiry';
                    }

                    // Stock statuses
                    if ((int)$product['stock'] === 0) {
                        $statuses[] = 'Out of Stock';
                    } elseif ((int)$product['stock'] <= (int)$product['min_level']) {
                        $statuses[] = 'Critical';
                    } elseif ((int)$product['stock'] > (int)$product['min_level'] && (int)$product['stock'] <= ((int)$product['min_level'] + 5)) {
                        $statuses[] = 'Low Stock';
                    } else {
                        $statuses[] = 'In Stock';
                    }

                    // Combined status text
                    $statusText = implode(', ', $statuses);

                    // Pick badge color by priority (most urgent shown)
                    if (in_array('Expired', $statuses)) {
                        $badge = 'bg-secondary';
                    } elseif (in_array('Out of Stock', $statuses) || in_array('Critical', $statuses)) {
                        $badge = 'bg-danger';
                    } elseif (in_array('Near Expiry', $statuses) || in_array('Low Stock', $statuses)) {
                        $badge = 'bg-warning text-dark';
                    } else {
                        $badge = 'bg-success';
                    }
                ?>
                <tr>
                    <td>#<?= htmlspecialchars($product['product_id']) ?></td>

                    <!-- Image column -->
                    <td>
                        <?php if (!empty($product['image'])): ?>
                            <img src="../img/<?= htmlspecialchars($product['image']) ?>" 
                                alt="<?= htmlspecialchars($product['product_name']) ?>"
                                style="width:50px;height:50px;object-fit:cover;border-radius:5px;">
                        <?php else: ?>
                            <span class="text-muted">No Image</span>
                        <?php endif; ?>
                    </td>

                    <!-- Name -->
                    <td><?= htmlspecialchars($product['product_name']) ?></td>

                    <!-- Stock -->
                    <td><?= (int)$product['stock'] ?></td>

                    <!-- Price -->
                    <td>₱<?= number_format((float)$product['price'], 2) ?></td>

                    <!-- Expiration Date -->
                    <td><?= !empty($product['expiration_date']) && $product['expiration_date'] !== '0000-00-00' ? htmlspecialchars($product['expiration_date']) : '<span class="text-muted">N/A</span>' ?></td>

                    <!-- Status -->
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($statusText) ?></span></td>


                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $product['product_id'] ?>">Edit</button>
                        <a href="../PHP/deleteProduct.php?id=<?= $product['product_id'] ?>" 
                        class="btn btn-sm btn-danger" 
                        onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                    </td>
                </tr>

                <!-- Edit Product Modal -->
                <div class="modal fade" id="editProductModal<?= $product['product_id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content p-4">
                            <h5 class="mb-3">Edit Product</h5>
                            <form action="../PHP/editProduct.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

                                <div class="mb-3">
                                    <label>Product Name</label>
                                    <input type="text" name="product_name" class="form-control" 
                                        value="<?= htmlspecialchars($product['product_name']) ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col mb-3">
                                        <label>Stock</label>
                                        <input type="number" name="stock" class="form-control" value="<?= (int)$product['stock'] ?>" required>
                                    </div>
                                    <div class="col mb-3">
                                        <label>Low Level</label>
                                        <input type="number" name="low_level" class="form-control" value="<?= (int)$product['low_level'] ?>" required>
                                    </div>
                                    <div class="col mb-3">
                                        <label>Min Level</label>
                                        <input type="number" name="min_level" class="form-control" value="<?= (int)$product['min_level'] ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Price</label>
                                    <input type="number" step="0.01" name="price" class="form-control" 
                                        value="<?= htmlspecialchars($product['price']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label>Expiration Date</label>
                                    <input type="date" name="expiration_date" class="form-control" 
                                        value="<?= htmlspecialchars($product['expiration_date']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label>Replace Image (optional)</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>

                                <button type="submit" class="btn btn-success">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>

            <!-- Orders Section -->
            <section id="orders" class="section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Order Tracking</h5>
                        <input type="text" class="form-control search-box" placeholder="Search orders..." id="orders-search">
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>Image</th><th>Product ID</th><th>Name</th><th>Stock</th><th>Price</th><th>Status</th><th>Action</th></tr>
                                </thead>
                                <tbody id="orders-table">
                                <?php while($order = $ordersStmt->fetch_assoc()) :  
                                    $statusBadge = match($order['status']) {
                                        'Delivered' => 'bg-success',
                                        'Processing' => 'bg-primary',
                                        'Pending' => 'bg-warning',
                                        default => 'bg-secondary'
                                    }; ?>
                                    <tr>
                                        <td><?php if (!empty($product['image'])): ?>
                                        <img src="../img/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['product_name']); ?>" width="60" height="60" style="object-fit:cover; border-radius:8px;">
                                        <?php else: ?>
                                        <span class="text-muted">No image</span>
                                        <?php endif; ?></td>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td><?= $order['customer_name'] ?></td>
                                        <td><?= $order['order_date'] ?></td>
                                        <td>₱<?= number_format($order['total_amount'],2) ?></td>
                                        <td><span class="badge <?= $statusBadge ?>"><?= $order['status'] ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#qrModal<?= $order['order_id'] ?>">
                                                <i class="fas fa-qrcode"></i> QR Code
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- QR Modal -->
                                    <div class="modal fade" id="qrModal<?= $order['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-center p-4">
                                                <h5 class="mb-3">QR Code for Order #<?= $order['order_id'] ?></h5>
                                                <?php 
                                                // Build the same URL used in customerOrders.php
                                                $qrData = "http://192.168.1.4/MILKVAULTFP/PHP/updateOrderStatus.php?order_id=" . urlencode($order['order_id']);
                                                ?>
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($qrData) ?>" 
                                                    alt="QR Code for Order #<?= htmlspecialchars($order['order_id']) ?>" 
                                                    class="mb-3">
                                                <p class="text-muted small">Scan to verify order details.</p>
                                                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Users Section -->
            <section id="users" class="section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>User Management</h5>
                        <input type="text" class="form-control search-box" placeholder="Search users..." id="users-search">
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <thead>
                                        <tr><th>User ID</th><th>Name</th><th>Address</th><th>Phone</th><th>Status</th><th>Action</th></tr>
                                    </thead>

                                </thead>
                                <tbody id="users-table">
                                <?php while($user = $usersStmt->fetch_assoc()) :?>
                                    <tr>
                                        <td>#<?= $user['user_id'] ?></td>
                                        <td><?= $user['name'] ?></td>
                                        <td><?= $user['address'] ?></td>
                                        <td><?= $user['phone'] ?></td>
                                        <td><span class="badge bg-success"><?= $user['status'] ?></span></td>
                                        <td><button class="btn btn-sm btn-warning">Edit</button></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Messages Section -->
            <section id="messages" class="section" style="display:none;">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Customer Conversations</h5>
                        <button class="btn btn-sm btn-outline-secondary" id="refreshThreads">
                            <i class="fas fa-rotate"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="row g-0" style="min-height: 460px;">
                            <div class="col-md-4 border-end">
                                <div class="list-group list-group-flush" id="chatThreadList" style="max-height:460px;overflow-y:auto;">
                                    <div class="list-group-item text-center text-muted py-4">
                                        No conversations yet.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8 d-flex flex-column">
                                <div class="border-bottom px-4 py-3">
                                    <h6 class="mb-0" id="activeChatTitle">Select a customer</h6>
                                    <small class="text-muted" id="activeChatSubtitle"></small>
                                </div>
                                <div class="flex-grow-1 overflow-auto p-3" id="adminChatBox">
                                    <div class="text-center text-muted mt-5">
                                        Choose a conversation to start chatting.
                                    </div>
                                </div>
                                <div class="border-top p-3">
                                    <div class="input-group">
                                        <input type="hidden" id="activeCustomerIdTracker" value="0">
                                        <textarea class="form-control" id="adminMessageInput" placeholder="Type a message..." rows="1" style="resize:none;"></textarea>
                                        <button type="button" class="btn btn-primary" id="adminSendMessage">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../JS/adminDashboard.js"></script>
<script src="../JS/adminChat.js"></script>
</body>
</html>
