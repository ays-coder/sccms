 <?php
session_start();
require_once 'db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

// Check if child is verified
if (!isset($_SESSION['child_id'])) {
    header("Location: verify_child.php");
    exit();
}

$child_id = $_SESSION['child_id'];
$parent_username = $_SESSION['username'];
$parent_email = $_SESSION['email'];

// Get assignments with course name and submission status
$sql = "
    SELECT 
        a.assignment_id,
        a.title,
        a.description,
        a.due_date,
        c.course_name,
        COALESCE(s.submitted_at, 'Not Submitted') AS submission_status
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN submissions s 
        ON a.assignment_id = s.assignment_id AND s.student_id = ?
    ORDER BY a.due_date ASC
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
    <title>Track Assignments | Smart Commerce Core</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <span class="material-icons text-orange-600 text-3xl">assignment</span>
            <h1 class="text-xl font-bold text-gray-800">Assignments Tracker</h1>
        </div>
        <div>
            <span class="text-gray-700 font-semibold mr-4"><?= htmlspecialchars($parent_username) ?> (<?= htmlspecialchars($parent_email) ?>)</span>
            <a href="parent_dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Back to Dashboard</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto py-8 px-4">
        <h2 class="text-2xl font-bold text-center mb-6">Assignment Progress for Your Child</h2>

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Course</th>
                        <th class="px-4 py-2">Due Date</th>
                        <th class="px-4 py-2">Submission Status</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['title']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['course_name']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['due_date']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($row['submission_status'] === 'Not Submitted'): ?>
                                        <span class="text-red-600 font-semibold">Pending</span>
                                    <?php else: ?>
                                        <span class="text-green-600 font-semibold">Submitted on <?= htmlspecialchars($row['submission_status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-6 text-gray-500">No assignments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
