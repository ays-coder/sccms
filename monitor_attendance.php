<?php


session_start();
require_once 'db_connect.php';
require_once 'libs/phpqrcode/qrlib.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$qrGenerated = false;
$qrImage = '';
$successMsg = '';
$selectedCourse = null;
$qrImageUrl = '';

// If a course is selected via GET (AJAX or page reload), generate QR link automatically
if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);

    // Fetch course info for display
    $courseStmt = $conn->prepare("SELECT course_id, course_name FROM courses WHERE course_id = ?");
    $courseStmt->bind_param("i", $course_id);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $selectedCourse = $courseResult->fetch_assoc();

    if ($selectedCourse) {
        // Generate hourly-based unique QR data
        $currentHour = date('Y-m-d H:00:00');
        $qrData = json_encode([
            'course_id' => $selectedCourse['course_id'],
            'course_name' => $selectedCourse['course_name'],
            'timestamp' => $currentHour,
            'valid_until' => date('Y-m-d H:59:59')
        ]);
        
        // Create a unique code that changes every hour
        $uniqueCode = md5($qrData . $currentHour);
        
        // Create qrcodes directory if it doesn't exist
        $qrcodesDir = __DIR__ . '/qrcodes';
        if (!file_exists($qrcodesDir)) {
            mkdir($qrcodesDir, 0777, true);
            // Set directory permissions explicitly for Windows
            chmod($qrcodesDir, 0777);
        }
        
        // Format filename without colons (Windows safe)
        $safeTime = str_replace(':', '', $currentHour);
        $qrFileName = "course_{$selectedCourse['course_id']}_{$safeTime}.png";
        $qrImagePath = $qrcodesDir . '/' . $qrFileName;
        $qrImageUrl = 'qrcodes/' . $qrFileName;
        
        // Generate QR code as PNG file
        QRcode::png($qrData, $qrImagePath, QR_ECLEVEL_H, 10);
        $qrGenerated = true;
        
        // Clean up old QR codes
        $files = glob(__DIR__ . '/qrcodes/course_' . $selectedCourse['course_id'] . '_*.png');
        foreach ($files as $file) {
            if ($file !== $qrImagePath && (time() - filemtime($file)) > 3600) {
                unlink($file);
            }
        }
        
        // First, let's check if there's already a QR code for this course and hour
        $checkStmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE course_id = ? AND DATE(generated_at) = CURDATE() AND HOUR(generated_at) = HOUR(NOW())");
        $checkStmt->bind_param("i", $selectedCourse['course_id']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows == 0) {
            // Save to database with current timestamp and admin user as marked_by
            $stmt = $conn->prepare("INSERT INTO attendance (course_id, qr_code_data, marked_by, student_id, date, status) VALUES (?, ?, ?, ?, CURDATE(), 'Absent')");
            $stmt->bind_param("isii", $selectedCourse['course_id'], $qrData, $_SESSION['user_id'], $_SESSION['user_id']);
            $stmt->execute();
            $qrGenerated = true;
        } else {
            // Update existing record
            $stmt = $conn->prepare("UPDATE attendance SET qr_code_data = ? WHERE course_id = ? AND DATE(generated_at) = CURDATE() AND HOUR(generated_at) = HOUR(NOW())");
            $stmt->bind_param("si", $qrData, $selectedCourse['course_id']);
            $stmt->execute();
            $qrGenerated = true;
        }

        $successMsg = "QR Code link generated successfully for course: " . htmlspecialchars($selectedCourse['course_name']) . " (ID: " . $selectedCourse['course_id'] . ")";
    }
}

