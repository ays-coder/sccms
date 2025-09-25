 <?php
session_start();
require_once 'db_connect.php';

// Only allow tutors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $uploaded_by = $_SESSION['user_id'];
    $upload_date = date("Y-m-d");

    // Check if file is uploaded
    if (isset($_FILES['upload_pdf']) && $_FILES['upload_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['upload_pdf']['tmp_name'];
        $file_name = basename($_FILES['upload_pdf']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Allow only PDF
        if ($file_ext === 'pdf') {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $new_file_name = uniqid() . '_' . $file_name;
            $target_file = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO materials (course_id, title, file_path, uploaded_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $course_id, $title, $target_file, $uploaded_by);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: upload_materials.php?success=1");
                    exit();
                } else {
                    unlink($target_file); // Delete the uploaded file if database insert fails
                    header("Location: upload_materials.php?error=" . urlencode("Database error: " . $stmt->error));
                    exit();
                }
            } else {
                header("Location: upload_materials.php?error=" . urlencode("Failed to move uploaded file"));
                exit();
            }
        } else {
            header("Location: upload_materials.php?error=" . urlencode("Only PDF files are allowed"));
            exit();
        }
    } else {
        header("Location: upload_materials.php?error=" . urlencode("No file uploaded or upload error"));
        exit();
    }
}
?>
