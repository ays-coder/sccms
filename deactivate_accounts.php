<?php
session_start();
require_once 'db_connect.php';

// Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Query: Students with last payment older than 2 months OR no payments
$query = "
    SELECT u.user_id, u.username, u.email, u.status,
           MAX(p.payment_date) AS last_payment_date
    FROM users u
    LEFT JOIN course_registrations cr ON u.user_id = cr.student_id
    LEFT JOIN payments p ON cr.registration_id = p.registration_id
    WHERE u.role = 'student'
    GROUP BY u.user_id, u.username, u.email, u.status
    HAVING last_payment_date IS NULL OR last_payment_date < DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
    ORDER BY last_payment_date ASC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deactivate Accounts - Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-blue-700">Overdue Payment Accounts</h1>
        <a href="admin_dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Back</a>
    </header>

    <main class="max-w-6xl mx-auto py-8 px-4">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-4">Students Late on Payments (Over 2 Months)</h2>

            <?php if ($result->num_rows > 0): ?>
                <table class="w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">Name</th>
                            <th class="border p-2">Email</th>
                            <th class="border p-2">Last Payment Date</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="border p-2"><?= htmlspecialchars($row['username']) ?></td>
                                <td class="border p-2"><?= htmlspecialchars($row['email']) ?></td>
                                <td class="border p-2"><?= $row['last_payment_date'] ? $row['last_payment_date'] : 'Never Paid' ?></td>
                                <td class="border p-2">
                                    <?= $row['status'] == 'active' ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Deactivated</span>' ?>
                                </td>
                                <td class="border p-2 flex gap-2">
                                    <form method="POST" action="update_account_status.php">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Activate</button>
                                    </form>
                                    <form method="POST" action="update_account_status.php">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="status" value="deactivated">
                                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Deactivate</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">No overdue accounts found.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
