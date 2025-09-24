<?php
session_start();
require_once 'db_connect.php';

// Require Stripe PHP
require 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_YOUR_SECRET_KEY'); // Replace with your Stripe secret key

// Ensure only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

// Handle payment form submission (amount to pay)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $amount_cents = $amount * 100; // Stripe accepts amounts in cents

    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'lkr', // Sri Lankan Rupees
                'product_data' => [
                    'name' => 'Smart Commerce Core Fee Payment',
                ],
                'unit_amount' => $amount_cents,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'payment_success.php?amount=' . $amount,
        'cancel_url' => 'fees_payment.php',
        'metadata' => [
            'student_id' => $user_id
        ]
    ]);

    header("Location: " . $session->url);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Make Payment | Smart Commerce Core</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style> body { font-family: 'Public Sans', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen">
<header class="bg-white shadow px-6 py-4 flex justify-between items-center">
  <div class="flex items-center gap-2">
    <span class="material-icons text-pink-600 text-3xl">payments</span>
    <span class="text-xl font-bold text-blue-700">Smart Commerce Core - Make Payment</span>
  </div>
  <div class="text-right">
    <div class="text-gray-700 font-semibold"><?= htmlspecialchars($username) ?> (<?= htmlspecialchars($email) ?>)</div>
    <div class="text-sm text-gray-500">User ID: <span class="font-bold text-blue-600"><?= htmlspecialchars($user_id) ?></span></div>
    <a href="student_dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 mt-2 inline-block">Dashboard</a>
    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-2 inline-block">Logout</a>
  </div>
</header>

<main class="max-w-3xl mx-auto py-10">
  <h1 class="text-3xl font-bold mb-8 text-center">Make a Payment</h1>

  <form method="POST" class="bg-white shadow p-6 rounded-lg">
    <div class="mb-4">
      <label class="block mb-2 font-semibold">Amount (LKR)</label>
      <input type="number" name="amount" min="1" step="0.01" required class="w-full border p-3 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter amount to pay">
    </div>
    <div class="text-center">
      <button type="submit" class="bg-pink-600 text-white px-6 py-3 rounded-lg hover:bg-pink-700 font-semibold">
        <span class="material-icons align-middle mr-1">credit_card</span> Pay Now
      </button>
    </div>
  </form>
</main>
</body>
</html>
