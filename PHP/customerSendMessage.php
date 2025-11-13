<?php

$lifetime = 7200; 

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => false, // CHANGE TO TRUE if you use HTTPS!
    'httponly' => true, // Prevents client-side JavaScript access (security best practice)
    'samesite' => 'Lax' // Helps prevent CSRF and stabilizes cookie behavior
]);

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

$response = ['success' => false, 'message' => null];

try {
    // 1. Check Authorization (Only Customer required)
    if (!isset($_SESSION['customer_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized: Customer session not found.');
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

    // --- LOGIC: CUSTOMER SENDER ---
    $sender_type = 'customer';
    $customerId = (int)$_SESSION['customer_id'];
    
    // Customer targets the fixed Admin ID (1 is used as default)
    $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : 1; 

    // Set read statuses: Unread for Admin (recipient), read for Customer (sender)
    $is_read_admin = 0; 
    $is_read_customer = 1; 
    
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
    $response['message'] = "Customer Send Error: " . $e->getMessage();
}

ob_clean(); 
echo json_encode($response);
exit;
?>