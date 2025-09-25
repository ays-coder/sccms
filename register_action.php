<?php
require_once 'db_connect.php';

// Create tutor table if it doesn't exist
$create_tutor_table = "CREATE TABLE IF NOT EXISTS tutor (
    tutor_id VARCHAR(10) PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_tutor_table)) {
    die("Error creating tutor table: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation checks
    if (!preg_match("/^[A-Za-z\s]+$/", $username)) {
        die("Name can only contain letters and spaces.");
    }

    if (strlen($password) < 8) {
        die("Password must be at least 8 characters long.");
    }

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle tutor registration
    if ($role === 'tutor') {
        // Check if tutor email exists
        $check = $conn->prepare("SELECT tutor_id FROM tutor WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            die("Email is already registered as a tutor.");
        }

        // Generate unique tutor ID
        $tutor_id = 'TUT' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

        // Insert into tutor table
        $stmt = $conn->prepare("INSERT INTO tutor (tutor_id, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $tutor_id, $username, $email, $hashed_password);

        if ($stmt->execute()) {
            // Send welcome email
            $to = $email;
            $subject = "Welcome to Smart Commerce Core - Tutor Registration";
            $message = "Dear $username,\n\n"
                    . "Welcome to Smart Commerce Core!\n"
                    . "Your registration as a tutor was successful.\n"
                    . "Your Tutor ID is: $tutor_id\n\n"
                    . "Best regards,\nSmart Commerce Core Team";
            $headers = "From: noreply@smartcommercecore.com";
            
            mail($to, $subject, $message, $headers);
            
            header("Location: login.php?registration=success&role=tutor");
            exit();
        } else {
            die("Tutor registration failed. Please try again. Error: " . $conn->error);
        }

        $stmt->close();
        $check->close();
    } else {
        // Handle other roles registration
        $parent_of = null;
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, parent_of, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $parent_of);

        if ($stmt->execute()) {
            header("Location: login.php?registration=success");
            exit();
        } else {
            die("Registration failed. Please try again.");
        }

        $stmt->close();
    }
}
$conn->close();
?>