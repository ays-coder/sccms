<?php
session_start();
require_once 'db_connect.php';

// Check if user is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];
$message = "";

// Handle reminder submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reminder'])) {
    $task = mysqli_real_escape_string($conn, $_POST['task']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);

    $sql = "INSERT INTO reminders (tutor_id, task, due_date) VALUES ('$tutor_id', '$task', '$due_date')";
    if (mysqli_query($conn, $sql)) {
        $message = "Reminder added successfully!";
    }
}

// Handle schedule submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $topic = mysqli_real_escape_string($conn, $_POST['topic']);
    $class_date = mysqli_real_escape_string($conn, $_POST['class_date']);
    $class_time = mysqli_real_escape_string($conn, $_POST['class_time']);

    $sql = "INSERT INTO schedules (tutor_id, topic, class_date, class_time) VALUES ('$tutor_id', '$topic', '$class_date', '$class_time')";
    if (mysqli_query($conn, $sql)) {
        $message = "Class schedule added successfully!";
    }
}

// Fetch reminders and schedules
$reminders = mysqli_query($conn, "SELECT * FROM reminders WHERE tutor_id = $tutor_id ORDER BY due_date ASC");
$schedules = mysqli_query($conn, "SELECT * FROM schedules WHERE tutor_id = $tutor_id ORDER BY class_date ASC, class_time ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tutor Reminders & Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl font-bold text-blue-700 mb-6 text-center">Reminders & Class Schedule</h1>

        <?php if (!empty($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add Reminder -->
        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Add New Reminder</h2>
            <form method="POST">
                <input type="hidden" name="add_reminder" value="1" />
                <div class="grid md:grid-cols-2 gap-4">
                    <input type="text" name="task" required placeholder="Reminder task (e.g., Update grades)" class="p-2 border rounded w-full" />
                    <input type="date" name="due_date" required class="p-2 border rounded w-full" />
                </div>
                <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Reminder</button>
            </form>
        </div>

        <!-- Add Class Schedule -->
        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Add Class Schedule</h2>
            <form method="POST">
                <input type="hidden" name="add_schedule" value="1" />
                <div class="grid md:grid-cols-3 gap-4">
                    <input type="text" name="topic" required placeholder="Class topic" class="p-2 border rounded w-full" />
                    <input type="date" name="class_date" required class="p-2 border rounded w-full" />
                    <input type="time" name="class_time" required class="p-2 border rounded w-full" />
                </div>
                <button type="submit" class="mt-4 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Schedule</button>
            </form>
        </div>

        <!-- Reminder List -->
        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Your Reminders</h2>
            <?php if (mysqli_num_rows($reminders) > 0): ?>
                <ul class="list-disc pl-6 space-y-2 text-gray-700">
                    <?php while ($r = mysqli_fetch_assoc($reminders)): ?>
                        <li><?= htmlspecialchars($r['task']) ?> – <span class="text-sm text-gray-500">(Due: <?= htmlspecialchars($r['due_date']) ?>)</span></li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-500">No reminders added.</p>
            <?php endif; ?>
        </div>

        <!-- Class Schedule List -->
        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Class Schedule</h2>
            <?php if (mysqli_num_rows($schedules) > 0): ?>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100">
                            <th class="p-2 border">Topic</th>
                            <th class="p-2 border">Date</th>
                            <th class="p-2 border">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = mysqli_fetch_assoc($schedules)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-2 border"><?= htmlspecialchars($s['topic']) ?></td>
                                <td class="p-2 border"><?= htmlspecialchars($s['class_date']) ?></td>
                                <td class="p-2 border"><?= htmlspecialchars($s['class_time']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">No class schedules found.</p>
            <?php endif; ?>
        </div>

        <div class="text-center mt-6">
            <a href="tutor_dashboard.php" class="text-blue-600 hover:underline">← Back to Tutor Dashboard</a>
        </div>
    </div>
</body>
</html>
