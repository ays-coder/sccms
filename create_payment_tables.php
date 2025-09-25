<?php
require_once 'db_connect.php';

// Modify course_registrations table to add status column
$sql = "
    ALTER TABLE course_registrations 
    ADD COLUMN IF NOT EXISTS status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
";

try {
    if ($conn->query($sql)) {
        echo "Successfully added status and registration_date columns to course_registrations table.\n";
    }
} catch (Exception $e) {
    // If table doesn't exist, create it
    if ($e->getCode() == 1146) {
        $create_sql = "
            CREATE TABLE course_registrations (
                registration_id INT PRIMARY KEY AUTO_INCREMENT,
                course_id INT NOT NULL,
                student_id INT NOT NULL,
                parent_id INT NOT NULL,
                status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(course_id),
                FOREIGN KEY (student_id) REFERENCES users(user_id),
                FOREIGN KEY (parent_id) REFERENCES users(user_id)
            )
        ";
        
        if ($conn->query($create_sql)) {
            echo "Successfully created course_registrations table with all required columns.\n";
        } else {
            echo "Error creating table: " . $conn->error . "\n";
        }
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Create payments table if it doesn't exist
$payments_sql = "
    CREATE TABLE IF NOT EXISTS payments (
        payment_id INT PRIMARY KEY AUTO_INCREMENT,
        registration_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('credit_card', 'bank_transfer') NOT NULL,
        receipt_number VARCHAR(20) NOT NULL,
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (registration_id) REFERENCES course_registrations(registration_id),
        UNIQUE KEY unique_receipt (receipt_number)
    )
";

try {
    if ($conn->query($payments_sql)) {
        echo "Successfully ensured payments table exists with all required columns.\n";
    }
} catch (Exception $e) {
    echo "Error with payments table: " . $e->getMessage() . "\n";
}

?>