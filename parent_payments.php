<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

// Get parent's information
$parent_id = $_SESSION['user_id'];
$parent_query = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'parent'");
$parent_query->bind_param("i", $parent_id);
$parent_query->execute();
$parent_info = $parent_query->get_result()->fetch_assoc();

// Get all courses
$stmt = $conn->prepare("SELECT * FROM courses ORDER BY course_id DESC");
$stmt->execute();
$courses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registration | Parent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
        .course-card {
            transition: all 0.3s ease;
        }
        .course-card:hover {
            transform: translateY(-4px);
        }
        .register-btn {
            transition: all 0.3s ease;
        }
        .course-card:hover .register-btn {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">Course Registration</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?= htmlspecialchars($parent_info['username']) ?></span>
                    <a href="parent_dashboard.php" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <span class="material-icons-round text-sm mr-2">dashboard</span>
                        Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Available Courses</h2>
            <p class="mt-2 text-gray-600">Browse and register for our comprehensive course offerings.</p>
        </div>

        <!-- Courses Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="course-card bg-white rounded-xl shadow-sm hover:shadow-lg overflow-hidden border border-gray-100">
                    <!-- Course Header -->
                    <div class="relative p-6 border-b border-gray-100">
                        <div class="absolute top-0 right-0 mt-4 mr-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="material-icons-round text-sm mr-1">school</span>
                                Course #<?= $course['course_id'] ?>
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($course['course_name']) ?></h3>
                        <p class="text-gray-600 text-sm line-clamp-2"><?= htmlspecialchars($course['description']) ?></p>
                    </div>

                    <!-- Course Details -->
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-3">
                            <div class="flex items-center justify-between text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                                <div class="flex items-center">
                                    <span class="material-icons-round text-blue-600 mr-2">schedule</span>
                                    Duration
                                </div>
                                <span class="font-medium"><?= htmlspecialchars($course['duration'] ?? '12 weeks') ?></span>
                            </div>
                        </div>

                        <!-- Price and Register Button -->
                        <div class="pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Course Fee</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        <?php if ($course['fee'] > 0): ?>
                                            $<?= number_format($course['fee'], 2) ?>
                                        <?php else: ?>
                                            <span class="text-green-600">Free</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <a href="course_payment.php?course_id=<?= $course['course_id'] ?>" 
                                   class="register-btn inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300">
                                    <span class="material-icons-round text-sm mr-2">how_to_reg</span>
                                    Register Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($courses->num_rows === 0): ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                <span class="material-icons-round text-gray-400 text-6xl">school</span>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No Available Courses</h3>
                <p class="mt-2 text-gray-500">Check back later for new course offerings.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                &copy; <?= date('Y') ?> Smart Commerce Core. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>