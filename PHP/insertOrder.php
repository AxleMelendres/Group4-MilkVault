<?php
require_once '../phpqrcode/qrlib.php';       
require_once '../PHP/dbConnection.php';     

$db = new Database();
$conn = $db->getConnection();

$customer_name = "Axle Melendres";
$product_name = "Fresh Milk";
$quantity = 3;
$total_price = 450.00;


$sql = "INSERT INTO orders (customer_name, product_name, quantity, total_price)
        VALUES ('$customer_name', '$product_name', $quantity, $total_price)";

if ($conn->query($sql) === TRUE) {
    $order_id = $conn->insert_id; 

    $qr_text = "Order ID: $order_id\nCustomer: $customer_name\nProduct: $product_name\nQuantity: $quantity\nTotal: ₱$total_price";


    $path = '../img/';
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    $filename = $path . 'order_' . $order_id . '.png';
    QRcode::png($qr_text, $filename, 'H', 4, 4);

    $update_sql = "UPDATE orders SET qr_path = '$filename' WHERE order_id = $order_id";
    $conn->query($update_sql);

    echo "<h3>Order Created Successfully!</h3>";
    echo "<p>Order ID: $order_id</p>";
    echo "<p>Customer: $customer_name</p>";
    echo "<p>Product: $product_name</p>";
    echo "<p>Quantity: $quantity</p>";
    echo "<p>Total: ₱$total_price</p>";
    echo "<img src='" . $filename . "' alt='QR Code'>";
} else {
    echo "Error inserting order: " . $conn->error;
}

$db->closeConnection();
?>
