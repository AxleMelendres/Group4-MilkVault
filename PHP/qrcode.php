<?php
require_once '../phpqrcode/qrlib.php'; // make sure this is correct relative path

$path = '../img/';
if (!file_exists($path)) {
    mkdir($path);
}

$filename = $path . time() . ".png";

// generate QR
QRCode::png("Axle", $filename, 'H', 4, 4);

// show image
echo "<h3>QR Generated Successfully</h3>";
echo "<img src='".$filename."'>";
?>
