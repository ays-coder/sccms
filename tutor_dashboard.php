<?php
session_start();
require_once 'db_connect.php';

// Check if user is a tutor
function check_tutor_login() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
        header("Location: login.php");
        exit();
    }
}
check_tutor_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Tutor Dashboard | Smart Commerce Core</title>
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
      <span class="text-xl font-bold text-blue-700">Smart Commerce Core - Tutor</span>
    </div>
    <div>
      <span class="mr-4 text-gray-700 font-semibold"><?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)</span>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
    </div>
  </header>

  <main class="max-w-5xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-8 text-center">Tutor Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

      <!-- Upload Study Materials -->
      <a href="upload_materials.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-green-600 text-4xl mb-2">upload_file</span>
        <span class="font-semibold text-lg mb-1">Upload Materials</span>
        <span class="text-gray-500 text-sm text-center">Upload notes, past papers, or files for students.</span>
      </a>

      <!-- Manage Quizzes -->
      <a href="manage_quizzes.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-indigo-600 text-4xl mb-2">quiz</span>
        <span class="font-semibold text-lg mb-1">Manage Quizzes</span>
        <span class="text-gray-500 text-sm text-center">Create or auto-grade quizzes for students.</span>
      </a>

      <!-- Assignments -->
      <a href="manage_assignments.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-purple-600 text-4xl mb-2">assignment</span>
        <span class="font-semibold text-lg mb-1">Assignments</span>
        <span class="text-gray-500 text-sm text-center">Create and view student assignment submissions.</span>
      </a>

      <!-- Student Performance -->
      <a href="view_performance.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-orange-600 text-4xl mb-2">insights</span>
        <span class="font-semibold text-lg mb-1">Student Performance</span>
        <span class="text-gray-500 text-sm text-center">Review grades and give feedback to students.</span>
      </a>

      <!-- Communication -->
      <a href="tutor_messages.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-teal-600 text-4xl mb-2">chat</span>
        <span class="font-semibold text-lg mb-1">Messages</span>
        <span class="text-gray-500 text-sm text-center">Chat or send notices to students.</span>
      </a>

      <!-- Reminders -->
      <a href="update_reminders.php" class="bg-white rounded-lg shadow p-6 flex flex-col items-center hover:shadow-lg transition">
        <span class="material-icons text-red-600 text-4xl mb-2">event_note</span>
        <span class="font-semibold text-lg mb-1">Reminders</span>
        <span class="text-gray-500 text-sm text-center">Update grades, upload new materials timely.</span>
      </a>

    </div>
    <div class="mt-12 text-center">
      <a href="index.php" class="text-blue-600 hover:underline">← Back to Home</a>
    </div>
  </main>
</body>
</html>
