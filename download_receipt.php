<?php
session_start();
require_once 'db_connect.php';
require('fpdf/fpdf.php');

// Check for valid session and permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['parent', 'admin'])) {
    header("Location: login.php");
    exit();
}

// Validate receipt parameter
if (!isset($_GET['receipt'])) {
    $_SESSION['error'] = "Receipt number is required";
    header("Location: parent_payments.php");
    exit();
}

$receipt_number = filter_var($_GET['receipt'], FILTER_SANITIZE_STRING);

// Get payment and registration details with enhanced joins
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        cr.*, 
        c.course_name, 
        c.fee,
        u.username as student_name,
        u.username as student_full_name,
        u.email as student_email
    FROM payments p
    JOIN course_registrations cr ON p.registration_id = cr.registration_id
    JOIN courses c ON cr.course_id = c.course_id
    JOIN users u ON cr.student_id = u.user_id
    WHERE p.receipt_number = ?
    AND (? = 'admin' OR u.user_id = ?)
");

$stmt->bind_param("ssi", $receipt_number, $_SESSION['role'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Receipt not found or access denied";
    header("Location: parent_payments.php");
    exit();
}

$payment = $result->fetch_assoc();

// Enhanced PDF class with more features
class ReceiptPDF extends FPDF {
    private $payment;
    
    function __construct($payment) {
        parent::__construct();
        $this->payment = $payment;
    }
    
    function Header() {
        // School Logo or Name
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 10, 'SCCMS', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Payment Receipt', 0, 1, 'C');
        $this->Ln(5);
        
        // Receipt metadata
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, 'This is an official receipt from SCCMS', 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function generateReceipt() {
        $this->AddPage();
        
        // Receipt Information
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Receipt Number: ' . $this->payment['receipt_number'], 0, 1);
        $this->Cell(0, 8, 'Date: ' . date('F d, Y', strtotime($this->payment['payment_date'])), 0, 1);
        $this->Ln(5);
        
        // Student Information
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Student Information', 0, 1, 'L', true);
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, 'Student Username: ' . $this->payment['student_name'], 0, 1);
        $this->Cell(0, 8, 'Email: ' . $this->payment['student_email'], 0, 1);
        $this->Ln(5);
        
        // Course Information
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Course Information', 0, 1, 'L', true);
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, 'Course: ' . $this->payment['course_name'], 0, 1);
        $this->Cell(0, 8, 'Total Fee: $' . number_format($this->payment['fee'], 2), 0, 1);
        $this->Ln(5);
        
        // Payment Details
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Payment Details', 0, 1, 'L', true);
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, 'Amount Paid: $' . number_format($this->payment['amount'], 2), 0, 1);
        $this->Cell(0, 8, 'Payment Method: ' . ucfirst($this->payment['payment_method']), 0, 1);
        $this->Cell(0, 8, 'Status: ' . ucfirst($this->payment['payment_status']), 0, 1);
        $this->Cell(0, 8, 'Transaction ID: ' . ($this->payment['transaction_id'] ?? 'N/A'), 0, 1);
        
        // Balance Information
        if ($this->payment['fee'] > $this->payment['amount']) {
            $balance = $this->payment['fee'] - $this->payment['amount'];
            $this->Ln(5);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, 'Remaining Balance: $' . number_format($balance, 2), 0, 1);
        }
        
        // Thank You Message
        $this->Ln(15);
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, 'Thank you for your payment!', 0, 1, 'C');
        $this->Cell(0, 5, 'For any queries, please contact the administration office.', 0, 1, 'C');
    }
}

try {
    // Create and output PDF
    $pdf = new ReceiptPDF($payment);
    $pdf->generateReceipt();
    $pdf->Output('D', 'Receipt_' . $receipt_number . '.pdf');
} catch (Exception $e) {
    $_SESSION['error'] = "Error generating receipt: " . $e->getMessage();
    header("Location: parent_payments.php");
    exit();
}
?>
