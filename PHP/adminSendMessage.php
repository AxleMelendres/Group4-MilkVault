<?php

$lifetime = 7200; 

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => '',         // empty = defaults to current host
    'secure' => false,      // true if using HTTPS
    'httponly' => true,
    'samesite' => 'None'    // allows POST from JS, must be secure=true in HTTPS
]);

session_start();

header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

$response = ['success' => false, 'message' => null];

try {
    // 1. Check Authorization (Only Admin required)
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized: Admin session not found.');
    }

    // 2. Read JSON Input
    $data = json_decode(file_get_contents('php://input'), true);
    
    $message = trim($data['message'] ?? '');
    $customerId = (int)($data['customer_id'] ?? 0); // Must be included by adminChat.js

    if (empty($message)) {
        http_response_code(400);
        throw new Exception('Message content is required.');
    }
    if ($customerId <= 0) {
        http_response_code(400);
        throw new Exception('Target customer identifier is missing for Admin reply.'); 
    }

    $db = new Database();
    $conn = $db->getConnection();

    // --- LOGIC: ADMIN SENDER ---
    $sender_type = 'admin';
    $adminId = (int)$_SESSION['admin_id'];
    
    // Set read statuses: Read for Admin (sender), unread for Customer (recipient)
    $is_read_admin = 1; 
    $is_read_customer = 0;
    
    // 3. Insert Message into chat_messages
    $sql = "
        INSERT INTO chat_messages 
        (customer_id, admin_id, sender_type, message, is_read_admin, is_read_customer) 
        VALUES (?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // CRITICAL FIX: Explicitly cast to prevent bind_param errors
    $p_customerId = (int)$customerId;
    $p_adminId = (int)$adminId;
    $p_sender_type = (string)$sender_type;
    $p_message = (string)$message;
    $p_is_read_admin = (int)$is_read_admin;
    $p_is_read_customer = (int)$is_read_customer;

    $stmt->bind_param(
        'iissii', 
        $p_customerId, 
        $p_adminId, 
        $p_sender_type, 
        $p_message, 
        $p_is_read_admin, 
        $p_is_read_customer
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
    $response['message'] = "Admin Send Error: " . $e->getMessage();
}

ob_clean(); 
echo json_encode($response);
exit;
?>