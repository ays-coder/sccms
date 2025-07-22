<?php
require_once 'db_connect.php';
$result = $conn->query("SELECT course_id, course_name, description FROM courses");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Courses | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Public Sans', sans-serif;
    }
    :root {
      --primary: #0c7ff2;
      --secondary: #f0f2f5;
      --accent: #0a6cce;
    }
  </style>
</head>
<body class="bg-white text-gray-800">

  <!-- Header -->
  <header class="bg-white shadow sticky top-0 z-50">
    <div class="max-w-screen-xl mx-auto px-4 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 48 48"><path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" /></svg>
        <span class="text-xl font-bold text-blue-700">Smart Commerce Core</span>
      </div>
      <nav class="hidden md:flex space-x-6">
        <a href="index.php" class="text-sm font-medium hover:text-blue-600">Home</a>
        <a href="#" class="text-sm font-medium hover:text-blue-600">About</a>
        <a href="courses.php" class="text-sm font-medium text-blue-600 font-semibold">Courses</a>
        <a href="#" class="text-sm font-medium hover:text-blue-600">Contact</a>
      </nav>
      <div class="space-x-2">
        <a href="login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">Login</a>
        <a href="register.php?role=student" class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded">Register</a>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="bg-gradient-to-r from-blue-600 via-blue-500 to-blue-700 text-white text-center py-20 px-4">
    <h1 class="text-4xl md:text-5xl font-bold mb-4">Explore Our <span class="text-yellow-300">Courses</span></h1>
    <p class="max-w-xl mx-auto text-lg font-light">Empowering future commerce professionals with practical and modern learning tools.</p>
  </section>

  <!-- Courses List -->
  <main class="max-w-5xl mx-auto px-4 py-12">
    <h2 class="text-3xl font-bold text-center mb-8 text-gray-800">Available Courses</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="bg-white rounded-lg shadow hover:shadow-lg p-6 border border-gray-200 transition">
            <h3 class="text-blue-700 text-xl font-semibold mb-1"><?= htmlspecialchars($row['course_name']) ?></h3>
            <p class="text-sm text-gray-500 mb-2">Course ID: <span class="font-medium"><?= htmlspecialchars($row['course_id']) ?></span></p>
            <p class="text-gray-600 mb-4"><?= htmlspecialchars($row['description']) ?></p>
            <a href="#" class="text-blue-600 hover:underline font-medium">More details</a>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-span-full text-center text-gray-500">No courses found.</div>
      <?php endif; ?>
    </div>

    <div class="mt-12 text-center">
      <a href="index.php" class="inline-block text-blue-600 hover:underline text-sm">← Back to Home</a>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-slate-800 text-white py-10 mt-10">
    <div class="max-w-screen-xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8 text-sm">
      <div>
        <h4 class="font-semibold text-white mb-2">Smart Commerce Core</h4>
        <p class="text-slate-300">Empowering the next generation of commerce professionals.</p>
      </div>
      <div>
        <h4 class="font-semibold text-white mb-2">Quick Links</h4>
        <ul class="space-y-1">
          <li><a href="#" class="text-slate-300 hover:text-white">About</a></li>
          <li><a href="courses.php" class="text-slate-300 hover:text-white">Courses</a></li>
          <li><a href="login.php" class="text-slate-300 hover:text-white">Login</a></li>
          <li><a href="register.php" class="text-slate-300 hover:text-white">Register</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-semibold text-white mb-2">Contact</h4>
        <ul class="space-y-1">
          <li><a href="#" class="text-slate-300 hover:text-white">Email Us</a></li>
          <li><a href="#" class="text-slate-300 hover:text-white">Privacy Policy</a></li>
          <li><a href="#" class="text-slate-300 hover:text-white">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <p class="text-center text-xs text-slate-400 mt-8">© 2025 Smart Commerce Core. All rights reserved.</p>
  </footer>

</body>
</html>
