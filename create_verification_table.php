<?php
require_once 'db_connect.php';

// Create the student verification table
$sql = "
CREATE TABLE IF NOT EXISTS student_verification (
    verification_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    verification_code VARCHAR(10) NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    UNIQUE KEY unique_student (student_id),
    UNIQUE KEY unique_code (verification_code)
)";

try {
    if ($conn->query($sql)) {
        echo "Student verification table created successfully.\n";
        
        // Insert verification codes for existing students
        $insert_sql = "
        INSERT IGNORE INTO student_verification (student_id, verification_code) 
        SELECT u.user_id, 
               CONCAT('STU', LPAD(u.user_id, 4, '0'))
        FROM users u 
        WHERE u.role = 'student' 
        AND u.user_id NOT IN (SELECT student_id FROM student_verification)";
        
        if ($conn->query($insert_sql)) {
            echo "Verification codes generated for existing students.\n";
            
            // Display generated codes for testing
            $result = $conn->query("
                SELECT u.username, u.email, sv.verification_code 
                FROM student_verification sv 
                JOIN users u ON u.user_id = sv.student_id
                WHERE u.role = 'student'
                ORDER BY u.user_id");
            
            if ($result->num_rows > 0) {
                echo "\nGenerated verification codes:\n";
                echo str_pad("Username", 20) . str_pad("Email", 30) . "Code\n";
                echo str_repeat("-", 60) . "\n";
                
                while ($row = $result->fetch_assoc()) {
                    echo str_pad($row['username'], 20) . 
                         str_pad($row['email'], 30) . 
                         $row['verification_code'] . "\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>