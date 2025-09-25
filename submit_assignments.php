<?php
session_start();
require_once 'db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle assignment submission
if (isset($_POST['submit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    
    // Check if file was uploaded
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
        $file = $_FILES['submission_file'];
        
        // Check file type (only allow PDF)
        $allowed_types = ['application/pdf'];
        if (!in_array($file['type'], $allowed_types)) {
            $message = "Only PDF files are allowed!";
            $message_type = "error";
        } else {
            // Create unique filename
            $filename = time() . '_' . $_SESSION['user_id'] . '_' . $file['name'];
            $upload_path = 'uploads/assignments/' . $filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('uploads/assignments')) {
                mkdir('uploads/assignments', 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Check if student has already submitted this assignment
                $check_stmt = $conn->prepare("SELECT submission_id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
                $check_stmt->bind_param("is", $assignment_id, $_SESSION['user_id']);
                $check_stmt->execute();
                $existing_submission = $check_stmt->get_result()->fetch_assoc();
                
                if ($existing_submission) {
                    // Update existing submission
                    $stmt = $conn->prepare("UPDATE assignment_submissions SET file_path = ?, submission_date = CURRENT_TIMESTAMP WHERE assignment_id = ? AND student_id = ?");
                    $stmt->bind_param("sis", $upload_path, $assignment_id, $_SESSION['user_id']);
                } else {
                    // Create new submission
                    $stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $assignment_id, $_SESSION['user_id'], $upload_path);
                }
                
                if ($stmt->execute()) {
                    $message = "Assignment submitted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error submitting assignment. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Error uploading file. Please try again.";
                $message_type = "error";
            }
        }
    } else {
        $message = "Please select a file to upload.";
        $message_type = "error";
    }
}

// Fetch assignments with submission status
$sql = "
    SELECT 
        a.assignment_id,
        a.title,
        a.description,
        a.due_date,
        c.course_name,
        t.username as tutor_name,
        s.submission_date,
        s.grade,
        s.status as submission_status,
        s.file_path as submitted_file
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN tutor t ON a.uploaded_by = t.tutor_id
    LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id 
        AND s.student_id = ?
    WHERE a.due_date >= CURDATE()
    ORDER BY a.due_date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$assignments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignments | Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-md px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="bg-blue-100 p-2 rounded-full">
                    <i class="fas fa-tasks text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Submit Assignments</h1>
                    <p class="text-sm text-gray-600">View and submit your assignments</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm text-gray-600">Welcome back,</p>
                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($_SESSION['username']) ?></p>
                </div>
                <a href="student_dashboard.php" 
                   class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition duration-150">
                    <i class="fas fa-arrow-left"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-8 px-4">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
                <div class="flex items-center">
                    <i class="fas <?= $message_type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?> mr-2"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-6">
            <?php if ($assignments && $assignments->num_rows > 0): ?>
                <?php while ($row = $assignments->fetch_assoc()): 
                    $due_date = new DateTime($row['due_date']);
                    $now = new DateTime();
                    $days_remaining = $now->diff($due_date)->days;
                    $is_submitted = !empty($row['submission_date']);
                    $status_color = $is_submitted ? 'green' : ($days_remaining <= 3 ? 'red' : 'yellow');
                ?>
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?= htmlspecialchars($row['title']) ?>
                                </h3>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium 
                                        <?= $is_submitted ? 'bg-green-100 text-green-700' : 
                                            ($days_remaining <= 3 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                        <?= $is_submitted ? 'Submitted' : ($days_remaining . ' days left') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-book-open w-5"></i>
                                    <span><?= htmlspecialchars($row['course_name']) ?></span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-chalkboard-teacher w-5"></i>
                                    <span><?= htmlspecialchars($row['tutor_name']) ?></span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-calendar w-5"></i>
                                    <span>Due: <?= date('M d, Y', strtotime($row['due_date'])) ?></span>
                                </div>
                                <?php if ($is_submitted): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock w-5"></i>
                                        <span>Submitted: <?= date('M d, Y H:i', strtotime($row['submission_date'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <?php if ($is_submitted): ?>
                                    <div class="flex items-center space-x-4">
                                        <a href="<?= htmlspecialchars($row['submitted_file']) ?>" 
                                           target="_blank"
                                           class="flex items-center space-x-2 text-blue-600 hover:text-blue-700">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>View Submission</span>
                                        </a>
                                        <?php if ($row['grade']): ?>
                                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                                                Grade: <?= $row['grade'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" enctype="multipart/form-data" class="flex items-center space-x-4 <?= $is_submitted ? 'w-auto' : 'w-full' ?>">
                                    <input type="hidden" name="assignment_id" value="<?= $row['assignment_id'] ?>">
                                    <div class="flex-grow">
                                        <input type="file" 
                                               name="submission_file" 
                                               accept=".pdf"
                                               class="w-full text-sm text-gray-600 p-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                               required>
                                    </div>
                                    <button type="submit" 
                                            name="submit_assignment"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                                        <i class="fas fa-upload"></i>
                                        <span><?= $is_submitted ? 'Update Submission' : 'Submit Assignment' ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                        <i class="fas fa-tasks text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Active Assignments</h3>
                    <p class="text-gray-500">There are no assignments due at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>