<?php
session_start();
require_once 'db_connect.php';

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch payment records
$sql = "SELECT payment_id, student_id, amount, status, paid_at FROM payments";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Payments | Admin | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Public Sans', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 48 48">
        <path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4H7.01134C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44H42.4379Z"/>
      </svg>
      <span class="text-xl font-bold text-blue-700">Smart Commerce Core - Admin</span>
    </div>
    <div>
      <a href="admin_dashboard.php" class="text-sm text-blue-600 hover:underline mr-4">Dashboard</a>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Manage Payments</h1>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100 text-gray-700 text-left text-sm uppercase font-semibold">
          <tr>
            <th class="px-6 py-3">Payment ID</th>
            <th class="px-6 py-3">Student ID</th>
            <th class="px-6 py-3">Amount</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Paid At</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4"><?= htmlspecialchars($row['payment_id']) ?></td>
                <td class="px-6 py-4"><?= htmlspecialchars($row['student_id']) ?></td>
                <td class="px-6 py-4">Rs. <?= htmlspecialchars($row['amount']) ?></td>
                <td class="px-6 py-4">
                  <span class="<?= $row['status'] === 'paid' ? 'text-green-600 font-semibold' : 'text-red-500 font-semibold' ?>">
                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                  </span>
                </td>
                <td class="px-6 py-4"><?= htmlspecialchars($row['paid_at']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="px-6 py-4 text-center text-gray-500">No payment records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="text-center mt-6">
      <a href="admin_dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
    </div>
  </main>
</body>
</html>
