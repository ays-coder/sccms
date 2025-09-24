<?php
session_start();
require_once 'db_connect.php';
require_once 'libs/phpqrcode/qrlib.php';

// Only admin or tutor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','tutor'])) {
    header("Location: login.php");
    exit();
}

$qrGenerated = false;
$qrLink = '';
$successMsg = '';
$message = '';
$selectedCourse = null;

// Handle manual attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'], $_POST['course_id'], $_POST['date'], $_POST['status'])) {
    $student_id = intval($_POST['student_id']);
    $course_id = intval($_POST['course_id']);
    $date = $_POST['date'];
    $status = $_POST['status'];
    $marked_by = $_SESSION['user_id'];

    if (!in_array($status, ['Present', 'Absent', 'Late'])) {
        $message = "Invalid attendance status.";
    } else {
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, course_id, date, status, marked_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $student_id, $course_id, $date, $status, $marked_by);
        if ($stmt->execute()) {
            $message = "Attendance marked successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle QR code generation via GET
if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    $courseStmt = $conn->prepare("SELECT course_id, course_name FROM courses WHERE course_id = ?");
    $courseStmt->bind_param("i", $course_id);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $selectedCourse = $courseResult->fetch_assoc();

    if ($selectedCourse) {
        $qrData = json_encode([
            'course_id' => $selectedCourse['course_id'],
            'course_name' => $selectedCourse['course_name'],
            'timestamp' => time()
        ]);
        $uniqueCode = md5($qrData . uniqid('', true));
        $qrLink = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($qrData) . "&choe=UTF-8";
        $qrGenerated = true;

        // Save QR code in DB
        $stmt = $conn->prepare("INSERT INTO attendance (course_id, qr_code_data) VALUES (?, ?)");
        $stmt->bind_param("is", $course_id, $uniqueCode);
        $stmt->execute();
    }
}

// Fetch students and courses for dropdowns
$students = $conn->query("SELECT user_id, username FROM users WHERE role='student' ORDER BY username");
$courses = $conn->query("SELECT course_id, course_name FROM courses ORDER BY course_name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Monitor Attendance | Admin/Tutor</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
function autoGenerateQR(selectObj) {
    var courseId = selectObj.value;
    if (courseId) window.location.href = "?course_id=" + courseId;
    else window.location.href = "monitor_attendance.php";
}
</script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

<header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-blue-700">Monitor Attendance</h1>
    <a href="<?= ($_SESSION['role']=='admin')?'admin_dashboard.php':'tutor_dashboard.php' ?>" class="text-blue-600 hover:underline">← Back to Dashboard</a>
</header>

<main class="max-w-3xl mx-auto py-10 px-4 space-y-10">

    <!-- Manual Attendance Form -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-semibold mb-6">Mark Attendance Manually</h2>
        <?php if ($message): ?>
            <div class="mb-4 text-center text-<?= strpos($message,'success')!==false?'green':'red' ?>-600 font-semibold"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block mb-1 font-medium">Student</label>
                <select name="student_id" class="border rounded p-2 w-full" required>
                    <option value="">Select Student</option>
                    <?php while($row=$students->fetch_assoc()): ?>
                        <option value="<?= $row['user_id'] ?>"><?= htmlspecialchars($row['username']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block mb-1 font-medium">Course</label>
                <select name="course_id" class="border rounded p-2 w-full" required>
                    <option value="">Select Course</option>
                    <?php $courses->data_seek(0); while($row=$courses->fetch_assoc()): ?>
                        <option value="<?= $row['course_id'] ?>"><?= htmlspecialchars($row['course_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block mb-1 font-medium">Date</label>
                <input type="date" name="date" class="border rounded p-2 w-full" required value="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="block mb-1 font-medium">Status</label>
                <select name="status" class="border rounded p-2 w-full" required>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Late">Late</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Mark Attendance</button>
        </form>
    </div>

    <!-- QR Code Generation -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-semibold mb-6">Generate Attendance QR</h2>
        <p class="mb-4 text-gray-700">Select a course to generate its QR code link for students to scan.</p>
        <label class="block mb-4">
            <span class="text-gray-700 font-medium">Select Course</span>
            <select onchange="autoGenerateQR(this)" class="mt-1 block w-full rounded border-gray-300">
                <option value="">-- Choose Course --</option>
                <?php $courses->data_seek(0); while($row=$courses->fetch_assoc()): ?>
                    <option value="<?= $row['course_id'] ?>" <?= (isset($selectedCourse)&&$selectedCourse['course_id']==$row['course_id'])?'selected':'' ?>>
                        <?= htmlspecialchars($row['course_name']) ?> (ID: <?= $row['course_id'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </label>
        <?php if($qrGenerated && $qrLink && $selectedCourse): ?>
            <div class="mt-6 text-center">
                <p class="font-semibold mb-2">Course: <span class="text-blue-700"><?= htmlspecialchars($selectedCourse['course_name']) ?></span></p>
                <p class="mb-2">Course ID: <span class="font-mono"><?= $selectedCourse['course_id'] ?></span></p>
                <a href="<?= $qrLink ?>" target="_blank" class="text-blue-600 underline break-all"><?= $qrLink ?></a>
                <div class="mt-4">
                    <img src="<?= $qrLink ?>" alt="Attendance QR Code" class="mx-auto border p-2 bg-white shadow rounded" />
                </div>
                <p class="text-sm text-gray-600 mt-2">(Students will scan this QR code or use the link from their dashboard)</p>
            </div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>
