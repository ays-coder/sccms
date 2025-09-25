<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';
$course = null;
$registration = null;

// Get course details
if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $parent_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
        $error_message = "Please select a student.";
    } elseif (!isset($_POST['payment_method']) || empty($_POST['payment_method'])) {
        $error_message = "Please select a payment method.";
    } elseif (!isset($_POST['course_id']) || empty($_POST['course_id'])) {
        $error_message = "Invalid course selection.";
    } elseif ($_POST['payment_method'] === 'credit_card') {
        // Validate credit card details
        if (empty($_POST['card_number']) || !preg_match('/^[0-9]{16}$/', $_POST['card_number'])) {
            $error_message = "Please enter a valid 16-digit card number.";
        } elseif (empty($_POST['expiry_date']) || !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $_POST['expiry_date'])) {
            $error_message = "Please enter a valid expiry date (MM/YY).";
        } elseif (empty($_POST['cvv']) || !preg_match('/^[0-9]{3,4}$/', $_POST['cvv'])) {
            $error_message = "Please enter a valid CVV.";
        } elseif (empty($_POST['card_holder'])) {
            $error_message = "Please enter the card holder's name.";
        }
    } elseif ($_POST['payment_method'] === 'bank_transfer') {
        // Validate bank transfer receipt
        if (!isset($_FILES['transfer_receipt']) || $_FILES['transfer_receipt']['error'] !== UPLOAD_ERR_OK) {
            $error_message = "Please upload the transfer receipt.";
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
            $file_type = $_FILES['transfer_receipt']['type'];
            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Invalid file type. Please upload PDF, JPG, or PNG files only.";
            }
        }
    } elseif ($_POST['payment_method'] === 'credit_card') {
        // Validate credit card details
        if (empty($_POST['card_number']) || !preg_match('/^[0-9]{16}$/', $_POST['card_number'])) {
            $error_message = "Invalid card number.";
        } elseif (empty($_POST['expiry_date']) || !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $_POST['expiry_date'])) {
            $error_message = "Invalid expiry date.";
        } elseif (empty($_POST['cvv']) || !preg_match('/^[0-9]{3,4}$/', $_POST['cvv'])) {
            $error_message = "Invalid CVV.";
        } elseif (empty($_POST['card_holder'])) {
            $error_message = "Card holder name is required.";
        }
    } elseif ($_POST['payment_method'] === 'bank_transfer') {
        // Validate bank transfer receipt
        if (!isset($_FILES['transfer_receipt']) || $_FILES['transfer_receipt']['error'] !== UPLOAD_ERR_OK) {
            $error_message = "Please upload the transfer receipt.";
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
            $file_type = $_FILES['transfer_receipt']['type'];
            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Invalid file type. Please upload PDF, JPG, or PNG files only.";
            }
        }ce 'db_connect.php';

// Check if user is logged in as parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';
$course = null;
$registration = null;

