<?php
session_start();
require_once 'db_connect.php';

$error = "";
$edit_user = null;

// Check admin login
function check_admin_login() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: login.php");
        exit();
    }
}
check_admin_login();

// Handle delete (prevent self-delete)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id == $_SESSION['user_id']) {
        header("Location: manage_users.php?error=cannot_delete_self");
        exit();
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}

// Load edit user data
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
}

// Handle new user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!empty($username) && !empty($email) && !empty($_POST['password']) && !empty($role)) {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $username, $email, $password, $role);
        $stmt->execute();
        header("Location: manage_users.php");
        exit();
    } else {
        $error = "All fields are required to create a new user.";
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE user_id=?");
        $stmt->bind_param("ssssi", $username, $email, $role, $password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
        $stmt->bind_param("sssi", $username, $email, $role, $user_id);
    }
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}

// Fetch users
$students = $conn->query("SELECT * FROM users WHERE role = 'student'");
$parents  = $conn->query("SELECT * FROM users WHERE role = 'parent'");
$tutors   = $conn->query("SELECT * FROM users WHERE role = 'tutor'");
$admins   = $conn->query("SELECT * FROM users WHERE role = 'admin'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users | Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <style>body { font-family: 'Public Sans', sans-serif; }</style>
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

    <?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_delete_self'): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center font-semibold">
        You cannot delete your own admin account while logged in.
      </div>
    <?php endif; ?>

    <!-- Create User Form -->
    <div class="bg-white p-6 shadow rounded-lg mb-10">
      <h2 class="text-2xl font-semibold mb-4 text-blue-700">Create New User</h2>
      <?php if (!empty($error)): ?>
        <p class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input type="text" name="username" placeholder="Username" class="border rounded p-2" required>
        <input type="email" name="email" placeholder="Email" class="border rounded p-2" required>
        <input type="password" name="password" placeholder="Password" class="border rounded p-2" required>
        <select name="role" class="border rounded p-2" required>
          <option value="">Select Role</option>
          <option value="admin">Admin</option>
          <option value="tutor">Tutor</option>
          <option value="student">Student</option>
          <option value="parent">Parent</option>
        </select>
        <button type="submit" name="create_user" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add</button>
      </form>
    </div>

    <!-- Edit User Form -->
    <?php if ($edit_user): ?>
      <div class="bg-yellow-50 p-6 shadow rounded-lg mb-10">
        <h2 class="text-2xl font-semibold mb-4 text-yellow-700">Edit User (<?= htmlspecialchars($edit_user['username']) ?>)</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
          <input type="text" name="username" value="<?= htmlspecialchars($edit_user['username']) ?>" class="border rounded p-2" required>
          <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" class="border rounded p-2" required>
          <input type="password" name="password" placeholder="New Password (leave blank to keep)" class="border rounded p-2">
          <select name="role" class="border rounded p-2" required>
            <option value="admin" <?= $edit_user['role']=='admin'?'selected':'' ?>>Admin</option>
            <option value="tutor" <?= $edit_user['role']=='tutor'?'selected':'' ?>>Tutor</option>
            <option value="student" <?= $edit_user['role']=='student'?'selected':'' ?>>Student</option>
            <option value="parent" <?= $edit_user['role']=='parent'?'selected':'' ?>>Parent</option>
          </select>
          <button type="submit" name="update_user" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Update</button>
        </form>
      </div>
    <?php endif; ?>

    <h1 class="text-3xl font-bold mb-8 text-center">All User Roles</h1>

    <div class="flex flex-col gap-8">
      <?php
      $roles = [
        'Admins' => $admins,
        'Tutors' => $tutors,
        'Students' => $students,
        'Parents' => $parents
      ];
      foreach ($roles as $title => $result):
      ?>
        <div class="bg-white shadow rounded-lg p-4">
          <h2 class="text-xl font-semibold text-blue-700 mb-4"><?= $title ?></h2>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
              <thead class="bg-blue-100 text-blue-800 uppercase text-xs">
                <tr>
                  <th class="px-4 py-2">User ID</th>
                  <th class="px-4 py-2">Username</th>
                  <th class="px-4 py-2">Email</th>
                  <th class="px-4 py-2">Role</th>
                  <th class="px-4 py-2">Created</th>
                  <th class="px-4 py-2 text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                      <td class="px-4 py-2"><?= $row['user_id'] ?></td>
                      <td class="px-4 py-2"><?= htmlspecialchars($row['username']) ?></td>
                      <td class="px-4 py-2"><?= htmlspecialchars($row['email']) ?></td>
                      <td class="px-4 py-2"><?= htmlspecialchars($row['role']) ?></td>
                      <td class="px-4 py-2"><?= $row['created_at'] ?></td>
                      <td class="px-4 py-2 text-right flex gap-2 justify-end">
                        <a href="?edit=<?= $row['user_id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-xs">Edit</a>
                        <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                          <a href="?delete=<?= $row['user_id'] ?>" onclick="return confirm('Delete this user?');"
                             class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs">Delete</a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="px-4 py-2 text-center text-gray-500">No <?= strtolower($title) ?> found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-8 text-center">
      <a href="admin_dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
    </div>
  </main>
</body>
</html>
