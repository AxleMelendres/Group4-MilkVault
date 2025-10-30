<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../HTML/customerLogin.html");
    exit();
}

include '../PHP/dbConnection.php';

// ✅ Create a Database instance and get the connection
$database = new Database();
$conn = $database->getConnection();

$products = [];
try {
    // ✅ Added `image` field here
    $query = "SELECT product_id, product_name, price, stock, image FROM products WHERE stock > 0 ORDER BY product_name ASC";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
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
    <link rel="stylesheet" href="../CSS/customerDashboarddd.css">
</head>
<body>
    <!-- 
        CRITICAL FIX: HTML ELEMENT FOR NOTIFICATION ADDED HERE 
        This is necessary for customerDashboard.js to work without errors.
    -->
    <div id="cart-notification-message" style="
        display: none; 
        position: fixed; 
        top: 20px; 
        right: 20px; 
        padding: 15px 25px; 
        border-radius: 8px; 
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000; /* Increased Z-index to ensure it is always on top */
        font-weight: bold;
        transition: opacity 0.5s ease;
        text-align: center;
        min-width: 250px;
        ">
        Item added successfully!
    </div>
    <!-- END NOTIFICATION ELEMENT -->


    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="brand-name">MilkVault</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="customerDashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="customerProducts.php" class="nav-link">
                    <i class="fas fa-bottle-water"></i> Products
                </a>
                <a href="../PHP/customerCart.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> My Cart
                </a>
                <a href="customerProfile.php" class="nav-link">
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
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['customer_name']); ?>!</h2>
                    <p class="header-subtitle">Here's your dairy dashboard</p>
                </div>
                <div class="header-profile">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="profile-avatar">
                </div>
            </header>

            <!-- ✅ Featured Products Section with Image -->
            <section class="featured-products-section">
                <div class="section-header">
                    <h2 class="section-title">Featured Products</h2>
                    <p class="section-subtitle">Browse our fresh dairy products</p>
                </div>

                <?php if (!empty($products)): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <!-- Product Image block (Your compact design changes should be applied in CSS) -->
                                <div class="product-image">
                                    <?php
                                    $imagePath = "../img/" . htmlspecialchars($product['image']);
                                    if (!empty($product['image']) && file_exists($imagePath)): ?>
                                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="img-fluid">
                                    <?php else: ?>
                                        <img src="https://placehold.co/100x100/eeeeee/343a40?text=No+Image" alt="No Image" class="img-fluid">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    <!-- Price Display (Adjusted for better visibility in compact mode) -->
                                    <div class="price-container">
                                        <span class="current-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                    </div>
                                    <div class="product-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Stock:</span>
                                            <span class="stock-badge <?php echo $product['stock'] > 10 ? 'in-stock' : 'low-stock'; ?>">
                                                <?php echo $product['stock']; ?> STOCKS
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-actions">
                                    <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-inbox"></i>
                        <p>No products available at the moment</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/customerDashboard.js"></script>
</body>
</html>
