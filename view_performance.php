<?php
session_start();
require_once 'db_connect.php';

// Ensure only tutors can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

// Handle feedback submission in same file
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['result_id'])) {
    $result_id = intval($_POST['result_id']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    $update = "UPDATE quiz_results SET feedback = '$feedback' WHERE result_id = $result_id";
    mysqli_query($conn, $update);
    $message = "Feedback updated successfully.";
}

// Fetch all quiz results with joins
$sql = "SELECT qr.result_id, qr.student_id, qr.quiz_id, qr.score, qr.feedback, qr.taken_at, 
               u.username AS student_name, q.title AS quiz_title, c.course_name
        FROM quiz_results qr
        JOIN users u ON qr.student_id = u.user_id
        JOIN quizzes q ON qr.quiz_id = q.quiz_id
        JOIN courses c ON q.course_id = c.course_id
        ORDER BY qr.taken_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Performance | Tutor Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-6xl mx-auto px-6 py-10">
    <h1 class="text-3xl font-bold mb-6 text-center text-blue-700">Student Performance</h1>

    <?php if (!empty($message)): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
      <div class="overflow-x-auto">
        <table class="w-full bg-white shadow-md rounded">
          <thead>
            <tr class="bg-blue-100 text-left">
              <th class="p-3">Student Name</th>
              <th class="p-3">Course</th>
              <th class="p-3">Quiz Title</th>
              <th class="p-3">Score</th>
              <th class="p-3">Taken At</th>
              <th class="p-3">Feedback</th>
              <th class="p-3">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="p-3"><?= htmlspecialchars($row['student_name']) ?></td>
                <td class="p-3"><?= htmlspecialchars($row['course_name']) ?></td>
                <td class="p-3"><?= htmlspecialchars($row['quiz_title']) ?></td>
                <td class="p-3 font-semibold"><?= htmlspecialchars($row['score']) ?></td>
                <td class="p-3 text-sm text-gray-500"><?= htmlspecialchars($row['taken_at']) ?></td>
                <td class="p-3">
                  <form method="post" action="view_performance.php">
                    <input type="hidden" name="result_id" value="<?= $row['result_id'] ?>">
                    <textarea name="feedback" class="w-full p-1 border rounded text-sm" rows="2"><?= htmlspecialchars($row['feedback']) ?></textarea>
                </td>
                <td class="p-3">
                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">Submit</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="bg-yellow-100 p-4 rounded text-yellow-800 text-center">
        No quiz results found.
      </div>
    <?php endif; ?>

    <div class="mt-8 text-center">
      <a href="tutor_dashboard.php" class="text-blue-600 hover:underline">← Back to Tutor Dashboard</a>
    </div>
  </div>
</body>
</html>