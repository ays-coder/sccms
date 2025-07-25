<?php
session_start();
require_once 'db_connect.php';

// Check if the tutor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

// Handle assignment form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $uploaded_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date, uploaded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $course_id, $title, $description, $due_date, $uploaded_by);
    
    if ($stmt->execute()) {
        $message = "Assignment uploaded successfully!";
    } else {
        $message = "Failed to upload assignment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Assignments | Tutor Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6 font-sans">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-center text-blue-700">Manage Assignments</h1>

    <?php if (!empty($message)): ?>
      <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Assignment Upload Form -->
    <form method="POST" class="bg-white p-6 rounded shadow mb-10">
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Course ID</label>
        <input type="text" name="course_id" required class="w-full border border-gray-300 p-2 rounded">
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Title</label>
        <input type="text" name="title" required class="w-full border border-gray-300 p-2 rounded">
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Description</label>
        <textarea name="description" required class="w-full border border-gray-300 p-2 rounded"></textarea>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Due Date</label>
        <input type="date" name="due_date" required class="w-full border border-gray-300 p-2 rounded">
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Upload Assignment</button>
    </form>

    <!-- Assignments Table -->
    <h2 class="text-2xl font-semibold mb-4">Uploaded Assignments</h2>
    <div class="overflow-x-auto bg-white rounded shadow">
      <table class="min-w-full text-sm text-left text-gray-600">
        <thead class="bg-gray-200 text-gray-700 text-xs uppercase">
          <tr>
            <th class="px-4 py-2">Assignment ID</th>
            <th class="px-4 py-2">Course ID</th>
            <th class="px-4 py-2">Title</th>
            <th class="px-4 py-2">Description</th>
            <th class="px-4 py-2">Due Date</th>
            <th class="px-4 py-2">Uploaded By</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $tutor_id = $_SESSION['user_id'];
            $result = $conn->query("SELECT * FROM assignments WHERE uploaded_by = $tutor_id ORDER BY assignment_id DESC");

            if ($result->num_rows > 0):
              while ($row = $result->fetch_assoc()):
          ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-4 py-2"><?= htmlspecialchars($row['assignment_id']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['course_id']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['title']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['description']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['due_date']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['uploaded_by']) ?></td>
            </tr>
          <?php endwhile; else: ?>
            <tr>
              <td colspan="6" class="text-center text-gray-500 py-4">No assignments found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
