 <?php
session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$child_id = $_SESSION['child_id'];
$input = json_decode(file_get_contents("php://input"), true);
$paymentIntentId = $input['paymentIntentId'] ?? '';
$amount = isset($input['amount']) ? intval($input['amount']) * 100 : 0;

if (!$paymentIntentId || $amount <= 0) {
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

// Update record to Paid
$stmt = $conn->prepare("UPDATE payments 
                        SET status = 'Paid', paid_at = NOW() 
                        WHERE student_id = ? AND amount = ? AND status = 'Pending' 
                        ORDER BY payment_id DESC LIMIT 1");
$stmt->bind_param("ii", $child_id, $amount);
$stmt->execute();

echo json_encode(['success' => true]);
