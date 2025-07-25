<?php
// Include the QR library
include "qrcode/qrlib.php";

// Text/data you want to encode
$text = "https://smartcommercecore.com"; // Replace with your own URL or data

// File path to save QR code image (optional)
$path = 'qrcodes/';
if (!file_exists($path)) {
    mkdir($path);
}
$file = $path . 'qr_' . time() . '.png';

// Generate the QR code and save to file
QRcode::png($text, $file, QR_ECLEVEL_L, 4);

// Display the image
echo "<h3>QR Code Generated</h3>";
echo "<img src='" . $file . "' />";
?>
