<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}
?>
<h2>Welcome, Parent <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
<a href="logout.php">Logout</a>
