<?php
session_start();
require_once 'db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Since we don't have the enrollment system yet, temporarily show all materials
$sql = "
    SELECT m.material_id, m.course_id, c.course_name, m.title, m.file_path, 
           m.upload_date, t.username AS tutor_name, 
           SUBSTRING_INDEX(m.file_path, '/', -1) as filename
    FROM materials m
    JOIN courses c ON m.course_id = c.course_id
    JOIN tutor t ON m.uploaded_by = t.tutor_id
    ORDER BY m.upload_date DESC
";

$materials = $conn->query($sql);

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return number_format($bytes) . ' bytes';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Materials | Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
<header class="bg-white shadow-md px-6 py-4">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <div class="bg-blue-100 p-2 rounded-full">
                <i class="fas fa-book-reader text-blue-600 text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Study Materials</h1>
                <p class="text-sm text-gray-600">Access your course materials</p>
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
    <?php if ($materials && $materials->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($row = $materials->fetch_assoc()):
                $file_path = $row['file_path'];
                $file_size = file_exists($file_path) ? formatFileSize(filesize($file_path)) : 'N/A';
                $file_extension = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
                
                // Determine file type icon and color
                [$icon_class, $color_class] = match($file_extension) {
                    'pdf' => ['fa-file-pdf', 'text-red-600 bg-red-50'],
                    'doc', 'docx' => ['fa-file-word', 'text-blue-600 bg-blue-50'],
                    'xls', 'xlsx' => ['fa-file-excel', 'text-green-600 bg-green-50'],
                    'ppt', 'pptx' => ['fa-file-powerpoint', 'text-orange-600 bg-orange-50'],
                    'jpg', 'jpeg', 'png', 'gif' => ['fa-file-image', 'text-purple-600 bg-purple-50'],
                    'zip', 'rar' => ['fa-file-archive', 'text-yellow-600 bg-yellow-50'],
                    default => ['fa-file', 'text-gray-600 bg-gray-50']
                };
            ?>
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="<?= $color_class ?> p-3 rounded-lg">
                                    <i class="fas <?= $icon_class ?> text-2xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($row['course_name']) ?>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        <?= date('M d, Y', strtotime($row['upload_date'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 line-clamp-2">
                            <?= htmlspecialchars($row['title']) ?>
                        </h3>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-chalkboard-teacher w-5"></i>
                                <span class="truncate"><?= htmlspecialchars($row['tutor_name']) ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-file-alt w-5"></i>
                                <span class="truncate" title="<?= htmlspecialchars($row['filename']) ?>">
                                    <?= htmlspecialchars($row['filename']) ?>
                                </span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-weight-hanging w-5"></i>
                                <span><?= $file_size ?></span>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <a href="<?= htmlspecialchars($file_path) ?>" 
                               download
                               class="inline-flex items-center justify-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-all duration-200 w-full sm:w-auto">
                                <i class="fas fa-download"></i>
                                <span>Download Material</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                <i class="fas fa-folder-open text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Materials Available</h3>
            <p class="text-gray-500">There are no materials available for your enrolled courses yet.</p>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
