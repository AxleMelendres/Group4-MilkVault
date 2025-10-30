<?php
// Set headers to ensure JSON response
header('Content-Type: application/json');

// Start the session and check for customer ID
session_start();
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(["success" => false, "message" => "Authentication failed. Please log in again."]);
    exit();
}

// --- Prerequisites and Setup ---

// Check if the QR code library is available
$qrlibPath = __DIR__ . '/qrlib/qrlib.php'; 
if (!file_exists($qrlibPath)) {
    $qrlibPath = __DIR__ . '/qrlib/qrcode.php'; 
    if (!file_exists($qrlibPath)) {
        echo json_encode(["success" => false, "message" => "Server configuration error: QR Code library files are missing. Please ensure the QR library folder is named 'qrlib' and is located inside the 'PHP' folder."]);
        exit();
    }
}
require_once $qrlibPath;


// Include database connection
include 'dbConnection.php';

$database = new Database();
$conn = $database->getConnection();
$customer_id = $_SESSION['customer_id'];

// ⚠️ IMPORTANT: Base URL must be correct for the QR code to function on scanning!
$base_url = 'http://192.168.1.4/MILKVAULTFP/PHP/'; // REPLACE 192.168.1.100 with YOUR PC's IPv4 address
// NOTE: I've updated the base URL to match your typical project structure (MILKVAULTFP)

// --- Function to Ensure Schema is Correct (Guarantees qr_path column exists) ---
function ensureQrPathColumnExists($conn) {
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'qr_path'");
    if ($result->num_rows == 0) {
        $alter_query = "ALTER TABLE orders ADD COLUMN qr_path VARCHAR(255) NULL AFTER status";
        if (!$conn->query($alter_query)) {
            throw new Exception("Database schema setup failed: Could not add 'qr_path' column. " . $conn->error);
        }
    }
}

try {
    // Ensure the required column present before starting the transaction
    ensureQrPathColumnExists($conn);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit();
}
// --- End Schema Check ---


// Start transaction
$conn->begin_transaction();

try {
    $totalPrice = 0;
    $cartItems = [];

    // --- 1. Fetch Cart Items and Preliminary Stock Check ---
    $query = "
        SELECT 
            c.product_id, 
            c.quantity, 
            c.price,
            p.product_name
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Your cart is empty. Cannot process order.");
    }

    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
        $totalPrice += ($row['price'] * $row['quantity']);

        // Check stock availability
        $stock_query = "SELECT stock FROM products WHERE product_id = ?";
        $stock_stmt = $conn->prepare($stock_query);
        $stock_stmt->bind_param("i", $row['product_id']);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        $product_data = $stock_result->fetch_assoc();
        $stock_stmt->close(); 

        if ($row['quantity'] > $product_data['stock']) {
            throw new Exception("Insufficient stock for " . htmlspecialchars($row['product_name']) . ".");
        }
    }
    $stmt->close(); 

    // --- 2. Create the Main Order Record ---
    $insert_order_query = "INSERT INTO orders (customer_id, total_price, status) VALUES (?, ?, 'Pending')";
    $order_stmt = $conn->prepare($insert_order_query);
    $order_stmt->bind_param("id", $customer_id, $totalPrice);
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create main order record: " . $conn->error);
    }

    $order_id = $conn->insert_id;
    $order_stmt->close();

    // --- 3. Insert Order Details and Update Stock ---
    $detail_query = "
        INSERT INTO order_details (order_id, product_id, product_name, quantity, unit_price, sub_total) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $detail_stmt = $conn->prepare($detail_query);

    $stock_update_query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
    $stock_update_stmt = $conn->prepare($stock_update_query);

    foreach ($cartItems as $item) {
        $subTotal = $item['price'] * $item['quantity'];
        
        // Insert into order_details
        $detail_stmt->bind_param(
            "iisidd", // i=order_id, i=product_id, s=product_name, i=quantity, d=unit_price, d=sub_total
            $order_id, 
            $item['product_id'], 
            $item['product_name'], 
            $item['quantity'], 
            $item['price'], // Price is the unit_price
            $subTotal // Calculated sub_total
        );
        if (!$detail_stmt->execute()) {
            throw new Exception("Failed to insert order detail for product ID " . $item['product_id'] . ". MySQL Error: " . $detail_stmt->error);
        }

        // Update product stock
        $stock_update_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        if (!$stock_update_stmt->execute()) {
            throw new Exception("Failed to update stock for product ID " . $item['product_id']);
        }
    }
    $detail_stmt->close();
    $stock_update_stmt->close();


    // --- 4. QR Code Generation and Path Update ---
    $qrData = $base_url . 'updateOrderStatus.php?order_id=' . $order_id;
    $qrFileName = 'qr_' . $order_id . '.png';
    $qrFilePath = '../qr_codes/' . $qrFileName; // Path relative to PHP/ folder

    // Generate the PNG file
    QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 10);

    // Update the orders table with the QR path
    $qr_update_query = "UPDATE orders SET qr_path = ? WHERE order_id = ?";
    $qr_stmt = $conn->prepare($qr_update_query);
    $qr_stmt->bind_param("si", $qrFilePath, $order_id);
    if (!$qr_stmt->execute()) {
        throw new Exception("Failed to update QR path in order: " . $conn->error);
    }
    $qr_stmt->close();

    // --- 5. Clear Cart and Commit Transaction ---
    $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
    $clear_stmt = $conn->prepare($clear_cart_query);
    $clear_stmt->bind_param("i", $customer_id);
    if (!$clear_stmt->execute()) {
        throw new Exception("Failed to clear cart: " . $clear_stmt->error);
    }
    $clear_stmt->close();

    // If all steps succeeded, commit the transaction
    $conn->commit();
    
    // 6. FIX: Return the necessary details in the JSON response
    echo json_encode([
        "success" => true, 
        "message" => "Order " . $order_id . " placed successfully!",
        "order_id" => $order_id, // Ensure order_id is returned
        "qr_path" => $qrFilePath // Ensure qr_path is returned
    ]);

} catch (Exception $e) {
    // If any error occurred, rollback all changes
    $conn->rollback();
    // Return detailed error message including file and line number for debugging
    $errorMessage = "Order failed: " . $e->getMessage() . 
                    " (Error in " . basename($e->getFile()) . " line " . $e->getLine() . ")";
    http_response_code(500); // Set HTTP response code for client-side error handling
    echo json_encode(["success" => false, "message" => $errorMessage]);

} finally {
    $conn->close();
}
?>
