<?php
require_once '../PHP/dbConnection.php';

// ✅ Create Database instance and get connection
$database = new Database();
$conn = $database->getConnection();

/**
 * Fetch all products available in stock
 */
function getAvailableProducts($conn) {
    $products = [];
    try {
        $query = "SELECT product_id, product_name, price, stock, image 
                  FROM products 
                  WHERE stock > 0 
                  ORDER BY product_name ASC";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
    }
    return $products;
}

/**
 * Fetch all orders for a specific customer, including the image path of one product in the order.
 */
function getCustomerOrders($conn, $customer_id) {
    $orders = [];
    try {
        $query = "
            SELECT 
                o.order_id, 
                o.order_date, 
                o.total_price, 
                o.status,
                od.quantity,
                p.product_name,
                CONCAT('../img/', p.image) AS product_image_path
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
            JOIN products p ON od.product_id = p.product_id
            WHERE o.customer_id = ?
            ORDER BY o.order_date DESC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching customer orders: " . $e->getMessage());
    }

    return $orders;
}



/**
 * Fetch all items in a customer's cart
 */
function getCustomerCart($conn, $customer_id) {
    $cartItems = [];
    try {
        $query = "
            SELECT 
                c.cart_id, 
                p.product_name, 
                p.image, 
                c.quantity, 
                p.price, 
                (c.quantity * p.price) AS total
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.customer_id = ?";

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            return $cartItems;
        }
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cartItems[] = $row;
            }
            $result->free();
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching cart items: " . $e->getMessage());
    }

    return $cartItems;
}
?>
