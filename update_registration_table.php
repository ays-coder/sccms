<?php
require_once 'db_connect.php';

// Add parent_id to course_registrations table
$sql = "
    ALTER TABLE course_registrations 
    ADD COLUMN IF NOT EXISTS parent_id INT,
    ADD FOREIGN KEY (parent_id) REFERENCES users(user_id)
";

try {
    if ($conn->query($sql)) {
        echo "Successfully added parent_id column to course_registrations table.\n";
    }
} catch (Exception $e) {
    // If table doesn't exist, create it from scratch
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
            echo "Successfully created course_registrations table with all required columns including parent_id.\n";
        } else {
            echo "Error creating table: " . $conn->error . "\n";
        }
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Verify table structure
$check_sql = "DESCRIBE course_registrations";
$result = $conn->query($check_sql);
if ($result) {
    echo "\nCurrent course_registrations table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
}

?>