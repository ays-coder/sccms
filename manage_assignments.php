<?php

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // This is the tutor_id for tutors
$role = $_SESSION['role'];
$message = "";

// Fetch courses for dropdown
$courses_query = "SELECT course_id, course_name FROM courses WHERE tutor_id = ?";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("s", $user_id); // user_id is already a string for tutors
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Handle Assignment Update
if (isset($_POST['update_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("UPDATE assignments SET course_id=?, title=?, description=?, due_date=? WHERE assignment_id=? AND uploaded_by=?");
    $stmt->bind_param("isssis", $course_id, $title, $description, $due_date, $assignment_id, $user_id); // user_id is already a string for tutors
    if ($stmt->execute()) {
        $message = "Assignment updated successfully!";
    } else {
        $message = "Failed to update assignment.";
    }
}

// Handle Assignment Upload
if ($role === 'tutor' && isset($_POST['upload_assignment'])) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date, uploaded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $course_id, $title, $description, $due_date, $user_id); // user_id is already a string for tutors
    if ($stmt->execute()) {
        $message = "Assignment uploaded successfully!";
    } else {
        $message = "Failed to upload assignment.";
    }
}

// Handle Assignment Delete
if (isset($_POST['delete_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ? AND uploaded_by = ?");
    $stmt->bind_param("is", $assignment_id, $user_id); // user_id is already a string for tutors
    if ($stmt->execute()) {
        $message = "Assignment deleted successfully!";
    } else {
        $message = "Failed to delete assignment.";
    }
}

// Fetch assignment for editing if requested
$edit_assignment = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM assignments WHERE assignment_id = ? AND uploaded_by = ?");
    $stmt->bind_param("is", $edit_id, $user_id); // user_id is already a string for tutors
    $stmt->execute();
    $edit_assignment = $stmt->get_result()->fetch_assoc();
}

// Student submission handling code remains the same
// ... existing submission code ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-6 font-sans">
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-blue-700">Manage Assignments</h1>
        <a href="tutor_dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-6 flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Tutor Assignment Form -->
    <?php if ($role === 'tutor'): ?>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-xl font-semibold mb-4">
            <?= $edit_assignment ? 'Edit Assignment' : 'Create New Assignment' ?>
        </h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="<?= $edit_assignment ? 'update_assignment' : 'upload_assignment' ?>" value="1">
            <?php if ($edit_assignment): ?>
                <input type="hidden" name="assignment_id" value="<?= $edit_assignment['assignment_id'] ?>">
            <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Course</label>
                    <select name="course_id" required class="w-full border border-gray-300 p-2 rounded focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>" 
                                <?= ($edit_assignment && $edit_assignment['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Title</label>
                    <input type="text" name="title" required 
                           value="<?= $edit_assignment ? htmlspecialchars($edit_assignment['title']) : '' ?>"
                           class="w-full border border-gray-300 p-2 rounded">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" required rows="4" 
                          class="w-full border border-gray-300 p-2 rounded"><?= $edit_assignment ? htmlspecialchars($edit_assignment['description']) : '' ?></textarea>
            </div>

            <div class="w-full md:w-1/3">
                <label class="block text-sm font-medium mb-1">Due Date</label>
                <input type="date" name="due_date" required 
                       value="<?= $edit_assignment ? $edit_assignment['due_date'] : '' ?>"
                       class="w-full border border-gray-300 p-2 rounded">
            </div>

            <div class="flex justify-end space-x-2">
                <?php if ($edit_assignment): ?>
                    <a href="manage_assignments.php" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </a>
                <?php endif; ?>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    <?= $edit_assignment ? 'Update Assignment' : 'Create Assignment' ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Assignment List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="text-xl font-semibold">
                <?= $role === 'tutor' ? "Your Assignments" : "Available Assignments" ?>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $query = ($role === 'tutor') 
                        ? "SELECT a.*, c.course_name FROM assignments a 
                           JOIN courses c ON a.course_id = c.course_id 
                           WHERE a.uploaded_by = ? ORDER BY a.assignment_id DESC"
                        : "SELECT a.*, c.course_name FROM assignments a 
                           JOIN courses c ON a.course_id = c.course_id 
                           ORDER BY a.assignment_id DESC";
                    
                    $stmt = $conn->prepare($query);
                    if ($role === 'tutor') {
                        $stmt->bind_param("s", $user_id);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($row['assignment_id']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($row['course_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($row['title']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($row['description']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('M d, Y', strtotime($row['due_date'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($role === 'tutor'): ?>
                                <div class="flex items-center space-x-3">
                                    <a href="?edit=<?= $row['assignment_id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this assignment?')">
                                        <input type="hidden" name="delete_assignment" value="1">
                                        <input type="hidden" name="assignment_id" value="<?= $row['assignment_id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                    <a href="view_submissions.php?assignment_id=<?= $row['assignment_id'] ?>" 
                                       class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-eye"></i> View Submissions
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Student submission form remains the same -->
                                <form method="POST" enctype="multipart/form-data" class="flex items-center space-x-2">
                                    <input type="hidden" name="submit_assignment" value="1">
                                    <input type="hidden" name="assignment_id" value="<?= $row['assignment_id'] ?>">
                                    <input type="file" name="submission_file" required class="text-sm">
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                        Submit
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No assignments found
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>