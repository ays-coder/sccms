<?php

session_start();
require_once 'db_connect.php';

// ...existing authorization code...
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

// ...existing deletion handling code...
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT file_path FROM materials WHERE material_id = ? AND uploaded_by = ?");
    $stmt->bind_param("is", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $file_to_delete = $row['file_path'];
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }

        $delete_stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ? AND uploaded_by = ?");
        $delete_stmt->bind_param("is", $id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
    $stmt->close();

    header("Location: upload_materials.php?deleted=1");
    exit();
}

// ...existing course fetching code...
$tutor_id = $_SESSION['user_id'];
$courses_query = "SELECT course_id, course_name FROM courses WHERE tutor_id = ?";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("s", $tutor_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// ...existing materials fetching code...
$materials_query = "
    SELECT m.*, c.course_name 
    FROM materials m 
    LEFT JOIN courses c ON m.course_id = c.course_id 
    WHERE m.uploaded_by = ? 
    ORDER BY m.upload_date DESC";
$materials_stmt = $conn->prepare($materials_query);
$materials_stmt->bind_param("s", $tutor_id);
$materials_stmt->execute();
$materials = $materials_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Upload Materials | Tutor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-book-reader mr-2"></i>
                Upload Study Materials
            </h1>
            <a href="tutor_dashboard.php" class="flex items-center gap-2 bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Material uploaded successfully!
                </div>
            </div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded shadow">
                <div class="flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Material deleted successfully!
                </div>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Error: <?= htmlspecialchars($_GET['error']) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Upload Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-6 flex items-center">
                    <i class="fas fa-upload mr-2 text-blue-600"></i>
                    Upload New Material
                </h2>
                <form action="pdf.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-book mr-2"></i>Select Course
                        </label>
                        <select name="course_id" required 
                                class="w-full border border-gray-300 rounded-md shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Choose a course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars($course['course_id']) ?>">
                                    <?= htmlspecialchars($course['course_name']) ?> 
                                    (ID: <?= htmlspecialchars($course['course_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2"></i>Title
                        </label>
                        <input type="text" name="title" required 
                               class="w-full border border-gray-300 rounded-md shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-file-pdf mr-2"></i>Upload PDF
                        </label>
                        <input type="file" name="upload_pdf" accept=".pdf" required 
                               class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Upload Material
                    </button>
                </form>
            </div>

            <!-- Materials List -->
            <div class="bg-white p-6 rounded-lg shadow-md md:col-span-2">
                <h2 class="text-xl font-semibold mb-6 flex items-center">
                    <i class="fas fa-list mr-2 text-gray-600"></i>
                    Your Uploaded Materials
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($materials && $materials->num_rows > 0): ?>
                                <?php while ($row = $materials->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $row['material_id'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($row['course_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="<?= htmlspecialchars($row['file_path']) ?>" 
                                               target="_blank" 
                                               class="text-blue-600 hover:text-blue-900 flex items-center gap-2">
                                                <i class="fas fa-file-pdf"></i>
                                                View PDF
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($row['upload_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="upload_materials.php?delete=<?= $row['material_id'] ?>"
                                               class="text-red-600 hover:text-red-900 flex items-center gap-2"
                                               onclick="return confirm('Are you sure you want to delete this material?')">
                                                <i class="fas fa-trash-alt"></i>
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center py-4">
                                            <i class="fas fa-folder-open text-4xl mb-2"></i>
                                            No materials uploaded yet.
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>