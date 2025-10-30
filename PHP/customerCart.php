<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../HTML/customerLogin.html");
    exit();
}

include '../PHP/dbConnection.php';
$database = new Database();
$conn = $database->getConnection();

$customer_id = $_SESSION['customer_id'];

$query = "
    SELECT c.cart_id, p.product_name, p.image, c.quantity, p.price, (c.quantity * p.price) AS total
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$totalAmount = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart - MilkVault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .cart-container { max-width: 900px; margin: 50px auto; }
        .cart-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); }
        .product-img { width: 80px; border-radius: 8px; }
        .total-row { font-weight: bold; font-size: 1.2em; }
        /* Style for the notification pop-up */
        #cart-notification {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050; /* Above Bootstrap modals */
            padding: 15px;
            text-align: center;
            color: white;
            font-weight: bold;
            display: none; /* Initially hidden */
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <!-- Notification Message Area -->
    <div id="cart-notification" class="alert alert-dismissible fade show" role="alert">
        <!-- Message content will be inserted here by JavaScript -->
    </div>
    
    <div class="cart-container">
        <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> My Cart</h2>

        <div class="cart-card">
            <?php if ($result->num_rows > 0): ?>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><img src="../img/<?php echo htmlspecialchars($row['image']); ?>" class="product-img"></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td>₱<?php echo number_format($row['price'], 2); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>₱<?php echo number_format($row['total'], 2); ?></td>
                            </tr>
                            <?php $totalAmount += $row['total']; ?>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="text-end">Total Amount:</td>
                            <td>₱<?php echo number_format($totalAmount, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="text-end mt-3">
                    <a href="customerDashboard.php" class="btn btn-secondary">Continue Shopping</a>
                    
                    <!-- Checkout Form to trigger JS function -->
                    <form id="checkout-form" onsubmit="handleCheckout(event)" style="display: inline;">
                        <button type="submit" id="checkout-btn" class="btn btn-success" <?php echo empty($totalAmount) ? 'disabled' : ''; ?>>
                            Checkout
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <p class="text-center text-muted"><i class="fas fa-inbox"></i> Your cart is empty.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- External JavaScript file for cart functionality -->
    <script src="../js/customerCart.js"></script>
</body>
</html>
