 <?php
session_start();
require_once 'db_connect.php';

// Ensure only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Student Dashboard | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    body { font-family: 'Public Sans', sans-serif; }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-blue-600 text-3xl">school</span>
      <span class="text-xl font-bold text-blue-700">Smart Commerce Core - Student</span>
    </div>
    <div>
      <span class="mr-4 text-gray-700 font-semibold"><?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)</span>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
    </div>
  </header>

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

    </div>
    <div class="mt-12 text-center">
      <a href="index.php" class="text-blue-600 hover:underline">← Back to Home</a>
    </div>
  </main>
</body>
</html>