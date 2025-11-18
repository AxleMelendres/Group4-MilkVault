<?php

// --- Fetch Summary Data ---
function getSummaryData($conn) {
    // Total Orders
    $result = $conn->query("SELECT COUNT(*) AS totalOrders FROM orders");
    $totalOrders = $result->fetch_assoc()['totalOrders'];

    // Total Customers
    $result = $conn->query("SELECT COUNT(*) AS totalCustomers FROM customers");
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

// --- Fetch Daily Sales Data for Chart ---
function getDailySalesData($conn, $days = 30) {
    // Uses prepared statement for safety
    $sql = "
        SELECT 
            DATE(order_date) AS sale_date,
            COUNT(order_id) AS total_orders,
            SUM(CASE WHEN status = 'Delivered' THEN total_price ELSE 0 END) AS total_revenue
        FROM 
            orders
        WHERE 
            order_date >= DATE(DATE_SUB(NOW(), INTERVAL ? DAY))
            AND status IN ('Delivered', 'Processing', 'Pending') -- Include all orders in range, but only Delivered for revenue sum
        GROUP BY 
            sale_date
        ORDER BY 
            sale_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure revenue is a float and date is used as key for zero-filling later (in JS)
        $data[] = [
            'sale_date' => $row['sale_date'],
            'total_orders' => (int)$row['total_orders'],
            'total_revenue' => (float)$row['total_revenue']
        ];
    }
    return $data;
}

?>
