 <?php
session_start();
require_once 'db_connect.php';

function check_admin_login() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: login.php");
        exit();
    }
}

check_admin_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 48 48"><path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" /></svg>
      <span class="text-xl font-bold text-blue-700">Smart Commerce Core - Admin</span>
    </div>
    <div>
      <span class="mr-4 text-gray-700 font-semibold"><?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)</span>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold mb-8 text-center">Admin Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Manage Users -->
      <a href="manage_users.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-blue-600 text-4xl mb-2">group</span>
        <span class="font-semibold text-lg mb-1">Manage Users</span>
        <p class="text-gray-500 text-sm text-center">Approve tutors, students, parents and manage accounts.</p>
      </a>

      <!-- Manage Courses -->
      <a href="manage_courses.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-green-600 text-4xl mb-2">menu_book</span>
        <span class="font-semibold text-lg mb-1">Manage Courses</span>
        <p class="text-gray-500 text-sm text-center">Create courses, assign tutors, set academic calendars.</p>
      </a>

      <!-- Attendance & Grades -->
      <a href="monitor_attendance.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-purple-600 text-4xl mb-2">qr_code_scanner</span>
        <span class="font-semibold text-lg mb-1">Attendance & Grades</span>
        <p class="text-gray-500 text-sm text-center">Monitor QR attendance, view performance reports.</p>
      </a>

      <!-- Payments & Reports -->
      <a href="manage_payments.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-yellow-600 text-4xl mb-2">payments</span>
        <span class="font-semibold text-lg mb-1">Payments & Reports</span>
        <p class="text-gray-500 text-sm text-center">Track payments and generate financial reports.</p>
      </a>

      <!-- Deactivate Overdue Accounts -->
      <a href="deactivate_accounts.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-red-600 text-4xl mb-2">person_off</span>
        <span class="font-semibold text-lg mb-1">Deactivate Accounts</span>
        <p class="text-gray-500 text-sm text-center">Manage and deactivate overdue-payment students.</p>
      </a>
    </div>

    <div class="mt-12 text-center">
      <a href="index.php" class="text-blue-600 hover:underline">← Back to Home</a>
    </div>
  </main>
</body>
</html>
