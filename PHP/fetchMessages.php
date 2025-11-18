<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

$response = ['success' => false, 'messages' => [], 'chat_partner' => 'Admin', 'message' => ''];

try {
    if (!isset($_SESSION['customer_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized.');
    }

    $customerId = (int)$_SESSION['customer_id'];

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT id, sender_type, message, created_at
        FROM chat_messages
        WHERE customer_id = ?
        ORDER BY created_at ASC
    ");

    if (!$stmt) throw new Exception($conn->error);

    $stmt->bind_param('i', $customerId);
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

    // Mark admin messages as read
    $upd = $conn->prepare("
        UPDATE chat_messages SET is_read_customer = 1
        WHERE customer_id = ? AND sender_type = 'admin' AND is_read_customer = 0
    ");
    $upd->bind_param('i', $customerId);
    $upd->execute();
    $upd->close();

    $stmt->close();
    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Fetch Error: " . $e->getMessage();
}

echo json_encode($response);
exit;
