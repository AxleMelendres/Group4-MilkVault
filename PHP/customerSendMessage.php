<?php
ob_start(); // 1. START BUFFERING OUTPUT

// --- Session Setup ---
$lifetime = 7200;
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Ensure header is set after session_start
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

$response = ['success' => false, 'message' => null];

try {
    if (!isset($_SESSION['customer_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized: Customer session not found.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $message = trim($data['message'] ?? '');

    if (empty($message)) {
        http_response_code(400);
        throw new Exception('Message content is required.');
    }

    $db = new Database();
    $conn = $db->getConnection();

    $customerId = (int)$_SESSION['customer_id'];
    $adminId = 1; // default admin ID

    $stmt = $conn->prepare("
        INSERT INTO chat_messages 
        (customer_id, admin_id, sender_type, message, is_read_admin, is_read_customer) 
        VALUES (?, ?, 'customer', ?, 0, 1)
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('iis', $customerId, $adminId, $message);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $response['success'] = true;
    $response['message'] = 'Message sent successfully.';

    $stmt->close();

} catch (Exception $e) {
    // If an error occurred, set 500 status unless already set (e.g., 401, 400)
    if (http_response_code() === 200) http_response_code(500);
    $response['message'] = "Customer Send Error: " . $e->getMessage();
}

// 2. DISCARD ALL PREVIOUS OUTPUT (INCLUDING ADS/NOTICES)
ob_clean(); 

echo json_encode($response);
exit;
// NO CLOSING ?> TAG