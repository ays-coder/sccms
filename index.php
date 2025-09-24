 <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Smart Commerce Core - Landing Page</title>
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
      --accent-color: #0a6cce;
    }

    body {
      font-family: "Public Sans", "Noto Sans", sans-serif;
    }

    .btn-primary {
      @apply flex items-center justify-center rounded-lg h-10 px-4 bg-[var(--primary-color)] text-white text-sm font-bold hover:bg-[var(--accent-color)] transition duration-200;
    }

    .btn-secondary {
      @apply flex items-center justify-center rounded-lg h-10 px-4 bg-[var(--secondary-color)] text-[var(--text-primary)] text-sm font-bold hover:bg-gray-200 transition duration-200;
    }

    .nav-link {
      @apply text-[var(--text-primary)] text-sm font-medium hover:text-[var(--primary-color)] transition duration-200;
    }

    .role-card {
      @apply flex flex-col gap-4 border border-gray-300 bg-white p-6 rounded-xl shadow-sm hover:shadow-lg hover:border-[var(--primary-color)] transition duration-300;
    }

    .footer-link {
      @apply text-[var(--text-secondary)] hover:text-[var(--primary-color)] text-sm transition;
    }
  </style>
</head>
<body class="bg-slate-50">
  <div class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white border-b shadow-sm px-6 py-4 md:px-10 sticky top-0 z-50">
      <div class="flex justify-between items-center">
        <div class="flex items-center gap-3 text-[var(--text-primary)]">
          <div class="size-6 text-[var(--primary-color)]">
            <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
              <path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" fill="currentColor"></path>
            </svg>
          </div>
          <h2 class="text-xl font-bold">Smart Commerce Core</h2>
        </div>

        <nav class="hidden md:flex items-center gap-6">
          <a href="#" class="nav-link">About</a>
          <a href="courses.php" class="nav-link">Courses</a>
          <a href="contact.php" class="nav-link">Contact</a>
        </nav>

        <div class="flex items-center gap-3">
          <a href="login.php" class="btn-primary">Login</a>
          <a href="register.php?role=student" class="btn-secondary">Register</a>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1">
      <section class="relative text-white text-center py-20 px-4 bg-gradient-to-r from-blue-600 via-blue-500 to-blue-700">
        <h1 class="text-5xl font-extrabold mb-4">
          Welcome to <span class="text-yellow-300">Smart Commerce Core</span>
        </h1>
        <p class="max-w-2xl mx-auto text-lg font-light mb-6">
          Your comprehensive learning management system for commerce education.
        </p>
        <a href="register.php" class="btn-primary text-lg px-6 py-3">Get Started Now</a>
      </section>

      <!-- Roles Section -->
      <section class="bg-white py-16">
        <div class="max-w-screen-xl mx-auto px-4 md:px-8">
          <h2 class="text-3xl font-bold text-center mb-12">Empowering Every Role in Education</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <div class="role-card text-center">
              <svg fill="currentColor" class="mx-auto" width="32" height="32" viewBox="0 0 256 256"><path d="..."/></svg>
              <h3 class="text-xl font-bold">Admin</h3>
              <p>Manage courses, users, and system settings.</p>
            </div>

            <div class="role-card text-center">
              <svg fill="currentColor" class="mx-auto" width="32" height="32" viewBox="0 0 256 256"><path d="..."/></svg>
              <h3 class="text-xl font-bold">Tutor</h3>
              <p>Create courses and track student progress.</p>
            </div>

            <div class="role-card text-center">
              <svg fill="currentColor" class="mx-auto" width="32" height="32" viewBox="0 0 256 256"><path d="..."/></svg>
              <h3 class="text-xl font-bold">Student</h3>
              <p>Access learning materials and assignments.</p>
            </div>

            <div class="role-card text-center">
              <svg fill="currentColor" class="mx-auto" width="32" height="32" viewBox="0 0 256 256"><path d="..."/></svg>
              <h3 class="text-xl font-bold">Parent</h3>
              <p>Monitor student progress and communicate.</p>
            </div>

          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-800 text-white py-12">
      <div class="max-w-screen-xl mx-auto px-4 md:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
          <div>
            <h3 class="font-bold mb-2">Smart Commerce Core</h3>
            <p class="text-sm">Empowering the next generation of commerce professionals.</p>
          </div>
          <div>
            <h3 class="font-bold mb-2">Quick Links</h3>
            <ul class="space-y-1">
              <li><a href="about.php" class="footer-link">About</a></li>
              <li><a href="courses.php" class="footer-link">Courses</a></li>
              <li><a href="login.php" class="footer-link">Login</a></li>
              <li><a href="register.php" class="footer-link">Register</a></li>
            </ul>
          </div>
          <div>
            <h3 class="font-bold mb-2">Connect</h3>
            <ul class="space-y-1">
              <li><a href="contact.php" class="footer-link">Contact</a></li>
              <li><a href="privacy.php" class="footer-link" target="_blank">Privacy Policy</a></li>
              <li><a href="terms.php" class="footer-link" target="_blank">Terms of Service</a></li>
            </ul>
          </div>
        </div>
        <p class="text-center text-xs text-slate-400 mt-8">© 2024 Smart Commerce Core. All rights reserved.</p>
      </div>
    </footer>
  </div>
</body>
</html>
