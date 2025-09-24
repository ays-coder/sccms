 <?php 
session_start();
require_once 'db_connect.php';

// Ensure only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Fetch the user's details from DB (status, username, email)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT status, username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($status, $username, $email);
$stmt->fetch();
$stmt->close();

// If account is deactivated, show notice and stop
if ($status === 'deactivated') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Account Deactivated - Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white shadow p-8 rounded max-w-lg text-center">
    <h1 class="text-2xl font-bold mb-4 text-red-600">Account Deactivated</h1>
    <p class="mb-6 text-gray-700">Your account has been deactivated due to overdue payments.</p>
    <p class="mb-6 text-gray-700">Please settle your monthly payment or contact the admin for assistance.</p>
    <a href="logout.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Logout</a>
  </div>
</body>
</html>
<?php exit(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Student Dashboard | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- Header -->
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-blue-600 text-3xl">school</span>
      <span class="text-xl font-bold text-blue-700">Smart Commerce Core - Student</span>
    </div>
    <div class="text-right">
      <div class="text-gray-700 font-semibold">
        <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($email) ?>)
      </div>
      <div class="text-sm text-gray-500">
        User ID: <span class="font-bold text-blue-600"><?= htmlspecialchars($user_id) ?></span>
      </div>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-2 inline-block">Logout</a>
    </div>
  </header>

  <!-- Main -->
  <main class="max-w-5xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-8 text-center">Student Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

      <!-- Register for Courses -->
      <a href="register_courses.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-green-600 text-4xl mb-2">app_registration</span>
        <span class="font-semibold text-lg mb-1">Register for Courses</span>
        <span class="text-gray-500 text-sm text-center">Enroll in available courses.</span>
      </a>

      <!-- Download Materials -->
      <a href="download_materials.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-indigo-600 text-4xl mb-2">download</span>
        <span class="font-semibold text-lg mb-1">Download Materials</span>
        <span class="text-gray-500 text-sm text-center">Access study notes and resources.</span>
      </a>

      <!-- Submit Assignments -->
      <a href="submit_assignments.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-purple-600 text-4xl mb-2">upload</span>
        <span class="font-semibold text-lg mb-1">Submit Assignments</span>
        <span class="text-gray-500 text-sm text-center">Upload your completed work.</span>
      </a>

      <!-- Take Quizzes -->
      <a href="take_quizzes.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-orange-600 text-4xl mb-2">task_alt</span>
        <span class="font-semibold text-lg mb-1">Take Quizzes</span>
        <span class="text-gray-500 text-sm text-center">Answer quizzes and get results instantly.</span>
      </a>

      <!-- View Performance -->
      <a href="student_performance.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-blue-600 text-4xl mb-2">insights</span>
        <span class="font-semibold text-lg mb-1">Performance Reports</span>
        <span class="text-gray-500 text-sm text-center">See your grades, attendance, and progress.</span>
      </a>

      <!-- Notifications -->
      <a href="student_notifications.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-teal-600 text-4xl mb-2">notifications</span>
        <span class="font-semibold text-lg mb-1">Notifications</span>
        <span class="text-gray-500 text-sm text-center">Check deadlines and fee status alerts.</span>
      </a>

      <!-- Fees Payment -->
      <a href="fees_payment.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-pink-600 text-4xl mb-2">payments</span>
        <span class="font-semibold text-lg mb-1">Fees Payment</span>
        <span class="text-gray-500 text-sm text-center">View payment history and settle fees.</span>
      </a>

    </div>

    <div class="mt-12 text-center">
      <a href="index.php" class="text-blue-600 hover:underline">← Back to Home</a>
    </div>
  </main>
</body>
</html>

