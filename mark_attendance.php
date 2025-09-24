<?php
session_start();
require_once 'db_connect.php';

// Ensure student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

if (isset($_GET['data'])) {
    // QR encodes URL like: mark_attendance.php?data={JSON}
    $qrDataJson = urldecode($_GET['data']);
    $qrData = json_decode($qrDataJson, true);

    if ($qrData && isset($qrData['course_id'], $qrData['timestamp'])) {
        $course_id = intval($qrData['course_id']);
        $unique_code = isset($qrData['unique_code']) ? $qrData['unique_code'] : '';

        // Check if student already marked attendance for this course & QR session
        $stmtCheck = $conn->prepare("SELECT * FROM attendance_records WHERE course_id=? AND student_id=? AND qr_code_data=?");
        $stmtCheck->bind_param("iis", $course_id, $student_id, $unique_code);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            $errorMsg = "You have already marked attendance for this session.";
        } else {
            // Insert attendance
            $stmtInsert = $conn->prepare("INSERT INTO attendance_records (course_id, student_id, qr_code_data, marked_at) VALUES (?, ?, ?, NOW())");
            $stmtInsert->bind_param("iis", $course_id, $student_id, $unique_code);
            if ($stmtInsert->execute()) {
                $successMsg = "Attendance marked successfully!";
            } else {
                $errorMsg = "Failed to mark attendance. Please try again.";
            }
        }
    } else {
        $errorMsg = "Invalid QR code data.";
    }
} else {
    $errorMsg = "No QR code data received.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mark Attendance</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="bg-white p-8 rounded shadow max-w-md text-center">
    <h1 class="text-2xl font-bold mb-4 text-blue-700">Attendance Status</h1>
    <?php if ($successMsg): ?>
        <p class="text-green-600 font-semibold mb-4"><?= $successMsg ?></p>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <p class="text-red-600 font-semibold mb-4"><?= $errorMsg ?></p>
    <?php endif; ?>
    <a href="student_dashboard.php" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">← Back to Dashboard</a>
</div>
</body>
</html>
