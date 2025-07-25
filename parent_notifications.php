<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in or not parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$parent_email = $_SESSION['email'];

// Get child's email from 'parent_of'
$stmt = $conn->prepare("SELECT parent_of FROM users WHERE email = ?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$result = $stmt->get_result();
$child_email = null;

if ($result && $row = $result->fetch_assoc()) {
    $child_email = $row['parent_of'];
}

// Get child's user_id
$child_id = null;
if ($child_email) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $child_email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $child = $res->fetch_assoc()) {
        $child_id = $child['user_id'];
    }
}

// Fetch notifications for the parent's child
$notification_result = null;
if ($child_id) {
    $notification_result = $conn->query("
        SELECT title, message, created_at 
        FROM notifications 
        WHERE recipient_id = $child_id
        ORDER BY created_at DESC
    ");
}

// Fetch payment notifications for the parent's child
$payment_result = null;
if ($child_id) {
    $stmt = $conn->prepare("
        SELECT amount, status, paid_at 
        FROM payments 
        WHERE student_id = ? 
        ORDER BY paid_at DESC
    ");
    $stmt->bind_param("i", $child_id);
    $stmt->execute();
    $payment_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Notifications - Smart Commerce Core</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="max-w-5xl mx-auto p-6">

        <!-- Back to Dashboard -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">📩 Parent Notifications</h1>
            <a href="parent_dashboard.php" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                🔙 Back to Dashboard
            </a>
        </div>

        <!-- General Notifications -->
        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">📢 Messages for Your Child</h2>
            <div class="bg-white shadow-md rounded p-4">
                <?php if ($notification_result && $notification_result->num_rows > 0): ?>
                    <ul class="space-y-4">
                        <?php while ($row = $notification_result->fetch_assoc()): ?>
                            <li class="border-b pb-2">
                                <h3 class="font-bold text-lg text-indigo-700"><?= htmlspecialchars($row['title']) ?></h3>
                                <p class="text-gray-700"><?= htmlspecialchars($row['message']) ?></p>
                                <p class="text-sm text-gray-400">Sent on <?= date('F j, Y, g:i A', strtotime($row['created_at'])) ?></p>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500">No notifications available for your child.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Payment Notifications -->
        <section>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">💰 Payment Notifications</h2>
            <div class="bg-white shadow-md rounded p-4">
                <?php if ($payment_result && $payment_result->num_rows > 0): ?>
                    <ul class="space-y-4">
                        <?php while ($row = $payment_result->fetch_assoc()): ?>
                            <li class="border-b pb-2">
                                <?php if ($row['status'] === 'paid'): ?>
                                    <p class="text-green-700 font-semibold">
                                        ✅ Your payment of <strong>LKR <?= number_format($row['amount'], 2) ?></strong> was successfully completed on 
                                        <?= date('F j, Y h:i A', strtotime($row['paid_at'])) ?>.
                                    </p>
                                <?php else: ?>
                                    <p class="text-red-600 font-semibold">
                                        ❗ Your payment of <strong>LKR <?= number_format($row['amount'], 2) ?></strong> is still pending. 
                                        Please complete it before the deadline.
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500">No payment notifications available.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
