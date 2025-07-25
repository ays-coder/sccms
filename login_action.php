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

    // Prepare SQL query to fetch user by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // If user exists
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

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
                case 'tutor':
                    header("Location: tutor_dashboard.php");
                    break;
                case 'parent':
                    // Redirect to child verification page
                    header("Location: verify_child.php");
                    break;
                default:
                    header("Location: dashboard.php");
            }
            exit();
        } else {
            // Incorrect password
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } else {
        // No user found
        header("Location: login.php?error=user_not_found");
        exit();
    }
} else {
    // Invalid request method
    header("Location: login.php");
    exit();
}
