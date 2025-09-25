<?php
require_once 'db_connect.php';

// Drop the existing payments table if it exists
$conn->query("DROP TABLE IF EXISTS payments");

// Create the payments table with all required columns
$create_payments = "
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'bank_transfer') NOT NULL,
    receipt_number VARCHAR(20) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES course_registrations(registration_id)
) ENGINE=InnoDB";

try {
    if ($conn->query($create_payments)) {
        echo "Payments table created successfully\n";
    }
} catch (Exception $e) {
    echo "Error creating payments table: " . $e->getMessage() . "\n";
    exit;
}

// Verify table structure
echo "\nCurrent payments table structure:\n";
$result = $conn->query("DESCRIBE payments");
while ($row = $result->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
}
?>