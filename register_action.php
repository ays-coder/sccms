<?php
require_once 'db_connect.php';



$admin_email = 'admin@gmail.com';
$admin_password = password_hash('admin', PASSWORD_DEFAULT);
$admin_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$admin_check->bind_param("s", $admin_email);
$admin_check->execute();
$admin_result = $admin_check->get_result();
if ($admin_result->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
    $admin_name = "Admin";
    $stmt->bind_param("sss", $admin_name, $admin_email, $admin_password);
    $stmt->execute();
    $stmt->close();
}
$admin_check->close();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $parent_of = null;

    // Check if email already exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "Email is already registered.";
        exit();
    }

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, parent_of, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $parent_of);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        echo "Registration failed. Try again.";
    }

    $stmt->close();
    $check->close();
}
$conn->close();
?>