// Get all courses for dropdown
$courseOptions = $conn->query("SELECT course_id, course_name FROM courses");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Attendance QR | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { font-family: 'Public Sans', sans-serif; }
    </style>
    <script>
        function autoGenerateQR(selectObj) {
            var courseId = selectObj.value;
            if (courseId) {
                window.location.href = "?course_id=" + courseId;
            } else {
                window.location.href = "monitor_attendance.php";
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-2xl font-bold text-blue-600">Generate Attendance QR</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="admin_dashboard.php" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                        <span class="material-icons">arrow_back</span>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            Create QR Code for Attendance
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Select a course to generate its attendance QR code. The code updates hourly for security.
                        </p>
                    </div>
                </div>

                <?php if ($successMsg): ?>
                    <div class="mt-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="material-icons text-green-400">check_circle</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800"><?= $successMsg ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-6">
                    <form method="GET" class="space-y-6">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="course_id" class="block text-sm font-medium text-gray-700">Select Course</label>
                                <select id="course_id" 
                                        name="course_id" 
                                        required 
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md"
                                        onchange="autoGenerateQR(this)">
                                    <option value="">-- Choose Course --</option>
                                    <?php
                                    $courseOptions->data_seek(0);
                                    while ($row = $courseOptions->fetch_assoc()): ?>
                                        <option value="<?= $row['course_id'] ?>" 
                                                <?= (isset($selectedCourse) && $selectedCourse['course_id'] == $row['course_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['course_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php if ($qrGenerated && $selectedCourse): ?>
        <div class="mt-8">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-blue-600 px-6 py-4">
                    <h3 class="text-xl font-semibold text-white">Generated QR Code</h3>
                </div>
                
                <div class="p-6">
                    <div class="text-center mb-6">
                        <h4 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($selectedCourse['course_name']) ?></h4>
                        <p class="text-sm text-gray-500">Valid until <?= date('h:i A', strtotime($currentHour) + 3600) ?></p>
                    </div>

                    <!-- QR Code Display -->
                    <div class="flex justify-center mb-8">
                        <div class="relative">
                            <img src="<?= $qrImageUrl ?>" 
                                 alt="Attendance QR Code" 
                                 class="w-64 h-64 border-4 border-white shadow-xl rounded-lg" />
                            <div class="absolute -bottom-3 -right-3 bg-green-500 text-white p-2 rounded-full shadow-lg">
                                <span class="material-icons">qr_code_2</span>
                            </div>
                        </div>
                    </div>

                    <!-- Share Options -->
                    <div class="space-y-4">
                        <div class="flex flex-wrap justify-center gap-3">
                            <!-- WhatsApp -->
                            <a href="https://wa.me/?text=<?= urlencode("Attendance QR Code for {$selectedCourse['course_name']}\nValid until: " . date('h:i A', strtotime($currentHour) + 3600) . "\n" . "http://{$_SERVER['HTTP_HOST']}/{$qrImageUrl}") ?>" 
                               target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-150 ease-in-out">
                                <span class="material-icons mr-2">whatsapp</span>
                                Share via WhatsApp
                            </a>

                            <!-- Download -->
                            <a href="<?= $qrImageUrl ?>" 
                               download="qr_<?= $selectedCourse['course_id'] ?>_<?= date('YmdH') ?>.png"
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">
                                <span class="material-icons mr-2">download</span>
                                Download QR
                            </a>

                            <!-- Share -->
                            <button onclick="shareQR()" 
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-150 ease-in-out">
                                <span class="material-icons mr-2">share</span>
                                Share QR
                            </button>
                        </div>

                        <!-- Auto-refresh Timer -->
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-600">QR Code will refresh in:</p>
                            <p class="text-2xl font-mono text-gray-800 mt-2" id="countdown"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Share functionality
            async function shareQR() {
                const shareData = {
                    title: 'Attendance QR Code',
                    text: 'QR Code for <?= htmlspecialchars($selectedCourse['course_name']) ?>',
                    url: window.location.origin + '/<?= $qrImageUrl ?>'
                };

                try {
                    if (navigator.share) {
                        await navigator.share(shareData);
                    } else {
                        // Fallback to copying to clipboard
                        await navigator.clipboard.writeText(shareData.url);
                        alert('QR Code link copied to clipboard!');
                    }
                } catch (err) {
                    console.error('Error sharing:', err);
                }
            }

            // Countdown Timer
            function updateCountdown() {
                const now = new Date();
                const nextHour = new Date(now);
                nextHour.setHours(nextHour.getHours() + 1, 0, 0, 0);
                const timeLeft = nextHour - now;
                
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                document.getElementById('countdown').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    window.location.reload();
                }
            }

            // Update countdown every second
            updateCountdown();
            setInterval(updateCountdown, 1000);
        </script>
    <?php endif; ?>

      <script>
        // Copy link functionality
        function copyQRLink() {
          const link = window.location.origin + '/<?= $qrLink ?>';
          navigator.clipboard.writeText(link).then(() => {
            alert('Link copied to clipboard!');
          });
        }

        // Countdown timer
        function updateCountdown() {
          const now = new Date();
          const nextHour = new Date(now);
          nextHour.setHours(nextHour.getHours() + 1, 0, 0, 0);
          const diff = nextHour - now;
          
          const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
          const seconds = Math.floor((diff % (1000 * 60)) / 1000);
          
          document.getElementById('countdown').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        // Update countdown every second
        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Reload page at the start of new hour
        const now = new Date();
        const nextHour = new Date(now);
        nextHour.setHours(nextHour.getHours() + 1, 0, 0, 0);
        const timeToNextHour = nextHour - now;
        setTimeout(() => {
          window.location.reload();
        }, timeToNextHour);
      </script>
  </main>
</body>
</html>