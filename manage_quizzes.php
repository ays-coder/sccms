<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $created_by = $_SESSION['user_id'];

    // Insert quiz
    $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, created_by, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isi", $course_id, $title, $created_by);
    if ($stmt->execute()) {
        $quiz_id = $stmt->insert_id;

        // Insert questions
        for ($i = 0; $i < count($_POST['question']); $i++) {
            $question = $_POST['question'][$i];
            $a = $_POST['option_a'][$i];
            $b = $_POST['option_b'][$i];
            $c = $_POST['option_c'][$i];
            $d = $_POST['option_d'][$i];
            $correct = $_POST['correct_option'][$i];

            $qstmt = $conn->prepare("INSERT INTO questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $qstmt->bind_param("issssss", $quiz_id, $question, $a, $b, $c, $d, $correct);
            $qstmt->execute();
        }

        $message = "Quiz uploaded successfully!";
    } else {
        $message = "Failed to upload quiz.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage Quizzes | Tutor Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
  <div class="max-w-5xl mx-auto p-6 bg-white shadow-md rounded mt-10">
    <h1 class="text-2xl font-bold mb-6 text-center">Upload MCQ Quiz</h1>

    <?php if ($message): ?>
      <div class="mb-4 text-green-600 font-semibold text-center"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-4">
        <label class="block mb-1 font-medium">Course ID</label>
        <input type="number" name="course_id" class="w-full border border-gray-300 p-2 rounded" required>
      </div>

      <div class="mb-6">
        <label class="block mb-1 font-medium">Quiz Title</label>
        <input type="text" name="title" class="w-full border border-gray-300 p-2 rounded" required>
      </div>

      <div id="questionSection">
        <h2 class="text-lg font-semibold mb-2">Questions</h2>
        <div class="question-block mb-4 border p-4 rounded bg-gray-50">
          <input type="text" name="question[]" placeholder="Enter Question" class="w-full mb-2 p-2 border rounded" required>
          <input type="text" name="option_a[]" placeholder="Option A" class="w-full mb-2 p-2 border rounded" required>
          <input type="text" name="option_b[]" placeholder="Option B" class="w-full mb-2 p-2 border rounded" required>
          <input type="text" name="option_c[]" placeholder="Option C" class="w-full mb-2 p-2 border rounded" required>
          <input type="text" name="option_d[]" placeholder="Option D" class="w-full mb-2 p-2 border rounded" required>
          <input type="text" name="correct_option[]" placeholder="Correct Option (A/B/C/D)" class="w-full p-2 border rounded" required>
        </div>
      </div>

      <button type="button" onclick="addQuestion()" class="mb-6 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add More Question</button>

      <div class="text-center">
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">Submit Quiz</button>
      </div>
    </form>
  </div>

  <script>
    function addQuestion() {
      const section = document.getElementById('questionSection');
      const block = document.querySelector('.question-block').cloneNode(true);
      block.querySelectorAll('input').forEach(input => input.value = '');
      section.appendChild(block);
    }
  </script>
</body>
</html>
