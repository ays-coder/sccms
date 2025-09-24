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

// Fetch available quizzes
$sql = "SELECT q.quiz_id, q.title, q.created_at, c.course_name
        FROM quizzes q
        LEFT JOIN courses c ON q.course_id = c.course_id
        ORDER BY q.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Available Quizzes | Student Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

  <!-- Header -->
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-orange-600 text-3xl">task_alt</span>
      <span class="text-xl font-bold text-orange-700">Available Quizzes</span>
    </div>
    <div class="text-right">
      <div class="text-gray-700 font-semibold">
        <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($email) ?>)
      </div>
      <a href="student_dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 inline-block mt-2">Back to Dashboard</a>
    </div>
  </header>

  <!-- Main -->
  <main class="max-w-5xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-6 text-center">Quizzes You Can Take</h1>

    <?php if ($result->num_rows > 0): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
            <h2 class="text-xl font-semibold text-gray-800 mb-2"><?= htmlspecialchars($row['title']) ?></h2>
            <p class="text-gray-600 mb-1"><strong>Course:</strong> <?= htmlspecialchars($row['course_name'] ?? 'N/A') ?></p>
            <p class="text-gray-500 text-sm mb-4">Uploaded on <?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></p>
            <a href="attempt_quiz.php?quiz_id=<?= $row['quiz_id'] ?>" 
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Start Quiz</a>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="text-center text-gray-600 mt-10">No quizzes available at the moment.</p>
    <?php endif; ?>
  </main>
</body>
</html>
