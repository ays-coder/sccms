 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Forgot Password - Smart Commerce Core</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Public Sans', sans-serif; }
    :root {
      --primary-color: #0c7ff2;
      --text-primary: #111418;
      --text-secondary: #60758a;
      --background-color: #eacfcfff;
    }
    .btn-primary {
      @apply bg-[var(--primary-color)] text-white hover:bg-opacity-90;
    }
  </style>
</head>
<body class="bg-[var(--background-color)] text-[var(--text-primary)] flex items-center justify-center min-h-screen">
  <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-bold text-center mb-2">Forgot Your Password?</h2>
    <p class="text-sm text-[var(--text-secondary)] text-center mb-6">
      No problem! Enter your email address below and we'll send a reset link.
    </p>

    <form id="resetForm" method="POST" action="#">
      <input type="email" name="email" placeholder="your.email@example.com" required class="form-input w-full mb-4" />
      <button type="submit" class="btn-primary w-full py-3 rounded-md font-semibold">Reset Password</button>
    </form>

    <div id="confirmation-message" class="hidden mt-4 text-center text-sm text-green-600 font-medium p-3 bg-green-50 rounded-md border border-green-200">
      If an account with that email exists, a password reset link has been sent.
    </div>

    <p class="mt-6 text-center text-sm text-[var(--text-secondary)]">
      Remember your password?
      <a href="login.php" class="font-medium text-[var(--primary-color)] hover:underline">Login</a>
    </p>
  </div>

  <script>
    document.getElementById('resetForm').addEventListener('submit', function(e) {
      e.preventDefault();
      document.getElementById('confirmation-message').classList.remove('hidden');
    });
  </script>
</body>
</html>

