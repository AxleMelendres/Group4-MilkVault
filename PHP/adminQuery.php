<?php

// --- Fetch Summary Data ---
function getSummaryData($conn) {
    // Total Orders
    $result = $conn->query("SELECT COUNT(*) AS totalOrders FROM orders");
    $totalOrders = $result->fetch_assoc()['totalOrders'];

    // Total Customers
    $result = $conn->query("SELECT COUNT(*) AS totalCustomers FROM customers"); // make sure table name is 'customer'
    $totalCustomers = $result->fetch_assoc()['totalCustomers'];

    // Total Products
    $result = $conn->query("SELECT COUNT(*) AS totalProducts FROM products");
    $totalProducts = $result->fetch_assoc()['totalProducts'];

    // Total Sales (Delivered Orders)
    $result = $conn->query("SELECT SUM(total_price) AS totalSales FROM orders WHERE status='Delivered'");
    $totalSales = $result->fetch_assoc()['totalSales'];

    return [
        'totalOrders' => $totalOrders,
        'totalCustomers' => $totalCustomers,
        'totalProducts' => $totalProducts,
        'totalSales' => $totalSales ?? 0 
    ];
}


// GET INVENTORY
function getInventory($conn) {
    return $conn->query("SELECT * FROM products");
}

function getOrders($conn) {
    $sql = "
        SELECT 
            o.order_id,
            CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
            o.total_price AS total_amount,
            o.order_date,
            o.status
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        ORDER BY o.order_date DESC
    ";
    return $conn->query($sql);
}


// GET USERS
function getUsers($conn) {
    $query = "
        SELECT 
            customer_id AS user_id,
            CONCAT(first_name, ' ', last_name) AS name,
            address,
            contact_number AS phone,
            'Active' AS status
        FROM customers
    ";
    return $conn->query($query);
}

// GET LOW STOCKS & NEARLY EXPIRE PRODUCTS
function getAlerts($conn, $nearExpiryDays = 7) {
    $today = date('Y-m-d');
    $nearExpiry = date('Y-m-d', strtotime("+$nearExpiryDays days"));

    $stmt = $conn->query("SELECT * FROM products 
                          WHERE stock <= low_level 
                          OR expiration_date <= '$nearExpiry'
                          ORDER BY stock ASC");
    return $stmt;
}
?>
