<?php
session_start();
require_once 'db_connect.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check for empty fields
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit();
    }

    // First check in tutor table
    $tutor_stmt = $conn->prepare("SELECT * FROM tutor WHERE email = ?");
    if (!$tutor_stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $tutor_stmt->bind_param("s", $email);
    $tutor_stmt->execute();
    $tutor_result = $tutor_stmt->get_result();

    // If tutor exists
    if ($tutor_result && $tutor_result->num_rows === 1) {
        $tutor = $tutor_result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $tutor['password'])) {
            // Set session variables for tutor
            $_SESSION['user_id'] = $tutor['tutor_id'];
            $_SESSION['username'] = $tutor['username'];
            $_SESSION['email'] = $tutor['email'];
            $_SESSION['role'] = 'tutor';
            $_SESSION['status'] = $tutor['status'];

            // Check if tutor account is active
            if ($tutor['status'] === 'inactive') {
                header("Location: login.php?error=account_inactive");
                exit();
            }

            // Redirect to tutor dashboard
            header("Location: tutor_dashboard.php");
            exit();
        } else {
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
        $tutor_stmt->close();
    } else {
        // If not found in tutor table, check users table
        $user_stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if (!$user_stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $user_stmt->bind_param("s", $email);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();

        // If user exists
        if ($user_result && $user_result->num_rows === 1) {
            $user = $user_result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirect to appropriate dashboard
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;
                    case 'student':
                        header("Location: student_dashboard.php");
                        break;
                    case 'parent':
                        header("Location: verify_child.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                header("Location: login.php?error=invalid_credentials");
                exit();
            }
        } else {
            header("Location: login.php?error=user_not_found");
            exit();
        }
        $user_stmt->close();
    }
} else {
    header("Location: login.php");
    exit();
}
$conn->close();
?>