// Get course details
if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $parent_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
        $error_message = "Please select a student.";
    } elseif (!isset($_POST['payment_method']) || empty($_POST['payment_method'])) {
        $error_message = "Please select a payment method.";
    } elseif (!isset($_POST['course_id']) || empty($_POST['course_id'])) {
        $error_message = "Invalid course selection.";
    } else {
        $student_id = intval($_POST['student_id']);
        $course_id = intval($_POST['course_id']);
        $payment_method = $_POST['payment_method'];
        
        // Verify student belongs to parent
        $verify_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND parent_id = ? AND role = 'student'");
        $verify_stmt->bind_param("ii", $student_id, $parent_id);
        $verify_stmt->execute();
        $student_result = $verify_stmt->get_result();
        
        if ($student_result->num_rows === 0) {
            $error_message = "Invalid student selection.";
        } else {
            // Check if student is already registered for this course
            $check_stmt = $conn->prepare("
                SELECT registration_id 
                FROM course_registrations 
                WHERE course_id = ? 
                AND student_id = ? 
                AND (status = 'completed' OR status = 'pending')
            ");
            $check_stmt->bind_param("ii", $course_id, $student_id);
            $check_stmt->execute();
            $existing_reg = $check_stmt->get_result();
            
            if ($existing_reg->num_rows > 0) {
                $error_message = "Student is already registered for this course.";
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Debug information
                    error_log("Starting payment process for Course ID: $course_id, Student ID: $student_id, Parent ID: $parent_id");
                    error_log("Payment Method: $payment_method, Course Fee: {$course['fee']}");

                    // Verify course exists and is active
                    $course_check = $conn->prepare("SELECT course_id, course_name, fee FROM courses WHERE course_id = ? AND status = 'active'");
                    $course_check->bind_param("i", $course_id);
                    $course_check->execute();
                    $course_result = $course_check->get_result();
                    
                    if ($course_result->num_rows === 0) {
                        throw new Exception("Course not found or not active");
                    }
                    
                    // Create course registration with proper checks
                    $reg_stmt = $conn->prepare("
                        INSERT INTO course_registrations 
                        (course_id, student_id, parent_id, status, registration_date) 
                        SELECT ?, ?, ?, 'pending', NOW()
                        FROM users 
                        WHERE user_id = ? AND role = 'parent'
                        LIMIT 1
                    ");
                    
                    if (!$reg_stmt) {
                        throw new Exception("Registration preparation failed: " . $conn->error);
                    }
                    
                    $reg_stmt->bind_param("iiii", $course_id, $student_id, $parent_id, $parent_id);
                    if (!$reg_stmt->execute()) {
                        throw new Exception("Registration failed: " . $reg_stmt->error);
                    }
                    $registration_id = $conn->insert_id;
                    error_log("Registration created with ID: $registration_id");
                    
                    // Generate unique receipt number
                    $receipt_number = 'RCP' . date('Ymd') . str_pad($registration_id, 4, '0', STR_PAD_LEFT);
                    error_log("Generated receipt number: $receipt_number");
                    
                    // Create payment record with proper error handling
                    $payment_stmt = $conn->prepare("
                        INSERT INTO payments 
                        (registration_id, amount, payment_method, receipt_number, payment_status, payment_date) 
                        VALUES (?, ?, ?, ?, 'completed', NOW())
                    ");
                    
                    if (!$payment_stmt) {
                        throw new Exception("Payment preparation failed: " . $conn->error);
                    }
                    
                    $payment_stmt->bind_param("idss", $registration_id, $course['fee'], $payment_method, $receipt_number);
                    if (!$payment_stmt->execute()) {
                        throw new Exception("Payment creation failed: " . $payment_stmt->error);
                    }
                    error_log("Payment record created successfully");

                    // Update course registration status to completed
                    $update_reg_stmt = $conn->prepare("
                        UPDATE course_registrations 
                        SET status = 'completed'
                        WHERE registration_id = ?
                    ");
                    
                    if (!$update_reg_stmt) {
                        throw new Exception("Registration update preparation failed: " . $conn->error);
                    }
                    
                    $update_reg_stmt->bind_param("i", $registration_id);
                    if (!$update_reg_stmt->execute()) {
                        throw new Exception("Registration update failed: " . $update_reg_stmt->error);
                    }
                    error_log("Course registration updated to completed status");
                    
                    // Update registration status
                    $update_stmt = $conn->prepare("
                        UPDATE course_registrations 
                        SET status = 'completed' 
                        WHERE registration_id = ?
                    ");
                    
                    if (!$update_stmt) {
                        throw new Exception("Status update preparation failed: " . $conn->error);
                    }
                    
                    $update_stmt->bind_param("i", $registration_id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Status update failed: " . $update_stmt->error);
                    }
                    error_log("Registration status updated to completed");
                    
                    // Commit transaction
                    $conn->commit();
                    error_log("Transaction committed successfully");
                    
                    // Store success message in session and redirect
                    $_SESSION['success_message'] = "Payment successful! Your receipt number is " . $receipt_number;
                    header("Location: download_receipt.php?receipt=" . $receipt_number);
                    exit();
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    error_log("Payment processing error: " . $e->getMessage());
                    $error_message = "Payment processing failed: " . $e->getMessage();
                    
                    // Log detailed error information
                    error_log("Full error details: " . print_r([
                        'course_id' => $course_id,
                        'student_id' => $student_id,
                        'parent_id' => $parent_id,
                        'payment_method' => $payment_method,
                        'course_fee' => $course['fee'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], true));
                }
            }
        }
    }
}

// Get parent's children (students)
$parent_id = $_SESSION['user_id'];
$children_stmt = $conn->prepare("SELECT * FROM users WHERE parent_id = ? AND role = 'student'");
$children_stmt->bind_param("i", $parent_id);
$children_stmt->execute();
$children = $children_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Payment | Parent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">Course Payment</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="parent_payments.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-600 uppercase tracking-widest hover:bg-gray-200 active:bg-gray-300 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <span class="material-icons-round text-sm mr-2">arrow_back</span>
                        Back to Courses
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <?php if ($course): ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- Course Details Section -->
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-900">Payment Details</h2>
                    <p class="mt-1 text-sm text-gray-600">Complete payment to register for the course</p>
                </div>

                <!-- Course Summary -->
                <div class="p-6 bg-gray-50 border-b border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($course['course_name']) ?></h3>
                            <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($course['description']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Course Fee</p>
                            <p class="text-2xl font-bold text-blue-600">$<?= number_format($course['fee'], 2) ?></p>
                        </div>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="p-4 bg-red-50 border-b border-gray-100">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="material-icons-round text-red-400">error</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800"><?= $error_message ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Payment Form -->
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                        
                        <!-- Student Selection -->
                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700">Select Student</label>
                            <?php if ($children->num_rows > 0): ?>
                                <select id="student_id" 
                                        name="student_id" 
                                        required 
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                                    <option value="">-- Select Student --</option>
                                    <?php while ($child = $children->fetch_assoc()): ?>
                                        <option value="<?= $child['user_id'] ?>"><?= htmlspecialchars($child['username']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            <?php else: ?>
                                <div class="mt-2 rounded-md bg-yellow-50 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <span class="material-icons-round text-yellow-400">warning</span>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-yellow-800">No Students Linked</h3>
                                            <div class="mt-2 text-sm text-yellow-700">
                                                <p>You need to verify and link your child's account before making a payment. Please follow these steps:</p>
                                                <ol class="list-decimal ml-4 mt-2 space-y-1">
                                                    <?php $_SESSION['pending_course_payment'] = $course['course_id']; ?>
                                                    <li>Go to the <a href="verify_child.php" class="font-medium underline">Verify Child</a> page</li>
                                                    <li>Enter your child's verification code (format: STU0001)</li>
                                                    <li>You'll be automatically redirected back to complete the payment</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="relative">
                                    <input type="radio" 
                                           id="credit_card"
                                           name="payment_method" 
                                           value="credit_card" 
                                           class="hidden peer" 
                                           required
                                           onchange="showPaymentForm(this.value)">
                                    <label for="credit_card" 
                                           class="block w-full cursor-pointer rounded-lg border bg-white p-4 shadow-sm hover:border-blue-500 peer-checked:border-blue-500 peer-checked:ring-1 peer-checked:ring-blue-500">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="block text-sm font-medium text-gray-900">Credit Card</span>
                                                <span class="mt-1 flex items-center text-sm text-gray-500">Pay with credit card</span>
                                            </div>
                                            <div class="text-blue-600 opacity-0 peer-checked:opacity-100">
                                                <span class="material-icons-round">check_circle</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="relative">
                                    <input type="radio" 
                                           id="bank_transfer"
                                           name="payment_method" 
                                           value="bank_transfer" 
                                           class="hidden peer" 
                                           required
                                           onchange="showPaymentForm(this.value)">
                                    <label for="bank_transfer" 
                                           class="block w-full cursor-pointer rounded-lg border bg-white p-4 shadow-sm hover:border-blue-500 peer-checked:border-blue-500 peer-checked:ring-1 peer-checked:ring-blue-500">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="block text-sm font-medium text-gray-900">Bank Transfer</span>
                                                <span class="mt-1 flex items-center text-sm text-gray-500">Direct bank transfer</span>
                                            </div>
                                            <div class="text-blue-600 opacity-0 peer-checked:opacity-100">
                                                <span class="material-icons-round">check_circle</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Credit Card Form -->
                        <div id="credit_card_form" class="hidden space-y-4 mt-6 p-4 border rounded-lg bg-gray-50">
                            <h4 class="font-semibold text-gray-900">Credit Card Details</h4>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="card_number" class="block text-sm font-medium text-gray-700">Card Number</label>
                                    <input type="text" 
                                           id="card_number" 
                                           name="card_number" 
                                           placeholder="1234 5678 9012 3456"
                                           pattern="[0-9]{16}"
                                           maxlength="16"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                        <input type="text" 
                                               id="expiry_date" 
                                               name="expiry_date" 
                                               placeholder="MM/YY"
                                               pattern="(0[1-9]|1[0-2])\/([0-9]{2})"
                                               maxlength="5"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="cvv" class="block text-sm font-medium text-gray-700">CVV</label>
                                        <input type="text" 
                                               id="cvv" 
                                               name="cvv" 
                                               placeholder="123"
                                               pattern="[0-9]{3,4}"
                                               maxlength="4"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>
                                <div>
                                    <label for="card_holder" class="block text-sm font-medium text-gray-700">Card Holder Name</label>
                                    <input type="text" 
                                           id="card_holder" 
                                           name="card_holder" 
                                           placeholder="John Doe"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Bank Transfer Form -->
                        <div id="bank_transfer_form" class="hidden space-y-4 mt-6 p-4 border rounded-lg bg-gray-50">
                            <h4 class="font-semibold text-gray-900">Bank Transfer Information</h4>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600">Please transfer the payment to:</p>
                                <div class="bg-white p-4 rounded border">
                                    <p class="text-sm"><strong>Bank Name:</strong> Example Bank</p>
                                    <p class="text-sm"><strong>Account Name:</strong> SCCMS Education</p>
                                    <p class="text-sm"><strong>Account Number:</strong> 1234567890</p>
                                    <p class="text-sm"><strong>Sort Code:</strong> 12-34-56</p>
                                    <p class="text-sm"><strong>Reference:</strong> STU<?= str_pad($_GET['course_id'], 4, '0', STR_PAD_LEFT) ?></p>
                                </div>
                                <div class="mt-4">
                                    <label for="transfer_receipt" class="block text-sm font-medium text-gray-700">Upload Transfer Receipt</label>
                                    <input type="file" 
                                           id="transfer_receipt" 
                                           name="transfer_receipt"
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <script>
                            function showPaymentForm(method) {
                                // Hide all forms first
                                document.getElementById('credit_card_form').classList.add('hidden');
                                document.getElementById('bank_transfer_form').classList.add('hidden');
                                
                                // Show the selected form
                                if (method === 'credit_card') {
                                    document.getElementById('credit_card_form').classList.remove('hidden');
                                } else if (method === 'bank_transfer') {
                                    document.getElementById('bank_transfer_form').classList.remove('hidden');
                                }
                            }
                        </script>
                        <div class="flex justify-end mt-6">
                            <button type="submit" 
                                    name="process_payment"
                                    <?php if ($children->num_rows === 0): ?>disabled<?php endif; ?>
                                    class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white transition-all duration-200 <?php echo ($children->num_rows > 0) ? 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500' : 'bg-gray-400 cursor-not-allowed opacity-75'; ?>">
                                <span class="material-icons-round text-sm mr-2">payment</span>
                                Process Payment
                            </button>
                        </div>

                        <?php if ($children->num_rows === 0): ?>
                            <div class="mt-4 text-center text-sm text-gray-600">
                                Please verify and link a student account before proceeding with the payment.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                <span class="material-icons-round text-gray-400 text-6xl">error_outline</span>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Course Not Found</h3>
                <p class="mt-2 text-gray-500">The requested course could not be found.</p>
                <div class="mt-6">
                    <a href="parent_payments.php" class="text-blue-600 hover:text-blue-800">Return to Course List</a>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
