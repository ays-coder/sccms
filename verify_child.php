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

// Handle verification form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_student'])) {
    $verification_code = trim($_POST['verification_code']);
    
    // Verify the code
    $verify_stmt = $conn->prepare("
        SELECT sv.student_id, u.username, u.email
        FROM student_verification sv
        JOIN users u ON u.user_id = sv.student_id
        WHERE sv.verification_code = ? 
        AND sv.is_verified = 0
        AND u.parent_id IS NULL
    ");
    $verify_stmt->bind_param("s", $verification_code);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update student's parent_id
            $update_stmt = $conn->prepare("UPDATE users SET parent_id = ? WHERE user_id = ?");
            $update_stmt->bind_param("ii", $_SESSION['user_id'], $student['student_id']);
            $update_stmt->execute();
            
            // Mark verification as complete
            $complete_stmt = $conn->prepare("
                UPDATE student_verification 
                SET is_verified = 1, verified_at = NOW() 
                WHERE student_id = ?
            ");
            $complete_stmt->bind_param("i", $student['student_id']);
            $complete_stmt->execute();
            
            $conn->commit();
            $success_message = "Successfully verified and linked student: " . htmlspecialchars($student['username']);
            
            // If there was a pending course payment, redirect back
            if (isset($_SESSION['pending_course_payment'])) {
                $course_id = $_SESSION['pending_course_payment'];
                unset($_SESSION['pending_course_payment']);
                header("Location: course_payment.php?course_id=" . $course_id);
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to verify student. Please try again.";
            error_log("Verification error: " . $e->getMessage());
        }
    } else {
        $error_message = "Invalid verification code or student already verified.";
    }
}

// Get currently linked children
$children_stmt = $conn->prepare("
    SELECT u.user_id, u.username, u.email, sv.verification_code, sv.verified_at
    FROM users u
    LEFT JOIN student_verification sv ON sv.student_id = u.user_id
    WHERE u.parent_id = ? AND u.role = 'student'
    ORDER BY sv.verified_at DESC
");
$children_stmt->bind_param("i", $_SESSION['user_id']);
$children_stmt->execute();
$linked_children = $children_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Child | Parent Dashboard</title>
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
                    <h1 class="text-2xl font-bold text-blue-600">Verify Your Child</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="parent_dashboard.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-600 uppercase tracking-widest hover:bg-gray-200 active:bg-gray-300 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <span class="material-icons-round text-sm mr-2">dashboard</span>
                        Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
            <!-- Verification Form -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-900">Verify New Student</h2>
                    <p class="mt-1 text-sm text-gray-600">Enter your child's student verification code to link their account</p>
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

                <?php if ($success_message): ?>
                    <div class="p-4 bg-green-50 border-b border-gray-100">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="material-icons-round text-green-400">check_circle</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800"><?= $success_message ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="verification_code" class="block text-sm font-medium text-gray-700">
                                Student Verification Code
                            </label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="verification_code" 
                                       id="verification_code"
                                       required
                                       placeholder="Enter code (e.g., STU0001)"
                                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       pattern="STU[0-9]{4}"
                                       title="Please enter a valid verification code (e.g., STU0001)">
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Format: STU followed by 4 digits (e.g., STU0001)
                            </p>
                        </div>

                        <div>
                            <button type="submit"
                                    name="verify_student"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span class="material-icons-round text-sm mr-2">verified_user</span>
                                Verify Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Linked Students Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-900">Linked Students</h2>
                    <p class="mt-1 text-sm text-gray-600">List of students currently linked to your account</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($linked_children->num_rows > 0): ?>
                                <?php while ($child = $linked_children->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($child['username']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($child['email']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($child['verification_code']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?= $child['verified_at'] ? date('M j, Y', strtotime($child['verified_at'])) : 'Not verified' ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No students linked to your account yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
