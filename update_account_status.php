<?php
session_start();
require_once 'db_connect.php';

// Only admin can change status
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $status = $_POST['status'] === 'active' ? 'active' : 'deactivated';

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $status, $user_id);

    if ($stmt->execute()) {
        header("Location: deactivate_accounts.php");
        exit();
    } else {
        echo "Error updating account status.";
    }
    $stmt->close();
}

$conn->close();
?>

