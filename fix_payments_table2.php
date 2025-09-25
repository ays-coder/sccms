<?php
require_once 'db_connect.php';

// Create payments table if it doesn't exist
$create_sql = "
    CREATE TABLE IF NOT EXISTS payments (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        registration_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('credit_card', 'bank_transfer') NOT NULL,
        receipt_number VARCHAR(20) NOT NULL,
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_registration FOREIGN KEY (registration_id) 
        REFERENCES course_registrations(registration_id)
    )
";

try {
    $conn->query($create_sql);
    echo "Payments table structure updated successfully.\n";
    
    // Verify the table structure
    echo "\nCurrent payments table structure:\n";
    $result = $conn->query("DESCRIBE payments");
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>