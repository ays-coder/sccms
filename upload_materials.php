<?php
session_start();
require_once 'db_connect.php';

// Only allow tutors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $user_id = $_SESSION['user_id'];

    // Use prepared statements for safety
    $stmt = $conn->prepare("SELECT file_path FROM materials WHERE material_id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $file_to_delete = $row['file_path'];
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete); // Delete file from server
        }

        // Delete record from database
        $delete_stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ? AND uploaded_by = ?");
        $delete_stmt->bind_param("ii", $id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Rebuild material_id auto-increment values in sequence
        $conn->query("SET @autoid := 0");
        $conn->query("UPDATE materials SET material_id = @autoid := @autoid + 1 ORDER BY material_id");
        $conn->query("ALTER TABLE materials AUTO_INCREMENT = 1");
    }
    $stmt->close();

    header("Location: upload_materials.php?deleted=1");
    exit();
}

// Fetch materials
$user_id = $_SESSION['user_id'];
$materials = $conn->query("SELECT * FROM materials WHERE uploaded_by = $user_id ORDER BY upload_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Upload Materials | Tutor Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="p-6 bg-gray-100 min-h-screen">
  <h2 class="text-3xl font-bold mb-6">Upload Study Materials</h2>

  <?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
      ✅ Material uploaded successfully!
    </div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
      🗑️ Material deleted successfully!
    </div>
  <?php elseif (isset($_GET['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
      ❌ Error: <?= htmlspecialchars($_GET['error']) ?>
    </div>
  <?php endif; ?>

  <!-- Upload Form -->
  <form action="pdf.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow mb-8 max-w-xl">
    <div class="mb-4">
      <label class="block mb-1 font-semibold">Course ID</label>
      <input type="number" name="course_id" required class="w-full border px-4 py-2 rounded" />
    </div>
    <div class="mb-4">
      <label class="block mb-1 font-semibold">Title</label>
      <input type="text" name="title" required class="w-full border px-4 py-2 rounded" />
    </div>
    <div class="mb-4">
      <label class="block mb-1 font-semibold">Upload PDF</label>
      <input type="file" name="upload_pdf" accept=".pdf" required class="w-full" />
    </div>
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
      Upload
    </button>
  </form>

  <!-- Display Uploaded Materials -->
  <h3 class="text-2xl font-semibold mb-4">Your Uploaded Materials</h3>
  <div class="overflow-x-auto bg-white rounded shadow">
    <table class="min-w-full text-sm text-left">
      <thead class="bg-gray-200">
        <tr>
          <th class="px-4 py-2">Material ID</th>
          <th class="px-4 py-2">Course ID</th>
          <th class="px-4 py-2">Title</th>
          <th class="px-4 py-2">File</th>
          <th class="px-4 py-2">Uploaded By</th>
          <th class="px-4 py-2">Upload Date</th>
          <th class="px-4 py-2">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($materials && $materials->num_rows > 0): ?>
          <?php while ($row = $materials->fetch_assoc()): ?>
            <tr class="border-t hover:bg-gray-50">
              <td class="px-4 py-2"><?= $row['material_id'] ?></td>
              <td class="px-4 py-2"><?= $row['course_id'] ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['title']) ?></td>
              <td class="px-4 py-2">
                <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="text-blue-600 underline">View PDF</a>
              </td>
              <td class="px-4 py-2"><?= $row['uploaded_by'] ?></td>
              <td class="px-4 py-2"><?= $row['upload_date'] ?></td>
              <td class="px-4 py-2">
                <a href="upload_materials.php?delete=<?= $row['material_id'] ?>"
                   class="text-red-600 hover:underline"
                   onclick="return confirm('Are you sure you want to delete this material? This will remove the file from server and database.')">
                   Delete
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="px-4 py-4 text-center text-gray-500">No materials uploaded yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div> 
  <a href="tutor_dashboard.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded text-sm"> ← Back to Dashboard </a>
</body>
</html>