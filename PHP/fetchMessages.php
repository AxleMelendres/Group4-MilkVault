<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

// Check if dbConnection.php provides a global $conn variable. If not, instantiate Database here.
if (!isset($conn)) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'messages' => [], 'message' => 'Database connection failed.']);
        exit;
    }
}

$response = [
    'success' => false,
    'messages' => [],
    'chat_partner' => null,
    'message' => ''
];

try {
    // 1. Check for any session authorization first
    if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized access. Please log in.');
    }

    $customerId = 0;
    
    // 2. Determine the target customer ID for fetching messages
    if (isset($_SESSION['admin_id'])) {
        // Admin view: Customer ID comes from the URL parameter 'chat_with'
        if (!isset($_GET['chat_with']) || (int)$_GET['chat_with'] <= 0) {
            throw new Exception('Missing target customer identifier (chat_with) for Admin view.');
        }
        $customerId = (int)$_GET['chat_with'];
    } elseif (isset($_SESSION['customer_id'])) {
        // Customer view: Customer ID is the logged-in user's ID
        $customerId = (int)$_SESSION['customer_id'];
    }

    // 3. Final check for a valid ID (This is the specific fix for the error you received)
    if ($customerId <= 0) {
        http_response_code(401);
        throw new Exception('Missing customer identifier.');
    }


    // --- 1. Get Chat Partner Name ---
    if (isset($_SESSION['admin_id'])) {
        $stmt = $conn->prepare("SELECT first_name, last_name FROM customers WHERE customer_id = ? LIMIT 1");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $response['chat_partner'] = trim($row['first_name'] . ' ' . $row['last_name']) ?: 'Customer';
        } else {
            $response['chat_partner'] = 'Unknown Customer';
        }
        $stmt->close();
    } elseif (isset($_SESSION['customer_id'])) {
        // Customer side: Chat partner is always 'Admin' (based on your system structure)
        $response['chat_partner'] = 'Admin'; 
    }
    
    // --- 2. Fetch messages from the correct table ---
    $sql = "
        SELECT id, sender_type, message, created_at
        FROM chat_messages
        WHERE customer_id = ? 
        ORDER BY created_at ASC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Message query preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $customerId); 
    $stmt->execute();
    $res = $stmt->get_result();

    // Fetch and format messages
    while ($row = $res->fetch_assoc()) {
        $response['messages'][] = [
            'id' => (int)$row['id'],
            'sender_type' => $row['sender_type'],
            'message' => $row['message'], 
            'created_at' => $row['created_at'] // Pass the raw timestamp for JS to format
        ];
    }
    $stmt->close();

    // --- 3. Mark messages as read ---
    if (isset($_SESSION['admin_id'])) {
        $upd = $conn->prepare("
            UPDATE chat_messages SET is_read_admin = 1
            WHERE customer_id = ? AND sender_type = 'customer' AND is_read_admin = 0
        ");
        $upd->bind_param('i', $customerId);
    } else {
        $upd = $conn->prepare("
            UPDATE chat_messages SET is_read_customer = 1
            WHERE customer_id = ? AND sender_type = 'admin' AND is_read_customer = 0
        ");
        $upd->bind_param('i', $customerId);
    }

    if ($upd && $upd->execute()) {
        $upd->close();
    }
    // (Errors on update are suppressed to prioritize message fetching)

    $response['success'] = true;

} catch (Exception $e) {
    // If an error occurs, send the error message in the JSON response
    http_response_code(500); 
    $response['success'] = false;
    $response['message'] = "FETCH ERROR: " . $e->getMessage();
}

ob_clean();
echo json_encode($response);
exit;
?>