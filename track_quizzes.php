<?php
session_start();
require_once 'db_connect.php';

// Ensure parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

// Ensure child is verified
if (!isset($_SESSION['child_id'])) {
    header("Location: verify_child.php");
    exit();
}

$child_id = $_SESSION['child_id'];
$parent_username = $_SESSION['username'];
$parent_email = $_SESSION['email'];

// Fetch all quizzes and match with quiz_results of the child
$sql = "
    SELECT 
        q.quiz_id,
        q.title,
        q.created_at,
        c.course_name,
        IFNULL(r.score, 'Not Attempted') AS score,
        r.attempted_at
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    LEFT JOIN quiz_results r 
        ON q.quiz_id = r.quiz_id AND r.student_id = ?
    ORDER BY q.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Quizzes | Smart Commerce Core</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <span class="material-icons text-blue-600 text-3xl">quiz</span>
            <h1 class="text-xl font-bold text-gray-800">Quizzes Tracker</h1>
        </div>
        <div>
            <span class="text-gray-700 font-semibold mr-4"><?= htmlspecialchars($parent_username) ?> (<?= htmlspecialchars($parent_email) ?>)</span>
            <a href="parent_dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Back to Dashboard</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto py-8 px-4">
        <h2 class="text-2xl font-bold text-center mb-6">Quiz Progress for Your Child</h2>

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="px-4 py-2">Quiz Title</th>
                        <th class="px-4 py-2">Course</th>
                        <th class="px-4 py-2">Created Date</th>
                        <th class="px-4 py-2">Attempt Status</th>
                        <th class="px-4 py-2">Score</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['title']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['course_name']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['created_at']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($row['score'] === 'Not Attempted'): ?>
                                        <span class="text-red-600 font-semibold">Pending</span>
                                    <?php else: ?>
                                        <span class="text-green-600 font-semibold">Attempted on <?= htmlspecialchars($row['attempted_at']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?= $row['score'] === 'Not Attempted' ? '-' : htmlspecialchars($row['score']) . '%' ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-6 text-gray-500">No quizzes found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
