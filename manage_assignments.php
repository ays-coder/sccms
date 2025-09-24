<?php
session_start();
require_once 'db_connect.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = "";

/* --------------------------
   Tutor: Upload New Assignment (with PDF)
--------------------------- */
if ($role === 'tutor' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_assignment'])) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    $file_path = null;
    if (!empty($_FILES['assignment_file']['name'])) {
        $upload_dir = "uploads/assignment_pdfs/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . "_" . basename($_FILES['assignment_file']['name']);
        $file_tmp = $_FILES['assignment_file']['tmp_name'];
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($file_tmp, $file_path)) {
            $file_path = null;
            $message = "File upload failed!";
        }
    }

    $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, file_path, due_date, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $course_id, $title, $description, $file_path, $due_date, $user_id);
    if ($stmt->execute()) {
        $message = "Assignment uploaded successfully!";
    } else {
        $message = "Failed to upload assignment.";
    }
}

/* --------------------------
   Tutor: Delete Assignment
--------------------------- */
if ($role === 'tutor' && isset($_POST['delete_assignment'])) {
    $assignment_id = intval($_POST['assignment_id']);

    // First fetch file path to delete from server
    $stmt = $conn->prepare("SELECT file_path FROM assignments WHERE assignment_id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $assignment_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    $stmt->fetch();
    $stmt->close();

    if ($file_path && file_exists($file_path)) {
        unlink($file_path); // delete file
    }

    // Delete from DB
    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $assignment_id, $user_id);
    if ($stmt->execute()) {
        $message = "Assignment deleted successfully!";
    } else {
        $message = "Failed to delete assignment.";
    }
}

/* --------------------------
   Student: Submit Assignment
--------------------------- */
if ($role === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $file_name = $_FILES['submission_file']['name'];
    $file_tmp = $_FILES['submission_file']['tmp_name'];
    $upload_dir = "uploads/assignments/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_path = $upload_dir . time() . "_" . basename($file_name);
    if (move_uploaded_file($file_tmp, $file_path)) {
        $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, submitted_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $assignment_id, $user_id, $file_path);
        if ($stmt->execute()) {
            $message = "Assignment submitted successfully!";
        } else {
            $message = "Failed to save submission.";
        }
    } else {
        $message = "File upload failed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6 font-sans">
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-center text-blue-700">Manage Assignments</h1>

    <?php if (!empty($message)): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Tutor Upload Form -->
    <?php if ($role === 'tutor'): ?>
    <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow mb-10">
        <input type="hidden" name="upload_assignment" value="1">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Course ID</label>
            <input type="text" name="course_id" required class="w-full border border-gray-300 p-2 rounded">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Title</label>
            <input type="text" name="title" required class="w-full border border-gray-300 p-2 rounded">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" required class="w-full border border-gray-300 p-2 rounded"></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Assignment PDF</label>
            <input type="file" name="assignment_file" accept="application/pdf" class="w-full border p-2 rounded">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Due Date</label>
            <input type="date" name="due_date" required class="w-full border border-gray-300 p-2 rounded">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Upload Assignment</button>
    </form>
    <?php endif; ?>

    <!-- Assignment List -->
    <h2 class="text-2xl font-semibold mb-4"><?= $role === 'tutor' ? "Uploaded Assignments" : "Available Assignments" ?></h2>
    <div class="overflow-x-auto bg-white rounded shadow mb-10">
        <table class="min-w-full text-sm text-left text-gray-600">
            <thead class="bg-gray-200 text-gray-700 text-xs uppercase">
            <tr>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Course ID</th>
                <th class="px-4 py-2">Title</th>
                <th class="px-4 py-2">Description</th>
                <th class="px-4 py-2">PDF</th>
                <th class="px-4 py-2">Due Date</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $query = ($role === 'tutor') 
                ? "SELECT * FROM assignments WHERE uploaded_by = $user_id ORDER BY assignment_id DESC"
                : "SELECT * FROM assignments ORDER BY assignment_id DESC";
            $result = $conn->query($query);

            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-2"><?= htmlspecialchars($row['assignment_id']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['course_id']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['title']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['description']) ?></td>
                <td class="px-4 py-2">
                    <?php if (!empty($row['file_path'])): ?>
                        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="text-blue-600 underline">View PDF</a>
                    <?php else: ?>
                        <span class="text-gray-400">No PDF</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['due_date']) ?></td>
                <td class="px-4 py-2 space-x-2">
                    <?php if ($role === 'student'): ?>
                        <form method="POST" enctype="multipart/form-data" class="flex space-x-2">
                            <input type="hidden" name="submit_assignment" value="1">
                            <input type="hidden" name="assignment_id" value="<?= $row['assignment_id'] ?>">
                            <input type="file" name="submission_file" required class="border p-1 rounded text-xs">
                            <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded text-xs">Submit</button>
                        </form>
                    <?php elseif ($role === 'tutor'): ?>
                        <a href="view_submissions.php?assignment_id=<?= $row['assignment_id'] ?>" 
                           class="bg-purple-600 text-white px-2 py-1 rounded text-xs hover:bg-purple-700">
                            View Submissions
                        </a>
                        <form method="POST" class="inline">
                            <input type="hidden" name="delete_assignment" value="1">
                            <input type="hidden" name="assignment_id" value="<?= $row['assignment_id'] ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this assignment?')" 
                                    class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">
                                Delete
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
                <td colspan="7" class="text-center text-gray-500 py-4">No assignments found.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
