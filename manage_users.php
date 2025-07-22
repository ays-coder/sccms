 <?php
session_start();
require_once 'db_connect.php';

// Check admin login
function check_admin_login() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: login.php");
        exit();
    }
}
check_admin_login();

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}

// Fetch student users
$students = $conn->query("SELECT * FROM users WHERE role = 'student'");

// Fetch parent users
$parents = $conn->query("SELECT * FROM users WHERE role = 'parent'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Public Sans', sans-serif; }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 48 48"><path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" /></svg>
      <span class="text-xl font-bold text-blue-700">Manage Users</span>
    </div>
    <div>
      <span class="mr-4 text-gray-700 font-semibold"><?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)</span>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
    </div>
  </header>

  <main class="max-w-7xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-8 text-center">Student & Parent Users</h1>

    <div class="flex flex-col md:flex-row gap-8">
      <!-- Student Users -->
      <div class="w-full md:w-1/2 bg-white shadow rounded-lg p-4">
        <h2 class="text-xl font-semibold text-blue-700 mb-4">Students</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-left text-gray-600">
            <thead class="bg-blue-100 text-blue-800 uppercase text-xs">
              <tr>
                <th class="px-4 py-2">User ID</th>
                <th class="px-4 py-2">Username</th>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Created</th>
                <th class="px-4 py-2 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($students && $students->num_rows > 0): ?>
                <?php while($row = $students->fetch_assoc()): ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2"><?= $row['user_id'] ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['username']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="px-4 py-2"><?= $row['created_at'] ?></td>
                    <td class="px-4 py-2 text-right">
                      <a href="?delete=<?= $row['user_id'] ?>" onclick="return confirm('Delete this student?');"
                         class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs">Delete</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No students found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Parent Users -->
      <div class="w-full md:w-1/2 bg-white shadow rounded-lg p-4">
        <h2 class="text-xl font-semibold text-blue-700 mb-4">Parents</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-left text-gray-600">
            <thead class="bg-blue-100 text-blue-800 uppercase text-xs">
              <tr>
                <th class="px-4 py-2">User ID</th>
                <th class="px-4 py-2">Username</th>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Created</th>
                <th class="px-4 py-2 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($parents && $parents->num_rows > 0): ?>
                <?php while($row = $parents->fetch_assoc()): ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2"><?= $row['user_id'] ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['username']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="px-4 py-2"><?= $row['created_at'] ?></td>
                    <td class="px-4 py-2 text-right">
                      <a href="?delete=<?= $row['user_id'] ?>" onclick="return confirm('Delete this parent?');"
                         class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs">Delete</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No parents found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="mt-8 text-center">
      <a href="admin_dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
    </div>
  </main>
</body>
</html>
