 <?php
session_start();
require_once 'db_connect.php';

// Ensure parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

// Ensure child is verified
if (!isset($_SESSION['child_id']) || !isset($_SESSION['child_email'])) {
    header("Location: verify_child.php");
    exit();
}

$child_id = $_SESSION['child_id'];
$child_email = $_SESSION['child_email'];

// Check if already paid
$query = $conn->prepare("SELECT * FROM payments WHERE student_id = ? AND status = 'Paid'");
$query->bind_param("i", $child_id);
$query->execute();
$result = $query->get_result();
$payment = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Payment | Smart Commerce Core</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h1 class="text-2xl font-bold mb-4 text-center">
        Pay Fees for <?= htmlspecialchars($child_email) ?>
    </h1>

    <?php if ($payment): ?>
        <p class="text-green-600 text-center font-semibold mb-4">
            Fees already paid!
        </p>
    <?php else: ?>
        <form id="payment-form" class="space-y-4">
            <label class="block font-medium">Amount (USD)</label>
            <input type="number" id="amount" min="1" value="100"
                   class="w-full p-2 border rounded" required>

            <label class="block font-medium">Card Details</label>
            <div id="card-element" class="p-2 border rounded"></div>
            <div id="card-errors" class="text-red-500"></div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
                Pay Now
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
const stripe = Stripe("pk_test_YOUR_PUBLISHABLE_KEY"); // ✅ Replace with your publishable key
const elements = stripe.elements();
const card = elements.create("card");
card.mount("#card-element");

const form = document.getElementById("payment-form");
form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const amount = document.getElementById("amount").value;

    // Call backend to create a PaymentIntent
    const res = await fetch("parent_charge.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ amount: amount })
    });
    const data = await res.json();

    if (data.error) {
        document.getElementById("card-errors").textContent = data.error;
        return;
    }

    // Confirm the payment on the client
    const result = await stripe.confirmCardPayment(data.clientSecret, {
        payment_method: { card: card }
    });

    if (result.error) {
        document.getElementById("card-errors").textContent = result.error.message;
    } else {
        if (result.paymentIntent.status === "succeeded") {
            // Update DB to mark as Paid
            await fetch("update_payment.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    paymentIntentId: result.paymentIntent.id,
                    amount: amount
                })
            });

            alert("Payment successful!");
            window.location.reload();
        }
    }
});
</script>
</body>
</html>
