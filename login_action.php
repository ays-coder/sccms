<?php
session_start();
include 'db_connect.php'; // Make sure this file contains your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
        header("Location: login.php");
        exit();
    }

    // Prepare SQL (assuming 'users' table with email & hashed password)
    $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect to role-based dashboard
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'tutor':
                    header("Location: tutor/dashboard.php");
                    break;
                case 'student':
                    header("Location: student/dashboard.php");
                    break;
                case 'parent':
                    header("Location: parent/dashboard.php");
                    break;
                default:
                    header("Location: login.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid password.";
        }
    } else {
        $_SESSION['error'] = "No user found with that email.";
    }

    $stmt->close();
    $conn->close();
    header("Location: login.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
