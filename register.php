 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Register - Smart Commerce Core</title>
  <link rel="icon" href="data:image/x-icon;base64," type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Public+Sans:wght@400;500;600;700;900" onload="this.rel='stylesheet'" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <style type="text/tailwindcss">
    :root {
      --primary-color: #0c7ff2;
      --secondary-color: #f0f2f5;
      --text-primary: #111418;
      --text-secondary: #60758a;
      --text-accent: #0c7ff2;
    }

    body {
      font-family: "Public Sans", "Noto Sans", sans-serif;
    }

    .form-input {
      @apply w-full rounded-md py-3 px-4 bg-[var(--secondary-color)] text-[var(--text-primary)] shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] sm:text-sm;
    }

    .btn-primary {
      @apply w-full justify-center rounded-md bg-[var(--primary-color)] px-3 py-3 text-sm font-semibold text-white hover:bg-opacity-90 focus:outline focus:ring-2 focus:ring-[var(--primary-color)];
    }

    .header-link {
      @apply text-[var(--text-primary)] text-sm font-medium hover:text-[var(--primary-color)];
    }
  </style>
</head>
<body class="bg-slate-50">
  <div class="min-h-screen flex flex-col">
    <header class="bg-white border-b px-6 py-4 flex justify-between items-center">
      <div class="flex items-center gap-3 text-[var(--text-primary)]">
        <div class="size-6 text-[var(--primary-color)]">
          <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="..." fill="currentColor"></path></svg>
        </div>
        <h1 class="text-xl font-bold">Smart Commerce Core</h1>
      </div>
      <nav class="hidden sm:flex gap-6 items-center">
        <a href="index.php" class="header-link">Home</a>
        <a href="#" class="header-link">Courses</a>
        <a href="#" class="header-link">Contact</a>
        <a href="login.php" class="btn-secondary">Login</a>
      </nav>
    </header>

    <main class="flex flex-1 justify-center items-center px-4 py-12">
      <div class="w-full max-w-md bg-white p-6 sm:p-8 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold text-center mb-1 text-[var(--text-primary)]">Create Your Account</h2>
        <p class="text-sm text-center mb-6 text-[var(--text-secondary)]">Join Smart Commerce Core and start your learning journey.</p>
       <form method="POST" action="register_action.php" class="space-y-4">
  <input type="text" name="username" placeholder="Full Name" class="form-input" required />
  <input type="email" name="email" placeholder="Email Address" class="form-input" required />
   
<?php $selected_role = isset($_GET['role']) ? $_GET['role'] : ''; ?>
 
<select name="role" class="form-input" required>
  <option value="" disabled <?= $selected_role ? '' : 'selected' ?>>Select your role</option>
  <option value="tutor" <?= $selected_role == 'tutor' ? 'selected' : '' ?>>Tutor</option>
  <option value="student" <?= $selected_role == 'student' ? 'selected' : '' ?>>Student</option>
  <option value="parent" <?= $selected_role == 'parent' ? 'selected' : '' ?>>Parent</option>
</select>
 
  <input type="password" name="password" placeholder="Password" class="form-input" required />
  <input type="password" name="confirm_password" placeholder="Confirm Password" class="form-input" required />
  <button type="submit" class="btn-primary">Register</button>
</form>

        <p class="text-sm text-center mt-4 text-[var(--text-secondary)]">
          Already have an account?
          <a href="login.php" class="text-[var(--text-accent)] hover:text-opacity-80 font-medium">Login</a>
        </p>
      </div>
    </main>

    <footer class="text-center text-sm text-[var(--text-secondary)] py-6 border-t bg-white">
      <p>© 2024 Smart Commerce Core. All rights reserved.</p>
    </footer>
  </div>
</body>
</html>
