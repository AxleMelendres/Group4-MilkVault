<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../HTML/customerLogin.html");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dairy Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/customerDashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="brand-name">MilkVault</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="customerDashboard.html" class="nav-link active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="customerProducts.html" class="nav-link">
                    <i class="fas fa-bottle-water"></i> Products
                </a>
                <a href="customerOrders.html" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> My Orders
                </a>
                <a href="customerProfile.html" class="nav-link">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="../PHP/logout.php" class="nav-link logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-content">
                    <h2>Welcome, <?php echo $_SESSION['customer_name']; ?>!</h2>
                    <p class="header-subtitle">Here's your dairy dashboard</p>
                </div>
                <div class="header-profile">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="profile-avatar">
                </div>
            </header>

            <!-- Quick Stats -->
            <section class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Orders</p>
                        <h3 class="stat-value" id="totalOrders">0</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Pending Deliveries</p>
                        <h3 class="stat-value" id="pendingDeliveries">0</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Account Balance</p>
                        <h3 class="stat-value" id="accountBalance">$0.00</h3>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="actions-section">
                <h2 class="section-title">Quick Actions</h2>
                <div class="action-cards">
                    <a href="customerProducts.html" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3>Place Order</h3>
                        <p>Browse and order products</p>
                    </a>
                    <a href="customerOrders.html" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <h3>View Orders</h3>
                        <p>Check your order history</p>
                    </a>
                    <a href="customerProfile.html" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3>Edit Profile</h3>
                        <p>Update your information</p>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="customerDashboard.js"></script>
</body>
</html>
