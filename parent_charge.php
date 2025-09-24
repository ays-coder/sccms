 <?php
session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php'; // Stripe SDK

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$child_id = $_SESSION['child_id'];

\Stripe\Stripe::setApiKey("sk_test_YOUR_SECRET_KEY"); // ✅ Replace with your secret key

$input = json_decode(file_get_contents("php://input"), true);
$amount = isset($input['amount']) ? intval($input['amount']) * 100 : 0;

if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid amount']);
    exit();
}

try {
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'usd',
        'payment_method_types' => ['card'],
    ]);

    // Insert a Pending record
    $stmt = $conn->prepare("INSERT INTO payments (student_id, amount, status, paid_at) 
                            VALUES (?, ?, 'Pending', NOW())");
    $stmt->bind_param("ii", $child_id, $amount);
    $stmt->execute();

    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
