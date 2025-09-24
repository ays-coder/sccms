 <?php
session_start();
require_once 'db_connect.php';

// Ensure only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = "";
$email = "";

// Fetch student details
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

$message = "";

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $assignment_id = intval($_POST['assignment_id']);

    // File upload
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/submissions/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['submission_file']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $filePath)) {
            // Save into submissions table
            $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, submitted_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $assignment_id, $user_id, $filePath);
            if ($stmt->execute()) {
                $message = "Assignment submitted successfully!";
            } else {
                $message = "Error saving submission to database.";
            }
        } else {
            $message = "File upload failed.";
        }
    } else {
        $message = "Please select a file to upload.";
    }
}

// Fetch available assignments including PDF path
$assignments = $conn->query("SELECT assignment_id, title, description, file_path, due_date FROM assignments ORDER BY due_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submit Assignments | Student Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

  <!-- Header -->
  <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <span class="material-icons text-purple-600 text-3xl">upload</span>
      <span class="text-xl font-bold text-purple-700">Submit Assignments</span>
    </div>
    <div class="text-right">
      <div class="text-gray-700 font-semibold">
        <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($email) ?>)
      </div>
      <a href="student_dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mt-2 inline-block">Back to Dashboard</a>
    </div>
  </header>

  <!-- Main -->
  <main class="max-w-5xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-6 text-center">Available Assignments</h1>

    <?php if ($message): ?>
      <div class="bg-green-100 text-green-700 px-4 py-2 mb-6 rounded"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($assignments->num_rows > 0): ?>
      <div class="space-y-6">
        <?php while ($row = $assignments->fetch_assoc()): ?>
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-2 text-gray-800"><?= htmlspecialchars($row['title']) ?></h2>
            <p class="text-gray-600 mb-2"><?= nl2br(htmlspecialchars($row['description'])) ?></p>

            <!-- Show PDF if tutor uploaded -->
            <?php if (!empty($row['file_path'])): ?>
              <p class="mb-3 flex gap-6">
                <!-- View PDF -->
                <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" 
                   class="inline-flex items-center gap-1 text-blue-600 font-medium hover:underline">
                  <span class="material-icons text-sm">picture_as_pdf</span> View Assignment PDF
                </a>

                <!-- Download PDF -->
                <a href="<?= htmlspecialchars($row['file_path']) ?>" download 
                   class="inline-flex items-center gap-1 text-green-600 font-medium hover:underline">
                  <span class="material-icons text-sm">download</span> Download PDF
                </a>
              </p>
            <?php endif; ?>

            <p class="text-sm text-red-600 font-semibold mb-4">Due: <?= date("F j, Y", strtotime($row['due_date'])) ?></p>

            <!-- Upload form -->
            <form method="POST" enctype="multipart/form-data" class="space-y-3">
              <input type="hidden" name="assignment_id" value="<?= $row['assignment_id'] ?>">
              <input type="file" name="submission_file" accept="application/pdf" class="block w-full border p-2 rounded" required>
              <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                Submit Assignment
              </button>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-600 text-center">No assignments available at the moment.</p>
    <?php endif; ?>
  </main>
</body>
</html>
