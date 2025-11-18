<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

$response = [
    'success' => false,
    'messages' => [],
    'chat_partner' => '',
    'message' => null
];

try {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized: Admin session not found.');
    }

    $adminId = (int)$_SESSION['admin_id'];
    
    // Get the customer ID from the query parameter
    $customerId = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
    
    if ($customerId <= 0) {
        http_response_code(400);
        throw new Exception('Customer ID is required.');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get customer name for the chat header
    $nameStmt = $conn->prepare("
        SELECT CONCAT(first_name, ' ', last_name) AS full_name 
        FROM customers 
        WHERE customer_id = ?
    ");
    $nameStmt->bind_param('i', $customerId);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    
    if ($nameRow = $nameResult->fetch_assoc()) {
        $response['chat_partner'] = $nameRow['full_name'];
    } else {
        $response['chat_partner'] = 'Customer';
    }
    $nameStmt->close();

    // Fetch all messages between this admin and the customer
    $stmt = $conn->prepare("
        SELECT id, sender_type, message, created_at
        FROM chat_messages
        WHERE customer_id = ? AND admin_id = ?
        ORDER BY created_at ASC
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param('ii', $customerId, $adminId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['messages'][] = [
            'id' => (int)$row['id'],
            'sender_type' => $row['sender_type'],
            'message' => $row['message'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();

    // Mark customer messages as read by admin
    $updateStmt = $conn->prepare("
        UPDATE chat_messages 
        SET is_read_admin = 1
        WHERE customer_id = ? 
        AND admin_id = ? 
        AND sender_type = 'customer' 
        AND is_read_admin = 0
    ");
    $updateStmt->bind_param('ii', $customerId, $adminId);
    $updateStmt->execute();
    $updateStmt->close();

    $response['success'] = true;

} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    $response['message'] = "Fetch Error: " . $e->getMessage();
}

ob_clean();
echo json_encode($response);
exit;