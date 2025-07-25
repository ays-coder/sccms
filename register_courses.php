 <?php
session_start();
require_once 'db_connect.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$success = $error = "";

// Handle course registration with payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'], $_POST['receipt_id'], $_POST['paid_at'])) {
    $course_id = intval($_POST['course_id']);
    $receipt_id = trim($_POST['receipt_id']);
    $paid_at = $_POST['paid_at'];
    $amount = 1000; // Example course fee

    // Check if already registered
    $check_stmt = $conn->prepare("SELECT * FROM courseregistration WHERE student_id = ? AND course_id = ?");
    $check_stmt->bind_param("ii", $student_id, $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        // Insert into course registration
        $stmt = $conn->prepare("INSERT INTO courseregistration (student_id, course_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $student_id, $course_id);
        if ($stmt->execute()) {
            // Insert payment record
            $status = 'Paid';
            $payment_stmt = $conn->prepare("INSERT INTO payments (student_id, amount, receipt_id, paid_at, status) VALUES (?, ?, ?, ?, ?)");
            $payment_stmt->bind_param("iisss", $student_id, $amount, $receipt_id, $paid_at, $status);
            if ($payment_stmt->execute()) {
                $success = "Course registered and payment recorded successfully!";
            } else {
                $error = "Payment recording failed.";
            }
        } else {
            $error = "Course registration failed.";
        }
    } else {
        $error = "You are already registered for this course.";
    }
}

// Get all available courses
$courses = [];
$result = $conn->query("SELECT course_id, course_name, description FROM courses");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Register for Courses</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-blue-700">Smart Commerce Core - Register Courses</h1>
    <a href="student_dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
  </header>

  <main class="max-w-4xl mx-auto py-10 px-4">
    <h2 class="text-2xl font-semibold mb-6">Available Courses</h2>

    <?php if ($success): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($courses)): ?>
      <p class="text-gray-600">No courses available at the moment.</p>
    <?php else: ?>
      <div class="space-y-8">
        <?php foreach ($courses as $course): ?>
          <div class="bg-white p-6 rounded shadow">
            <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($course['course_name']) ?></h3>
            <p class="text-gray-700 mb-4"><?= htmlspecialchars($course['description']) ?></p>
            <form method="POST" class="space-y-4">
              <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">

              <div>
                <label class="block font-medium text-sm text-gray-700">Receipt ID</label>
                <input type="text" name="receipt_id" required class="w-full mt-1 p-2 border rounded" placeholder="Enter your receipt ID">
              </div>

              <div>
                <label class="block font-medium text-sm text-gray-700">Paid Date</label>
                <input type="date" name="paid_at" required class="w-full mt-1 p-2 border rounded">
              </div>

              <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Register & Pay
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
