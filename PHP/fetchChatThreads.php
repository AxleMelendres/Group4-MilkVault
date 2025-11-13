<?php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';

$response = [
    'success' => false,
    'threads' => [],
    'message' => null
];

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$adminId = (int)$_SESSION['admin_id'];

$database = new Database();
$conn = $database->getConnection();

function ensureChatTableExists(mysqli $conn): void {
    static $ensured = false;
    if ($ensured) {
        return;
    }

    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'chat_messages'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $ensured = true;
        return;
    }

    // Try to create table with foreign keys first
    $ddl = "
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            admin_id INT NOT NULL,
            sender_type ENUM('customer', 'admin') NOT NULL,
            message TEXT NOT NULL,
            is_read_admin TINYINT(1) NOT NULL DEFAULT 0,
            is_read_customer TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer_admin (customer_id, admin_id),
            INDEX idx_created_at (created_at)
        )
    ";

    if (!$conn->query($ddl)) {
        throw new Exception("Failed to initialise chat table: " . $conn->error);
    }

    // Try to add foreign keys separately (they may fail if tables don't exist or constraints already exist)
    $fk1 = "ALTER TABLE chat_messages ADD CONSTRAINT fk_chat_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE";
    $fk2 = "ALTER TABLE chat_messages ADD CONSTRAINT fk_chat_admin FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE CASCADE";
    
    @$conn->query($fk1); // Suppress errors if constraint already exists or table doesn't exist
    @$conn->query($fk2);

    $ensured = true;
}

try {
    ensureChatTableExists($conn);

    $sql = "
        SELECT t.customer_id,
               t.customer_name,
               t.last_message,
               t.last_sender_type,
               t.last_message_at,
               t.unread_count
        FROM (
            SELECT cm.customer_id,
                   CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                   SUM(CASE WHEN cm.sender_type = 'customer' AND cm.is_read_admin = 0 THEN 1 ELSE 0 END) AS unread_count,
                   (
                       SELECT message
                       FROM chat_messages
                       WHERE customer_id = cm.customer_id AND admin_id = cm.admin_id
                       ORDER BY created_at DESC
                       LIMIT 1
                   ) AS last_message,
                   (
                       SELECT sender_type
                       FROM chat_messages
                       WHERE customer_id = cm.customer_id AND admin_id = cm.admin_id
                       ORDER BY created_at DESC
                       LIMIT 1
                   ) AS last_sender_type,
                   (
                       SELECT created_at
                       FROM chat_messages
                       WHERE customer_id = cm.customer_id AND admin_id = cm.admin_id
                       ORDER BY created_at DESC
                       LIMIT 1
                   ) AS last_message_at
            FROM chat_messages cm
            JOIN customers c ON c.customer_id = cm.customer_id
            WHERE cm.admin_id = ?
            GROUP BY cm.customer_id, customer_name
        ) AS t
        ORDER BY t.last_message_at DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['threads'][] = [
            'customer_id' => (int)$row['customer_id'],
            'customer_name' => $row['customer_name'] ?: 'Customer',
            'last_message' => $row['last_message'] ?: '',
            'last_sender_type' => $row['last_sender_type'] ?: null,
            'last_message_at' => $row['last_message_at'],
            'unread_count' => (int)$row['unread_count']
        ];
    }

    $stmt->close();
    $response['success'] = true;
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

// Clear any unexpected output
ob_clean();

// Send JSON response
echo json_encode($response);
exit;

