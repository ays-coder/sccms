 <?php 
session_start();
require_once 'C:\xampp\htdocs\sccms\sccms\db_connect.php'; 

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle course deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // First delete related materials
        $deleteMaterials = $conn->prepare("DELETE FROM materials WHERE course_id = ?");
        $deleteMaterials->bind_param("i", $delete_id);
        $deleteMaterials->execute();

        // Then delete the course
        $deleteCourse = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
        $deleteCourse->bind_param("i", $delete_id);
        $deleteCourse->execute();

        // Commit the transaction
        $conn->commit();
        $_SESSION['success'] = "Course and its materials deleted successfully.";
    } catch (Exception $e) {
        // Rollback if error
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete course: " . $e->getMessage();
    }

    header("Location: manage_courses.php");
    exit();
}

// Handle adding new course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];
    $tutor_id = $_POST['tutor_id'];

    $stmt = $conn->prepare("INSERT INTO courses (course_id, course_name, description, tutor_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $course_id, $course_name, $description, $tutor_id);
    $stmt->execute();
}

// Fetch all courses
$courses = [];
$result = $conn->query("SELECT * FROM courses");
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
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
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
      <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <!-- Add Course Form -->
    <div class="bg-white p-6 rounded shadow mb-8">
      <h2 class="text-xl font-semibold mb-4">Add New Course</h2>
      <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="number" name="course_id" placeholder="Course ID" required class="p-2 border rounded" />
        <input type="text" name="course_name" placeholder="Course Name" required class="p-2 border rounded" />
        <input type="text" name="description" placeholder="Description" required class="p-2 border rounded" />
        <input type="number" name="tutor_id" placeholder="Tutor ID" required class="p-2 border rounded" />
        <div class="md:col-span-2">
          <button type="submit" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Course</button>
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
              <th class="p-2 border">Tutor ID</th>
              <th class="p-2 border">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($courses as $course): ?>
              <tr class="hover:bg-gray-50">
                <td class="p-2 border"><?= htmlspecialchars($course['course_id']) ?></td>
                <td class="p-2 border"><?= htmlspecialchars($course['course_name']) ?></td>
                <td class="p-2 border"><?= htmlspecialchars($course['description']) ?></td>
                <td class="p-2 border"><?= htmlspecialchars($course['tutor_id']) ?></td>
                <td class="p-2 border">
                  <a href="?delete_id=<?= $course['course_id'] ?>" onclick="return confirm('Are you sure you want to delete this course and all its materials?')" class="text-red-600 hover:underline">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($courses)): ?>
              <tr><td colspan="5" class="text-center py-4 text-gray-500">No courses available.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Back Button -->
    <div class="mt-6">
      <a href="admin_dashboard.php" class="inline-block bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">← Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
