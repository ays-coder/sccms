<?php
session_start();
require_once 'db_connect.php';

// Ensure parent is logged in and child is verified
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent' || !isset($_SESSION['child_email'])) {
    header("Location: login.php");
    exit();
}

$child_email = $_SESSION['child_email'];

// Get child's ID from email
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE email = ?");
$stmt->bind_param("s", $child_email);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();

if (!$child) {
    echo "Child not found in the system.";
    exit();
}

$child_id = $child['user_id'];
$child_name = $child['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Child Grades | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-3xl font-bold text-center mb-6">Child's Grades - <?= htmlspecialchars($child_name) ?></h1>

    <!-- Assignment Results -->
    <div class="bg-white p-5 rounded shadow mb-8">
      <h2 class="text-2xl font-semibold mb-4">Assignment Results</h2>
      <table class="w-full table-auto border-collapse border border-gray-300">
        <thead>
          <tr class="bg-gray-100 text-left">
            <th class="p-2 border">Assignment Title</th>
            <th class="p-2 border">Course ID</th>
            <th class="p-2 border">Score</th>
            <th class="p-2 border">Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $conn->prepare("SELECT a.title, a.course_id, r.score, r.feedback FROM assignment_results r JOIN assignments a ON r.assignment_id = a.assignment_id WHERE r.student_id = ?");
          $stmt->bind_param("i", $child_id);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  echo "<tr>
                          <td class='p-2 border'>" . htmlspecialchars($row['title']) . "</td>
                          <td class='p-2 border'>" . htmlspecialchars($row['course_id']) . "</td>
                          <td class='p-2 border'>" . htmlspecialchars($row['score']) . "</td>
                          <td class='p-2 border'>" . htmlspecialchars($row['feedback']) . "</td>
                        </tr>";
              }
          } else {
              echo "<tr><td colspan='4' class='p-2 text-center border'>No assignment results found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <!-- Quiz Results -->
    <div class="bg-white p-5 rounded shadow">
      <h2 class="text-2xl font-semibold mb-4">Quiz Results</h2>
      <table class="w-full table-auto border-collapse border border-gray-300">
        <thead>
          <tr class="bg-gray-100 text-left">
            <th class="p-2 border">Quiz Title</th>
            <th class="p-2 border">Course ID</th>
            <th class="p-2 border">Score</th>
            <th class="p-2 border">Feedback</th>
            <th class="p-2 border">Taken At</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $conn->prepare("SELECT q.title, q.course_id, r.score, r.feedback, r.taken_at FROM quiz_results r JOIN quizzes q ON r.quiz_id = q.quiz_id WHERE r.student_id = ?");
          $stmt->bind_param("i", $child_id);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  echo "<tr>
                          <td class='p-2 border'>" . htmlspecialchars($row['title']) . "</td>
                          <td class='p-2 border'>" . htmlspecialchars($row['course_id']) . "</td>
                          <td class='p-2 border'>" . htmlspecialchars($row['score']) . "</td>
                          <td class='p-2 border'>" . htmlspecialchars($row['feedback']) . "</td>
                          <td class='p-2 border'>" . htmlspecialchars($row['taken_at']) . "</td>
                        </tr>";
              }
          } else {
              echo "<tr><td colspan='5' class='p-2 text-center border'>No quiz results found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <div class="text-center mt-8">
      <a href="parent_dashboard.php" class="text-blue-600 hover:underline">← Back