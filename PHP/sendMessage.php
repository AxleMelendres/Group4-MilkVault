<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

$response = ['success' => false, 'message' => null];

try {
    // 1. Check Authorization
    if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized: Must be logged in as customer or admin.');
    }

    // 2. Read JSON Input
    $data = json_decode(file_get_contents('php://input'), true);
    $message = trim($data['message'] ?? '');
    
    if (empty($message)) {
        http_response_code(400);
        throw new Exception('Message content is required.');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Initialize variables
    $customerId = 0;
    $adminId = 0;
    $sender_type = '';
    $is_read_admin = 0; 
    $is_read_customer = 0;

    // --- CRITICAL FIX: Use sender_type from client to resolve session conflict ---
    $client_sender_type = strtolower($data['sender_type'] ?? '');
    
    if (isset($_SESSION['admin_id']) && $client_sender_type !== 'customer') {
        // 🥇 ADMIN PRIORITY: Admin is sending a message, OR an admin is logged in 
        // and the request is NOT explicitly marked as a customer request.
        $sender_type = 'admin';
        $adminId = (int)$_SESSION['admin_id'];
        
        // Admin must specify the Customer ID (The ID they clicked on in the thread list)
        $customerId = (int)($data['customer_id'] ?? 0);
        
        if ($customerId <= 0) {
            http_response_code(400);
            // This is the error message you were receiving!
            throw new Exception('Target customer identifier is missing for Admin reply.'); 
        }

        // Set read statuses: Read for Admin (sender), unread for Customer (recipient)
        $is_read_admin = 1; 
        $is_read_customer = 0;
        
    } elseif (isset($_SESSION['customer_id'])) {
        // 🥈 CUSTOMER: Customer is sending a message (This block will now run 
        // even if admin_id is also set, provided the request is from the customer client).
        $sender_type = 'customer';
        $customerId = (int)$_SESSION['customer_id'];
        
        // Customer targets the fixed Admin ID (1 is used as default)
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : 1; 

        // Set read statuses: Unread for Admin (recipient), read for Customer (sender)
        $is_read_admin = 0; 
        $is_read_customer = 1; 
        
    } else {
        http_response_code(401);
        throw new Exception('Unauthorized session state.');
    }

    // 4. Insert Message into chat_messages
    $sql = "
        INSERT INTO chat_messages 
        (customer_id, admin_id, sender_type, message, is_read_admin, is_read_customer) 
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param(
        'iissii', 
        $customerId, 
        $adminId, 
        $sender_type, 
        $message, 
        $is_read_admin, 
        $is_read_customer
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Message sent.';
    } else {
        throw new Exception('Failed to execute insert: ' . $stmt->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(500); 
    }
    $response['message'] = $e->getMessage();
}

ob_clean(); 
echo json_encode($response);
exit;
