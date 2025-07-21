<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Smart Commerce Core</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Public Sans', sans-serif; }
    :root {
      --primary-color: #0c7ff2;
      --text-primary: #111418;
      --text-secondary: #60758a;
    }
  </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
  <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-bold text-[var(--text-primary)] text-center">Create an Account</h2>
    <p class="text-sm text-[var(--text-secondary)] text-center mb-6">Join Smart Commerce Core today</p>

    <form action="register_action.php" method="POST" class="space-y-4">
      <input type="text" name="name" placeholder="Full Name" required
        class="w-full border-gray-300 rounded-lg py-3 px-4 bg-gray-50 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)]" />

      <input type="email" name="email" placeholder="Email" required
        class="w-full border-gray-300 rounded-lg py-3 px-4 bg-gray-50 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)]" />

      <input type="password" name="password" placeholder="Password" required
        class="w-full border-gray-300 rounded-lg py-3 px-4 bg-gray-50 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)]" />

      <select name="role" required
        class="w-full border-gray-300 rounded-lg py-3 px-4 bg-gray-50 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)]">
        <option value="">Select Role</option>
        <option value="admin">Admin</option>
        <option value="tutor">Tutor</option>
        <option value="student">Student</option>
        <option value="parent">Parent</option>
      </select>

      <button type="submit"
        class="w-full bg-[var(--primary-color)] text-white py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
        Register
      </button>
    </form>

    <p class="text-center text-sm text-[var(--text-secondary)] mt-6">
      Already have an account?
      <a href="login.php" class="text-[var(--primary-color)] font-semibold hover:underline">Login</a>
    </p>
  </div>
</body>
</html>
