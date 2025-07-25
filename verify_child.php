<?php
session_start();
require_once 'db_connect.php';

// Only logged-in parents can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_email = trim($_POST['child_email']);
    $child_id = trim($_POST['child_id']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_id = ? AND role = 'student'");
    $stmt->bind_param("si", $child_email, $child_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $child = $result->fetch_assoc();

    if ($child) {
        $_SESSION['child_id'] = $child['user_id'];
        $_SESSION['child_email'] = $child['email'];
        header("Location: parent_dashboard.php");
        exit();
    } else {
        $error = "Invalid student email or ID. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Verify Child | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600&display=swap" rel="stylesheet">
  <style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-blue-700 mb-6">Verify Your Child</h2>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label for="child_email" class="block text-gray-700 font-semibold mb-1">Child's Email</label>
        <input type="email" name="child_email" id="child_email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label for="child_id" class="block text-gray-700 font-semibold mb-1">Child's Student ID</label>
        <input type="number" name="child_id" id="child_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div class="text-center">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Verify & Continue</button>
      </div>
    </form>
  </div>
</body>
</html>
