<?php
session_start();
require_once 'db_connect.php';

// Ensure only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = "";
$email = "";
$status = "";

// Fetch student details
$stmt = $conn->prepare("SELECT username, email, status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $status);
$stmt->fetch();
$stmt->close();

// If account is deactivated
if ($status === 'deactivated') {
    echo "<p class='text-red-600 text-center mt-10'>Your account has been deactivated due to overdue payments.</p>";
    exit();
}

// Fetch quiz results
$quiz_sql = "SELECT qr.result_id, q.title AS quiz_title, qr.score, qr.feedback, qr.taken_at
             FROM quiz_results qr
             JOIN quizzes q ON qr.quiz_id = q.quiz_id
             WHERE qr.student_id = ?
             ORDER BY qr.taken_at DESC";
$qstmt = $conn->prepare($quiz_sql);
$qstmt->bind_param("i", $user_id);
$qstmt->execute();
$quiz_results = $qstmt->get_result();

// Fetch assignment results
$assign_sql = "SELECT ar.result_id, a.title AS assignment_title, ar.score, ar.feedback, ar.submitted_at
               FROM assignment_results ar
               JOIN assignments a ON ar.assignment_id = a.assignment_id
               WHERE ar.student_id = ?
               ORDER BY ar.submitted_at DESC";
$astmt = $conn->prepare($assign_sql);
$astmt->bind_param("i", $user_id);
$astmt->execute();
$assignment_results = $astmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Performance Reports | Student Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

  <!-- Header -->
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-blue-600 text-3xl">insights</span>
      <span class="text-xl font-bold text-blue-700">Performance Reports</span>
    </div>
    <div class="text-right">
      <div class="text-gray-700 font-semibold">
        <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($email) ?>)
      </div>
      <a href="student_dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mt-2 inline-block">Back to Dashboard</a>
    </div>
  </header>

  <!-- Main -->
  <main class="max-w-6xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-6 text-center">Your Academic Performance</h1>

    <!-- Quiz Results -->
    <section class="mb-10">
      <h2 class="text-2xl font-semibold mb-4 text-orange-600">Quiz Results</h2>
      <?php if ($quiz_results->num_rows > 0): ?>
        <div class="overflow-x-auto bg-white shadow rounded-lg">
          <table class="w-full text-left border-collapse">
            <thead class="bg-gray-100">
              <tr>
                <th class="p-3 border">Quiz Title</th>
                <th class="p-3 border">Score</th>
                <th class="p-3 border">Feedback</th>
                <th class="p-3 border">Taken At</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $quiz_results->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                  <td class="p-3 border"><?= htmlspecialchars($row['quiz_title']) ?></td>
                  <td class="p-3 border"><?= htmlspecialchars($row['score']) ?>%</td>
                  <td class="p-3 border"><?= htmlspecialchars($row['feedback'] ?? '-') ?></td>
                  <td class="p-3 border"><?= date("F j, Y, g:i a", strtotime($row['taken_at'])) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-gray-600">No quiz results available yet.</p>
      <?php endif; ?>
    </section>

    <!-- Assignment Results -->
    <section>
      <h2 class="text-2xl font-semibold mb-4 text-green-600">Assignment Results</h2>
      <?php if ($assignment_results->num_rows > 0): ?>
        <div class="overflow-x-auto bg-white shadow rounded-lg">
          <table class="w-full text-left border-collapse">
            <thead class="bg-gray-100">
              <tr>
                <th class="p-3 border">Assignment Title</th>
                <th class="p-3 border">Score</th>
                <th class="p-3 border">Feedback</th>
                <th class="p-3 border">Submitted At</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $assignment_results->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                  <td class="p-3 border"><?= htmlspecialchars($row['assignment_title']) ?></td>
                  <td class="p-3 border"><?= htmlspecialchars($row['score']) ?>%</td>
                  <td class="p-3 border"><?= htmlspecialchars($row['feedback'] ?? '-') ?></td>
                  <td class="p-3 border"><?= date("F j, Y, g:i a", strtotime($row['submitted_at'])) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-gray-600">No assignment results available yet.</p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
