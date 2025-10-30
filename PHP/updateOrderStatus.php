<?php
// updateOrderStatus.php
// This script is accessed via the QR code (scanned by the customer) and automatically changes the order status to 'Delivered'.

// Include database connection
include 'dbConnection.php';
$database = new Database();
$conn = $database->getConnection();

$message = "";
$order = null;
$order_id_to_fetch = $_GET['order_id'] ?? null;
$new_status_on_scan = 'Delivered'; // The final status to automatically set upon customer scanning

// --- 1. Process Automatic Status Update (GET) ---
if ($order_id_to_fetch && $_SERVER["REQUEST_METHOD"] == "GET") {
    
    try {
        // First, check the current status to prevent unnecessary updates
        $check_query = "SELECT status FROM orders WHERE order_id = ?";
        $stmt_check = $conn->prepare($check_query);
        $stmt_check->bind_param("i", $order_id_to_fetch);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $order_data = $result_check->fetch_assoc();
        $current_status = $order_data['status'] ?? null;
        $stmt_check->close();

        // Only update if the current status is NOT 'Delivered' or 'Cancelled'
        if ($current_status && $current_status !== 'Delivered' && $current_status !== 'Cancelled') {
            // Update the order status
            $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("si", $new_status_on_scan, $order_id_to_fetch);
            
            if ($stmt_update->execute()) {
                $message = "<div class='message success'>✅ Order #{$order_id_to_fetch} has been successfully marked as '{$new_status_on_scan}'. Thank you for your purchase!</div>";
            } else {
                $message = "<div class='message error'>❌ Error updating status for Order #{$order_id_to_fetch}. Please contact support.</div>";
            }
            $stmt_update->close();
        } elseif ($current_status == 'Delivered') {
             $message = "<div class='message info'>ℹ️ Order #{$order_id_to_fetch} is already marked as 'Delivered'.</div>";
        } elseif ($current_status) {
             $message = "<div class='message info'>ℹ️ Order #{$order_id_to_fetch} is currently marked as '{$current_status}'. No action taken.</div>";
        } else {
             $message = "<div class='message error'>❌ Order ID #{$order_id_to_fetch} not found in the system.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='message error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} elseif (!$order_id_to_fetch) {
    $message = "<div class='message error'>❌ Invalid access. Please scan a valid Order QR code.</div>";
}

// --- 2. Fetch Order Details (Final Display) ---
if ($order_id_to_fetch) {
    // Fetch the updated (or current) state of the order for display
    $fetch_query = "SELECT order_id, customer_id, total_price, status, order_date FROM orders WHERE order_id = ?";
    $stmt = $conn->prepare($fetch_query);
    $stmt->bind_param("i", $order_id_to_fetch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc(); 
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Updated</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; text-align: center; }
        .container { 
            max-width: 600px; 
            margin: 50px auto; 
            background-color: #fff; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2); 
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        h1 { color: #1e88e5; margin-bottom: 25px; border-bottom: 3px solid #e0f7fa; padding-bottom: 10px; font-size: 1.8em; }
        
        .order-details { 
            margin-top: 20px;
            padding: 20px; 
            border: 1px solid #b3e5fc; 
            border-radius: 8px; 
            background-color: #e3f2fd;
            text-align: left;
        }
        .order-details p { margin: 8px 0; font-size: 1.1em; color: #333; }
        .order-details strong { display: inline-block; width: 140px; color: #0288d1; font-weight: 600; }
        .status-Current { 
            font-weight: bold; 
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .message { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: bold; text-align: left; transition: all 0.3s ease; }
        .success { background-color: #e8f5e9; color: #2e7d32; border-color: #a5d6a7; box-shadow: 0 2px 5px rgba(46, 125, 50, 0.15); }
        .error { background-color: #ffebee; color: #c62828; border-color: #ef9a9a; box-shadow: 0 2px 5px rgba(198, 40, 40, 0.15); }
        .info { background-color: #fffde7; color: #ff8f00; border-color: #ffee58; box-shadow: 0 2px 5px rgba(255, 143, 0, 0.15); }

        /* Conditional status styling */
        .status-Pending { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .status-Processing { background-color: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .status-Delivered { background-color: rgba(40, 167, 69, 0.1); color: #28a745; }
        .status-Cancelled { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }

    </style>
</head>
<body>
    <div class="container">
        <h1>Order Delivery Confirmation</h1>

        <?php echo $message; // Display final status message ?>

        <?php if ($order): ?>
            <div class="order-details">
                <h2>Order #<?php echo htmlspecialchars($order['order_id']); ?></h2>
                <p><strong>Customer ID:</strong> <?php echo htmlspecialchars($order['customer_id']); ?></p>
                <p><strong>Order Date:</strong> <?php echo date("F j, Y, g:i a", strtotime(htmlspecialchars($order['order_date']))); ?></p>
                <p><strong>Total Price:</strong> $<?php echo number_format(htmlspecialchars($order['total_price']), 2); ?></p>
                <p><strong>Final Status:</strong> 
                    <span class="status-Current status-<?php echo htmlspecialchars($order['status']); ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </p>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 30px; color: #666;">This transaction confirms receipt of the order.</p>

    </div>
</body>
</html>
