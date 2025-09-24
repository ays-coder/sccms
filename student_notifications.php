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

// Fetch student details
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

// Fetch notifications (only for this student)
$query = "
    SELECT 
        n.notification_id,
        n.user_id AS receiver_id,
        n.title,
        n.message,
        n.seen,
        n.created_at,
        n.sender,
        u.username AS sender_name,
        u.role AS sender_role
    FROM notifications n
    LEFT JOIN users u ON n.sender = u.user_id
    WHERE n.user_id = $user_id
    ORDER BY n.created_at DESC
";
$notifications = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Notifications | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

  <!-- Header -->
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-teal-600 text-3xl">notifications</span>
      <span class="text-xl font-bold text-teal-700">Notifications</span>
    </div>
    <div class="text-right">
      <div class="text-gray-700 font-semibold">
        <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($email) ?>)
      </div>
      <a href="student_dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mt-2 inline-block">Back to Dashboard</a>
    </div>
  </header>

  <!-- Main -->
  <main class="max-w-5xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-6 text-center">Your Notifications</h1>

    <?php if ($notifications && $notifications->num_rows > 0): ?>
      <div class="space-y-6">
        <?php while ($row = $notifications->fetch_assoc()): ?>
          <div class="bg-white shadow rounded-lg p-6 border-l-4 
            <?= ($row['sender_role'] ?? '') === 'admin' ? 'border-red-500' : 'border-green-500' ?>">
            
            <div class="flex justify-between items-center mb-2">
              <h2 class="text-lg font-semibold text-gray-800">
                <?= htmlspecialchars($row['title']) ?>
              </h2>
              <span class="text-sm text-gray-500">
                <?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?>
              </span>
            </div>

            <p class="text-gray-700 mb-2"><?= nl2br(htmlspecialchars($row['message'])) ?></p>
            
            <p class="text-sm text-gray-500">
              Sent by: <span class="font-semibold"><?= ucfirst($row['sender_role'] ?? 'System') ?></span>
              (<?= htmlspecialchars($row['sender_name'] ?? 'System') ?>)
            </p>

            <?php if ($row['seen'] == 0): ?>
              <span class="inline-block mt-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">New</span>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-600 text-center">No notifications available at the moment.</p>
    <?php endif; ?>
  </main>
</body>
</html>
