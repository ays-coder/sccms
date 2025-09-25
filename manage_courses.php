<?php 
session_start();
require_once 'db_connect.php'; 

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle course deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    $conn->begin_transaction();
    try {
        // First delete related materials
        $deleteMaterials = $conn->prepare("DELETE FROM materials WHERE course_id = ?");
        $deleteMaterials->bind_param("s", $delete_id);
        $deleteMaterials->execute();

        // Then delete the course
        $deleteCourse = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
        $deleteCourse->bind_param("s", $delete_id);
        $deleteCourse->execute();

        $conn->commit();
        $_SESSION['success'] = "Course and its materials deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete course: " . $e->getMessage();
    }

    header("Location: manage_courses.php");
    exit();
}

// Handle adding new course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (empty($_POST['course_name']) || empty($_POST['description']) || empty($_POST['tutor_id'])) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: manage_courses.php");
        exit();
    }

    // Validate tutor exists and is active
    $tutor_check = $conn->prepare("SELECT tutor_id FROM tutor WHERE tutor_id = ? AND status = 'active'");
    $tutor_check->bind_param("s", $_POST['tutor_id']);
    $tutor_check->execute();
    if ($tutor_check->get_result()->num_rows === 0) {
        $_SESSION['error'] = "Selected tutor is not valid or not active.";
        header("Location: manage_courses.php");
        exit();
    }

    // Generate unique course ID
    $course_id = 'CRS' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);
    $tutor_id = $_POST['tutor_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO courses (course_id, course_name, description, tutor_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $course_id, $course_name, $description, $tutor_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course added successfully! Course ID: " . $course_id;
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to add course: " . $e->getMessage();
    }

    header("Location: manage_courses.php");
    exit();
}

// Fetch all active tutors for dropdown
$tutors = [];
$tutor_result = $conn->query("SELECT tutor_id, username FROM tutor WHERE status = 'active' ORDER BY username");
if ($tutor_result) {
    while ($row = $tutor_result->fetch_assoc()) {
        $tutors[] = $row;
    }
} else {
    $_SESSION['error'] = "Failed to fetch tutors list.";
}

// Fetch all courses with tutor names
$courses = [];
$result = $conn->query("
    SELECT c.*, t.username as tutor_name 
    FROM courses c 
    LEFT JOIN tutor t ON c.tutor_id = t.tutor_id
    ORDER BY c.course_id DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
} else {
    $_SESSION['error'] = "Failed to fetch courses list.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Courses</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Manage Courses</h1>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Add Course Form -->
        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Course</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Course Name</label>
                    <input type="text" name="course_name" required 
                           class="w-full p-2 border rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Tutor</label>
                    <select name="tutor_id" required 
                            class="w-full p-2 border rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">Choose a tutor</option>
                        <?php foreach ($tutors as $tutor): ?>
                            <option value="<?= htmlspecialchars($tutor['tutor_id']) ?>">
                                <?= htmlspecialchars($tutor['username']) ?> (<?= htmlspecialchars($tutor['tutor_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Course Description</label>
                    <textarea name="description" required 
                              class="w-full p-2 border rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                              rows="3"></textarea>
                </div>
                <div class="col-span-2">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors">
                        Add Course
                    </button>
                </div>
            </form>
        </div>

        <!-- Courses Table -->
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-semibold mb-4">Existing Courses</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto border border-gray-300 text-sm">
                    <thead class="bg-gray-200 text-left">
                        <tr>
                            <th class="p-2 border">Course ID</th>
                            <th class="p-2 border">Course Name</th>
                            <th class="p-2 border">Description</th>
                            <th class="p-2 border">Tutor</th>
                            <th class="p-2 border">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-2 border"><?= htmlspecialchars($course['course_id']) ?></td>
                                <td class="p-2 border"><?= htmlspecialchars($course['course_name']) ?></td>
                                <td class="p-2 border"><?= htmlspecialchars($course['description']) ?></td>
                                <td class="p-2 border">
                                    <?php if ($course['tutor_name']): ?>
                                        <?= htmlspecialchars($course['tutor_name']) ?> 
                                        <span class="text-gray-500">(<?= htmlspecialchars($course['tutor_id']) ?>)</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">No tutor assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-2 border">
                                    <a href="?delete_id=<?= urlencode($course['course_id']) ?>" 
                                       onclick="return confirm('Are you sure you want to delete this course and all its materials?')" 
                                       class="text-red-600 hover:underline">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">No courses available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="admin_dashboard.php" class="inline-block bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>