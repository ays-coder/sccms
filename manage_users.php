 <?php
session_start();
require_once 'db_connect.php';

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = "";

// Handle new user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role       = trim($_POST['role']);
    $parent_of  = isset($_POST['parent_of']) ? trim($_POST['parent_of']) : null;

    if (!empty($username) && !empty($email) && !empty($_POST['password']) && !empty($role)) {
        if ($role === 'parent' && empty($parent_of)) {
            $error = "Parent accounts must have a child's student email.";
        } else {
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, parent_of, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $username, $email, $password, $role, $parent_of);
            $stmt->execute();

            // If tutor, also insert into tutor table
            if ($role === 'tutor') {
                $new_user_id = $conn->insert_id; // get inserted user_id
                $stmtTutor = $conn->prepare("INSERT INTO tutor (user_id, name, email, created_at) VALUES (?, ?, ?, NOW())");
                $stmtTutor->bind_param("iss", $new_user_id, $username, $email);
                $stmtTutor->execute();
            }

            header("Location: manage_users.php");
            exit();
        }
    } else {
        $error = "All fields are required to create a new user.";
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}

// Fetch all users grouped by role
$users = [
    'admin'   => [],
    'tutor'   => [],
    'student' => [],
    'parent'  => []
];

$result = $conn->query("SELECT * FROM users ORDER BY role, username");
while ($row = $result->fetch_assoc()) {
    $users[$row['role']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <!-- Header -->
  <div class="bg-blue-600 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Manage Users</h1>
    <div>
      <span class="mr-4"><?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)</span>
      <a href="admin_dashboard.php" class="bg-white text-blue-600 px-4 py-2 rounded shadow hover:bg-gray-200">Back to Dashboard</a>
      <a href="logout.php" class="ml-2 bg-red-500 text-white px-4 py-2 rounded shadow hover:bg-red-600">Logout</a>
    </div>
  </div>

  <div class="max-w-6xl mx-auto p-6">
    
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

        <!-- Role Selector -->
        <select name="role" class="border rounded p-2" required onchange="toggleParentField(this.value)">
          <option value="">Select Role</option>
          <option value="admin">Admin</option>
          <option value="tutor">Tutor</option>
          <option value="student">Student</option>
          <option value="parent">Parent</option>
        </select>

        <!-- Parent Of (only if role = parent) -->
        <input type="email" name="parent_of" id="parent_of" placeholder="Child's Student Email"
               class="border rounded p-2 hidden">
        
        <button type="submit" name="create_user" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          Add User
        </button>
      </form>
    </div>

    <!-- User Lists -->
    <?php foreach ($users as $role => $list): ?>
      <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 capitalize"><?= ucfirst($role) ?>s</h2>
        <?php if (count($list) > 0): ?>
          <div class="overflow-x-auto">
            <table class="w-full bg-white shadow rounded-lg">
              <thead class="bg-gray-200">
                <tr>
                  <th class="p-3 text-left">ID</th>
                  <th class="p-3 text-left">Username</th>
                  <th class="p-3 text-left">Email</th>
                  <?php if ($role === 'parent'): ?>
                    <th class="p-3 text-left">Parent Of</th>
                  <?php endif; ?>
                  <th class="p-3 text-left">Created At</th>
                  <th class="p-3 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($list as $user): ?>
                  <tr class="border-b">
                    <td class="p-3"><?= $user['user_id'] ?></td>
                    <td class="p-3"><?= htmlspecialchars($user['username']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($user['email']) ?></td>
                    <?php if ($role === 'parent'): ?>
                      <td class="p-3"><?= htmlspecialchars($user['parent_of']) ?></td>
                    <?php endif; ?>
                    <td class="p-3"><?= $user['created_at'] ?></td>
                    <td class="p-3">
                      <a href="manage_users.php?delete=<?= $user['user_id'] ?>" 
                         onclick="return confirm('Are you sure you want to delete this user?')" 
                         class="text-red-600 hover:underline">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-gray-500">No <?= $role ?> accounts found.</p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

  </div>

<script>
function toggleParentField(role) {
  const field = document.getElementById('parent_of');
  if (role === 'parent') {
    field.classList.remove('hidden');
    field.setAttribute('required', 'true');
  } else {
    field.classList.add('hidden');
    field.removeAttribute('required');
  }
}
</script>
</body>
</html>
