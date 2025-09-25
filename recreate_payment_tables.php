<?php
require_once 'db_connect.php';

// Start transaction
$conn->begin_transaction();

try {
    // Drop existing tables
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->query("DROP TABLE IF EXISTS payments");
    $conn->query("DROP TABLE IF EXISTS course_registrations");
    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    // Create course_registrations table
    $create_registrations = "
        CREATE TABLE course_registrations (
            registration_id INT AUTO_INCREMENT PRIMARY KEY,
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
    $conn->query($create_registrations);
    echo "Course registrations table created successfully.\n";

    // Create payments table
    $create_payments = "
        CREATE TABLE payments (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            registration_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('credit_card', 'bank_transfer') NOT NULL,
            receipt_number VARCHAR(20) NOT NULL,
            payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registration_id) REFERENCES course_registrations(registration_id)
        )
    ";
    $conn->query($create_payments);
    echo "Payments table created successfully.\n";

    // Commit the transaction
    $conn->commit();

    // Verify the structures
    echo "\nCourse Registrations table structure:\n";
    $result = $conn->query("DESCRIBE course_registrations");
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }

    echo "\nPayments table structure:\n";
    $result = $conn->query("DESCRIBE payments");
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
?>