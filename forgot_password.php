 <?php
// forgot_password.php
session_start();
include("db_connect.php"); // <-- create db_connect.php with your connection details

$message = "";
$step = 1; // step 1 = ask user details, step 2 = set new password

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Step 1: Validate user details
    if (isset($_POST['verify'])) {
        $username = trim($_POST['username']);
        $user_id = trim($_POST['user_id']);
        $email = trim($_POST['email']);

        $sql = "SELECT * FROM users WHERE user_id=? AND username=? AND email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $user_id, $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $_SESSION['reset_user_id'] = $user_id;
            $step = 2; // move to password reset step
        } else {
            $message = "❌ Invalid Username, User ID, or Email.";
        }
        $stmt->close();
    }

    // Step 2: Update new password
    if (isset($_POST['reset_password'])) {
        if (isset($_SESSION['reset_user_id'])) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $user_id = $_SESSION['reset_user_id'];

            $sql = "UPDATE users SET password=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $new_password, $user_id);

            if ($stmt->execute()) {
                $message = "✅ Password updated successfully. You can now <a href='login.php'>login</a>.";
                unset($_SESSION['reset_user_id']);
                $step = 1; // back to first step
            } else {
                $message = "⚠️ Error updating password. Try again.";
            }
            $stmt->close();
        } else {
            $message = "⚠️ Session expired. Please try again.";
            $step = 1;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
  <h2 class="text-2xl font-bold text-center mb-6">Forgot Password</h2>

  <?php if ($message): ?>
    <p class="mb-4 text-center text-red-500"><?= $message ?></p>
  <?php endif; ?>

  <?php if ($step == 1): ?>
  <!-- Step 1: Verify details -->
  <form method="POST">
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">User ID</label>
      <input type="text" name="user_id" required class="w-full border px-3 py-2 rounded-lg">
    </div>
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Username</label>
      <input type="text" name="username" required class="w-full border px-3 py-2 rounded-lg">
    </div>
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Email</label>
      <input type="email" name="email" required class="w-full border px-3 py-2 rounded-lg">
    </div>
    <button type="submit" name="verify" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
      Verify
    </button>
  </form>
  <?php endif; ?>

  <?php if ($step == 2): ?>
  <!-- Step 2: Reset password -->
  <form method="POST">
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">New Password</label>
      <input type="password" name="new_password" required class="w-full border px-3 py-2 rounded-lg">
    </div>
    <button type="submit" name="reset_password" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">
      Update Password
    </button>
  </form>
  <?php endif; ?>

  <p class="mt-4 text-center text-sm">
    <a href="login.php" class="text-blue-600 hover:underline">Back to Login</a>
  </p>
</div>

</body>
</html>
