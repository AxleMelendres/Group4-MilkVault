<?php
require_once "dbConnection.php";
require_once "adminQuery.php";

$db = new Database();
$conn = $db->getConnection();

$range = intval($_GET['range'] ?? 30);

// Get daily sales data using the function in adminQuery.php
$dailySales = getDailySalesData($conn, $range);

// Return JSON to JavaScript
header("Content-Type: application/json");
echo json_encode($dailySales);
?>
