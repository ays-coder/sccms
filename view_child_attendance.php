<?php
session_start();
require_once 'db_connect.php';

// Ensure parent is logged in and child is verified
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent' || !isset($_SESSION['child_id'])) {
    header("Location: login.php");
    exit();
}

$child_id = $_SESSION['child_id'];
$child_email = $_SESSION['child_email'];

// Fetch child's attendance records
$stmt = $conn->prepare("SELECT a.date, a.status, c.course_name 
                        FROM attendance a 
                        JOIN courses c ON a.course_id = c.course_id 
                        WHERE a.student_id = ?
                        ORDER BY a.date DESC");
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();

$attendance_data = [];
while ($row = $result->fetch_assoc()) {
    $attendance_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Child Attendance | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-indigo-600 text-3xl">event_available</span>
      <span class="text-xl font-bold text-indigo-700">Child Attendance</span>
    </div>
    <div>
      <a href="parent_dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Back</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto py-10 px-4">
    <h1 class="text-2xl font-bold mb-6 text-center">Attendance Records for <span class="text-indigo-600"><?= htmlspecialchars($child_email) ?></span></h1>

    <?php if (count($attendance_data) > 0): ?>
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded shadow-md">
          <thead>
            <tr class="bg-indigo-100 text-indigo-700">
              <th class="px-6 py-3 text-left">Date</th>
              <th class="px-6 py-3 text-left">Course</th>
              <th class="px-6 py-3 text-left">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($attendance_data as $record): ?>
              <tr class="border-b">
                <td class="px-6 py-3"><?= htmlspecialchars($record['date']) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($record['course_name']) ?></td>
                <td class="px-6 py-3">
                  <?php if ($record['status'] === 'Present'): ?>
                    <span class="text-green-600 font-semibold">Present</span>
                  <?php else: ?>
                    <span class="text-red-500 font-semibold">Absent</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-center text-gray-600">No attendance records found for this student.</p>
    <?php endif; ?>
  </main>
</body>
</html>
