 <?php
session_start();
require_once 'db_connect.php';

// Ensure parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

// Ensure child is verified
if (!isset($_SESSION['child_id']) || !isset($_SESSION['child_email'])) {
    header("Location: verify_child.php");
    exit();
}

$parent_username = $_SESSION['username'];
$parent_email = $_SESSION['email'];
$child_id = $_SESSION['child_id'];
$child_email = $_SESSION['child_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Parent Dashboard | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style>
    body { font-family: 'Public Sans', sans-serif; }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <!-- Header -->
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-blue-600 text-3xl">supervisor_account</span>
      <span class="text-xl font-bold text-blue-700">Smart Commerce Core - Parent</span>
    </div>
    <div>
      <span class="mr-4 text-gray-700 font-semibold">
        <?= htmlspecialchars($parent_username) ?> (<?= htmlspecialchars($parent_email) ?>)
      </span>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
    </div>
  </header>

  <!-- Main Content -->
  <main class="max-w-5xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-4 text-center">Welcome, Parent</h1>
    <p class="text-center text-gray-600 mb-6">Monitoring details for: <strong><?= htmlspecialchars($child_email) ?></strong></p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

      <!-- Monitor Grades -->
      <a href="view_child_grades.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-green-600 text-4xl mb-2">grading</span>
        <span class="font-semibold text-lg mb-1">Child's Grades</span>
        <span class="text-gray-500 text-sm text-center">View subject-wise grades and performance trends.</span>
      </a>

      <!-- View Attendance -->
      <a href="view_child_attendance.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-indigo-600 text-4xl mb-2">event_available</span>
        <span class="font-semibold text-lg mb-1">Attendance</span>
        <span class="text-gray-500 text-sm text-center">Track daily and monthly attendance records.</span>
      </a>

      <!-- Assignment Tracking -->
      <a href="track_assignments.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-orange-500 text-4xl mb-2">assignment_turned_in</span>
        <span class="font-semibold text-lg mb-1">Assignments</span>
        <span class="text-gray-500 text-sm text-center">Monitor submitted and pending assignments.</span>
      </a>

      <!-- Quiz Attempts -->
      <a href="track_quizzes.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-purple-600 text-4xl mb-2">quiz</span>
        <span class="font-semibold text-lg mb-1">Quiz Attempts</span>
        <span class="text-gray-500 text-sm text-center">View quiz participation and results.</span>
      </a>

      <!-- Fee Payment & Reminders -->
      <a href="parent_payments.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-teal-600 text-4xl mb-2">payments</span>
        <span class="font-semibold text-lg mb-1">Fee Payments</span>
        <span class="text-gray-500 text-sm text-center">Pay fees online and view reminders.</span>
      </a>

      <!-- Notifications -->
      <a href="parent_notifications.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-red-500 text-4xl mb-2">notifications_active</span>
        <span class="font-semibold text-lg mb-1">Notifications</span>
        <span class="text-gray-500 text-sm text-center">Get updates on performance and deadlines.</span>
      </a>
    </div>

    <div class="mt-12 text-center">
      <a href="index.php" class="text-blue-600 hover:underline">← Back to Home</a>
    </div>
  </main>
</body>
</html>
