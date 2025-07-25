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
                $stmt = $conn->prepare("INSERT INTO materials (course_id, title, file_path, upload_pdf, uploaded_by, upload_date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssis", $course_id, $title, $target_file, $new_file_name, $uploaded_by, $upload_date);
                $stmt->execute();
                $stmt->close();

                header("Location: materials.php?success=1");
                exit();
            } else {
                header("Location: materials.php?error=upload_failed");
                exit();
            }
        } else {
            header("Location: materials.php?error=invalid_file");
            exit();
        }
    } else {
        header("Location: materials.php?error=no_file");
        exit();
    }
}
?>
