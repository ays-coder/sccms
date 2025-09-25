<?php
session_start();
require_once 'db_connect.php';

// Ensure only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Mark as read if clicked
if (isset($_GET['mark_read'])) {
    $notif_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE notifications SET seen = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $student_id);
    $stmt->execute();
    $stmt->close();

    header("Location: student_notifications.php");
    exit();
}

// Fetch notifications for this student
$stmt = $conn->prepare("SELECT notification_id, title, message, seen, created_at 
                        FROM notifications 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Notifications | Student Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-3xl mx-auto mt-10 p-6 bg-white shadow-md rounded">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">My Notifications</h2>
      <a href="student_dashboard.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded text-sm">
        ← Back to Dashboard
      </a>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <ul class="space-y-4">
        <?php while ($row = $result->fetch_assoc()): ?>
          <li class="p-4 border rounded <?= $row['seen'] ? 'bg-gray-50' : 'bg-yellow-50' ?>">
            <div class="flex justify-between items-center">
              <h3 class="font-semibold text-lg text-gray-900"><?= htmlspecialchars($row['title']) ?></h3>
              <span class="text-xs text-gray-500"><?= date("M d, Y H:i", strtotime($row['created_at'])) ?></span>
            </div>
            <p class="mt-2 text-gray-700"><?= nl2br(htmlspecialchars($row['message'])) ?></p>
            <?php if (!$row['seen']): ?>
              <a href="?mark_read=<?= $row['notification_id'] ?>" 
                 class="inline-block mt-3 bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                Mark as Read
              </a>
            <?php else: ?>
              <span class="inline-block mt-3 text-xs text-gray-500 italic">Read</span>
            <?php endif; ?>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p class="text-gray-600">No notifications yet.</p>
    <?php endif; ?>
  </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
