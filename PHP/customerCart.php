<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../HTML/customerLogin.html");
    exit();
}

// Include query file and DB connection
require_once '../PHP/dbConnection.php';
require_once __DIR__ . '/customerQuery.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

$customer_id = $_SESSION['customer_id'];
$cartItems = getCustomerCart($conn, $customer_id);

// Compute total amount
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += isset($item['total']) ? (float)$item['total'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - MilkVault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6B4A88;
            --primary-light: #8B6BA3;
            --cream: #F5E6D3;
            --mint: #A8D5BA;
            --white: #FFFFFF;
            --gray-light: #F8F7F5;
            --gray-dark: #4A4A4A;
            --border-color: #E8DCC8;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--gray-light) 0%, var(--cream) 100%);
            min-height: 100vh;
            color: var(--gray-dark);
        }

        .cart-header {
            background: var(--white);
            padding: 25px 0;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .cart-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-title i {
            font-size: 2.2rem;
        }

        .cart-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .cart-items-section {
            background: var(--white);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
        }

        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            align-items: center;
        }

        .cart-item:hover {
            box-shadow: 0 4px 12px rgba(107, 74, 136, 0.1);
            border-color: var(--primary);
        }

        .cart-item:last-child {
            margin-bottom: 0;
        }

        .product-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            background: var(--cream);
        }

        .product-details {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-dark);
            margin-bottom: 8px;
        }

        .product-info {
            display: flex;
            gap: 25px;
            font-size: 0.95rem;
            color: #666;
            flex-wrap: wrap;
        }

        .info-label {
            font-weight: 500;
            color: var(--primary);
            margin-right: 5px;
        }

        .quantity-display {
            display: inline-block;
            background: var(--cream);
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 600;
            color: var(--primary);
        }

        .item-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            min-width: 100px;
            text-align: right;
        }

        .delete-btn {
            background: #F8D7DA;
            border: none;
            color: #B91C1C;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .delete-btn:hover {
            background: #F5C2C7;
            transform: scale(1.05);
        }

        .delete-btn i {
            margin-right: 5px;
        }

        .summary-section {
            background: var(--white);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-top: 20px;
            padding-bottom: 0;
        }

        .summary-label {
            font-weight: 500;
            color: #666;
        }

        .summary-value {
            font-weight: 600;
            color: var(--gray-dark);
        }

        .summary-total {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 700;
        }

        .btn-primary-custom {
            background: var(--primary);
            border: none;
            color: var(--white);
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(107, 74, 136, 0.3);
            text-decoration: none;
            color: var(--white);
        }

        .btn-secondary-custom {
            background: var(--mint);
            border: none;
            color: var(--white);
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary-custom:hover {
            background: #95C9A6;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(168, 213, 186, 0.3);
            text-decoration: none;
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-cart-icon {
            font-size: 4rem;
            color: var(--primary);
            opacity: 0.3;
            margin-bottom: 20px;
        }

        .empty-cart-text {
            font-size: 1.2rem;
            color: #999;
            margin-bottom: 30px;
        }

        .delete-notification {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: #D4EDDA;
            border: 1px solid #C3E6CB;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .cart-title {
                font-size: 1.8rem;
            }

            .cart-item {
                flex-direction: column;
                text-align: center;
            }

            .product-info {
                justify-content: center;
            }

            .item-total {
                text-align: center;
            }

            .action-buttons {
                justify-content: center;
            }

            .product-img {
                width: 120px;
                height: 120px;
            }

            .cart-items-section,
            .summary-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="cart-header">
        <div class="cart-container">
            <h1 class="cart-title">
                <i class="fas fa-shopping-cart"></i>
                My Shopping Cart
            </h1>
        </div>
    </div>

    <div class="cart-container">
        <div class="delete-notification" id="deleteNotification">
            <i class="fas fa-check-circle"></i> Item removed from cart
        </div>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-items-section">
                <h3 style="color: var(--primary); margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-box"></i> Your Items (<?php echo count($cartItems); ?>)
                </h3>
                
                <?php foreach ($cartItems as $row): ?>
                    <div class="cart-item" data-cart-id="<?php echo htmlspecialchars($row['cart_id'] ?? ''); ?>">
                        <img src="<?php echo '../img/' . htmlspecialchars($row['image']); ?>" class="product-img" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                        
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                            <div class="product-info">
                                <div>
                                    <span class="info-label">Price:</span>
                                    ₱<?php echo number_format((float)$row['price'], 2); ?>
                                </div>
                                <div>
                                    <span class="info-label">Quantity:</span>
                                    <span class="quantity-display"><?php echo (int)$row['quantity']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="item-total">
                            ₱<?php echo number_format((float)$row['total'], 2); ?>
                        </div>

                        <button class="delete-btn" onclick="deleteCartItem(this, '<?php echo htmlspecialchars($row['cart_id'] ?? ''); ?>')">
                            <i class="fas fa-trash"></i>
                            <span class="d-none d-sm-inline">Remove</span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-section">
                <h3 style="color: var(--primary); margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-receipt"></i> Order Summary
                </h3>
                
                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">₱<?php echo number_format($totalAmount, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Shipping</span>
                    <span class="summary-value">Free</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label summary-total">Total Amount</span>
                    <span class="summary-value summary-total">₱<?php echo number_format($totalAmount, 2); ?></span>
                </div>

                <div class="action-buttons">
                    <a href="customerDashboard.php" class="btn-secondary-custom">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <form id="checkout-form" onsubmit="handleCheckout(event)" style="display: inline;">
                        <button type="submit" id="checkout-btn" class="btn-primary-custom" <?php echo empty($totalAmount) ? 'disabled' : ''; ?>>
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-items-section">
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <p class="empty-cart-text">Your cart is empty</p>
                    <p style="color: #999; margin-bottom: 30px;">Start adding your favorite dairy products now!</p>
                    <a href="customerDashboard.php" class="btn-secondary-custom">
                        <i class="fas fa-arrow-left"></i> Back to Store
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="../JS/customerCart.js"></script>
    <script>
        function deleteCartItem(button, cartId) {
            if (!cartId) {
                alert('Error: Invalid cart item');
                return;
            }

            if (confirm('Are you sure you want to remove this item from your cart?')) {
                const formData = new FormData();
                formData.append('cart_id', cartId);

                fetch('../PHP/deleteCartItem.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cartItem = button.closest('.cart-item');
                        cartItem.style.opacity = '0';
                        cartItem.style.transform = 'translateX(100px)';
                        
                        setTimeout(() => {
                            cartItem.remove();
                            showDeleteNotification();
                            
                            // Reload page after 1.5 seconds to update totals
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        }, 300);
                    } else {
                        alert('Error: ' + (data.message || 'Failed to remove item'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item from cart');
                });
            }
        }

        function showDeleteNotification() {
            const notification = document.getElementById('deleteNotification');
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
