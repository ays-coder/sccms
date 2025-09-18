<?php
session_start();
require_once 'db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Fetch all materials uploaded by tutors
$sql = "
    SELECT m.material_id, m.course_id, m.title, m.file_path, m.upload_date, u.username AS tutor_name
    FROM materials m
    JOIN users u ON m.uploaded_by = u.user_id
    ORDER BY m.upload_date DESC
";
$materials = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Materials | Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
        <span class="material-icons text-blue-600 text-3xl">download</span>
        <span class="text-xl font-bold text-blue-700">Download Study Materials</span>
    </div>
    <div>
        <span class="mr-4 text-gray-700 font-semibold"><?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="student_dashboard.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Back</a>
    </div>
</header>

<main class="max-w-5xl mx-auto py-10">
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2">Course ID</th>
                    <th class="px-4 py-2">Title</th>
                    <th class="px-4 py-2">Uploaded By</th>
                    <th class="px-4 py-2">Upload Date</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($materials && $materials->num_rows > 0): ?>
                    <?php while ($row = $materials->fetch_assoc()): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($row['course_id']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['title']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['tutor_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['upload_date']) ?></td>
                            <td class="px-4 py-2">
                                <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="text-blue-600 underline">
                                    View / Download
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No materials available yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
