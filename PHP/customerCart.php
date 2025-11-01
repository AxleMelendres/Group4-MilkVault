<?php
// PHP/customerCart.php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../HTML/customerLogin.html");
    exit();
}

// include the query file (path relative to this file)
require_once __DIR__ . '/customerQuery.php';

$customer_id = $_SESSION['customer_id'];
$cartItems = getCustomerCart($conn, $customer_id);

// Compute total amount
$totalAmount = 0;
foreach ($cartItems as $item) {
    // ensure numeric
    $totalAmount += isset($item['total']) ? (float)$item['total'] : 0;
}

// Close the connection (optional)
$conn->close();
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
        #cart-notification {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            padding: 15px;
            text-align: center;
            color: white;
            font-weight: bold;
            display: none;
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <div id="cart-notification" class="alert alert-dismissible fade show" role="alert"></div>

    <div class="cart-container">
        <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> My Cart</h2>

        <div class="cart-card">
            <?php if (!empty($cartItems)): ?>
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
                        <?php foreach ($cartItems as $row): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo '../img/' . htmlspecialchars($row['image']); ?>" class="product-img" alt="">
                                </td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td>₱<?php echo number_format((float)$row['price'], 2); ?></td>
                                <td><?php echo (int)$row['quantity']; ?></td>
                                <td>₱<?php echo number_format((float)$row['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
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

    <script src="../js/customerCart.js"></script>
</body>
</html>
