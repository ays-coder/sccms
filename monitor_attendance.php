<?php


session_start();
require_once 'db_connect.php';
require_once 'libs/phpqrcode/qrlib.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$qrGenerated = false;
$qrLink = '';
$successMsg = '';
$selectedCourse = null;

// If a course is selected via GET (AJAX or page reload), generate QR link automatically
if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);

    // Fetch course info for display
    $courseStmt = $conn->prepare("SELECT course_id, course_name FROM courses WHERE course_id = ?");
    $courseStmt->bind_param("i", $course_id);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $selectedCourse = $courseResult->fetch_assoc();

    if ($selectedCourse) {
        // QR data includes course id and name for clarity
        $qrData = json_encode([
            'course_id' => $selectedCourse['course_id'],
            'course_name' => $selectedCourse['course_name'],
            'timestamp' => time()
        ]);
        $uniqueCode = md5($qrData . uniqid('', true));
        // Instead of generating a PNG, generate a QR link (data URL)
        $qrLink = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($qrData) . "&choe=UTF-8";
        $qrGenerated = true;

        // Save into DB
        $stmt = $conn->prepare("INSERT INTO attendance (course_id, qr_code_data) VALUES (?, ?)");
        $stmt->bind_param("is", $course_id, $uniqueCode);
        $stmt->execute();

        $successMsg = "QR Code link generated successfully for course: " . htmlspecialchars($selectedCourse['course_name']) . " (ID: " . $selectedCourse['course_id'] . ")";
    }
}

// Get all courses for dropdown
$courseOptions = $conn->query("SELECT course_id, course_name FROM courses");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Generate Attendance QR | Admin</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    function autoGenerateQR(selectObj) {
      var courseId = selectObj.value;
      if (courseId) {
        window.location.href = "?course_id=" + courseId;
      } else {
        window.location.href = "monitor_attendance.php";
      }
    }
  </script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-blue-700">Generate Attendance QR</h1>
    <a href="admin_dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
  </header>

  <main class="max-w-3xl mx-auto py-10 px-4">
    <h2 class="text-2xl font-semibold mb-6">Create QR Code Link for Attendance</h2>
    <p class="mb-4 text-gray-700">Select a course to automatically generate and display its QR code link.</p>

    <?php if ($successMsg): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4"><?= $successMsg ?></div>
    <?php endif; ?>

    <form method="GET" class="bg-white p-6 rounded shadow space-y-4">
      <label class="block">
        <span class="text-gray-700 font-medium">Select Course</span>
        <select name="course_id" required class="mt-1 block w-full rounded border-gray-300"
          onchange="autoGenerateQR(this)">
          <option value="">-- Choose Course --</option>
          <?php
          // Reset pointer for dropdown
          $courseOptions->data_seek(0);
          while ($row = $courseOptions->fetch_assoc()): ?>
            <option value="<?= $row['course_id'] ?>" <?= (isset($selectedCourse) && $selectedCourse['course_id'] == $row['course_id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($row['course_name']) ?> (ID: <?= $row['course_id'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </label>
    </form>

    <?php if ($qrGenerated && $qrLink && $selectedCourse): ?>
      <div class="mt-8 text-center">
        <p class="font-semibold mb-2">Course: <span class="text-blue-700"><?= htmlspecialchars($selectedCourse['course_name']) ?></span></p>
        <p class="mb-2">Course ID: <span class="font-mono"><?= $selectedCourse['course_id'] ?></span></p>
        <a href="<?= $qrLink ?>" target="_blank" class="text-blue-600 underline break-all"><?= $qrLink ?></a>
        <div class="mt-4">
          <img src="<?= $qrLink ?>" alt="Attendance QR Code" class="mx-auto border p-2 bg-white shadow rounded" />
        </div>
        <p class="text-sm text-gray-600 mt-2">(Students will scan this QR code or use the link from their dashboard)</p>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>