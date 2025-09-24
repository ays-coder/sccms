<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Smart Commerce Core</title>

  <!-- Google Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <style type="text/tailwindcss">
    :root {
      --primary-color: #0c7ff2;
      --secondary-color: #f0f2f5;
      --text-primary: #111418;
      --text-secondary: #60758a;
    }
    body {
      font-family: 'Public Sans', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

  <!-- Header -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto flex items-center justify-between px-6 py-4">
      <div class="flex items-center gap-2 text-[var(--text-primary)]">
        <div class="text-[var(--primary-color)] w-8 h-8">
          <svg fill="currentColor" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z"></path>
          </svg>
        </div>
        <h1 class="text-xl font-semibold">Smart Commerce Core</h1>
      </div>

      <nav class="hidden md:flex gap-6 text-sm">
        <a href="index.php" class="text-[var(--text-secondary)] hover:text-[var(--primary-color)]">Home</a>
        <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--primary-color)]">About</a>
        <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--primary-color)]">Courses</a>
        <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--primary-color)]">Contact</a>
      </nav>

      <a href="register.php" class="hidden md:inline-block bg-[var(--primary-color)] text-white text-sm font-semibold px-4 py-2 rounded-md hover:bg-opacity-90 transition-all">
        Register
      </a>

      <button class="md:hidden text-[var(--text-primary)]" aria-label="Open menu">
        <span class="material-icons">menu</span>
      </button>
    </div>
  </header>

  <!-- Main -->
  <main class="flex flex-1 items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-6">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">Welcome Back!</h2>
        <p class="mt-2 text-sm text-[var(--text-secondary)]">Login to your Smart Commerce Core dashboard</p>
      </div>

      <form class="bg-white p-8 shadow-xl rounded-xl space-y-6" method="POST" action="login_action.php">
        <div>
          <label for="email" class="sr-only">Username or Email</label>
          <div class="relative">
            <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">person_outline</span>
            <input type="text" name="email" id="email" required
              placeholder="Username or Email"
              class="pl-10 pr-3 py-3.5 w-full border border-gray-300 rounded-lg bg-gray-50 text-sm placeholder-gray-400 text-[var(--text-primary)] focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] transition" />
          </div>
        </div>

        <div>
          <label for="password" class="sr-only">Password</label>
          <div class="relative">
            <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock_outline</span>
            <input type="password" name="password" id="password" required
              placeholder="Password"
              class="pl-10 pr-3 py-3.5 w-full border border-gray-300 rounded-lg bg-gray-50 text-sm placeholder-gray-400 text-[var(--text-primary)] focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] transition" />
          </div>
        </div>

        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center text-[var(--text-secondary)]">
            <input type="checkbox" name="remember" class="h-4 w-4 text-[var(--primary-color)] border-gray-300 rounded focus:ring-[var(--primary-color)]">
            <span class="ml-2">Remember me</span>
          </label>
          <a href="forgot_password.php" class="text-[var(--primary-color)] hover:text-opacity-80">Forgot password?</a>
        </div>

        <button type="submit" class="flex justify-center items-center w-full bg-[var(--primary-color)] text-white py-3 rounded-md font-semibold hover:bg-opacity-90 transition-all">
          <span class="material-icons mr-2">login</span> Login
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-[var(--text-secondary)]">
        Don't have an account?
        <a href="register.php" class="text-[var(--primary-color)] font-semibold hover:text-opacity-80">Register here</a>
      </p>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-white border-t border-gray-200">
    <div class="container mx-auto px-6 py-6 text-center text-sm text-[var(--text-secondary)]">
      <nav class="flex justify-center flex-wrap gap-6 mb-4">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="contact.php">Contact Us</a>
      </nav>
      <p>© 2024 Smart Commerce Core. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>
