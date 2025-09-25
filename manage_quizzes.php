<?php

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

$message = "";
$tutor_id = $_SESSION['user_id'];

// Fetch courses assigned to this tutor
$courses_query = "SELECT course_id, course_name FROM courses WHERE tutor_id = ?";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("s", $tutor_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = intval($_POST['course_id']);
    $title = trim($_POST['title']);
    $created_by = $_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        // Validate if course belongs to this tutor
        $validate_course = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND tutor_id = ?");
        $validate_course->bind_param("is", $course_id, $created_by);
        $validate_course->execute();
        if ($validate_course->get_result()->num_rows === 0) {
            throw new Exception("Invalid course selection");
        }

        // Insert quiz
        $stmt = $conn->prepare("INSERT INTO quiz (course_id, title, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $course_id, $title, $created_by);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create quiz");
        }

        $quiz_id = $stmt->insert_id;

        // Insert questions
        foreach ($_POST['question'] as $i => $question) {
            $question = trim($question);
            $a = trim($_POST['option_a'][$i]);
            $b = trim($_POST['option_b'][$i]);
            $c = trim($_POST['option_c'][$i]);
            $d = trim($_POST['option_d'][$i]);
            $correct = strtoupper(trim($_POST['correct_option'][$i]));

            if (!in_array($correct, ['A', 'B', 'C', 'D'])) {
                throw new Exception("Invalid correct option. Please use A, B, C, or D.");
            }

            $qstmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $qstmt->bind_param("issssss", $quiz_id, $question, $a, $b, $c, $d, $correct);
            if (!$qstmt->execute()) {
                throw new Exception("Failed to add questions");
            }
        }

        $conn->commit();
        $message = "Quiz uploaded successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch existing quizzes
$existing_quizzes = $conn->prepare("
    SELECT q.*, c.course_name, COUNT(qq.question_id) as question_count 
    FROM quiz q 
    JOIN courses c ON q.course_id = c.course_id 
    LEFT JOIN quiz_questions qq ON q.quiz_id = qq.quiz_id 
    WHERE q.created_by = ? 
    GROUP BY q.quiz_id 
    ORDER BY q.created_at DESC
");
$existing_quizzes->bind_param("s", $tutor_id);
$existing_quizzes->execute();
$quizzes = $existing_quizzes->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Quizzes | Tutor Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-5xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Upload MCQ Quiz</h1>
            <a href="tutor_dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded <?= strpos($message, 'Error') === 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <i class="<?= strpos($message, 'Error') === 0 ? 'fas fa-exclamation-circle' : 'fas fa-check-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 font-medium">Select Course</label>
                        <select name="course_id" class="w-full border border-gray-300 p-2 rounded" required>
                            <option value="">Choose a course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars($course['course_id']) ?>">
                                    <?= htmlspecialchars($course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 font-medium">Quiz Title</label>
                        <input type="text" name="title" class="w-full border border-gray-300 p-2 rounded" required>
                    </div>
                </div>

                <div id="questionSection" class="space-y-4">
                    <h2 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-question-circle mr-2"></i>Questions
                    </h2>
                    <div class="question-block p-4 border rounded-lg bg-gray-50">
                        <input type="text" name="question[]" placeholder="Enter Question" class="w-full mb-3 p-2 border rounded" required>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input type="text" name="option_a[]" placeholder="Option A" class="w-full p-2 border rounded" required>
                            <input type="text" name="option_b[]" placeholder="Option B" class="w-full p-2 border rounded" required>
                            <input type="text" name="option_c[]" placeholder="Option C" class="w-full p-2 border rounded" required>
                            <input type="text" name="option_d[]" placeholder="Option D" class="w-full p-2 border rounded" required>
                        </div>
                        <input type="text" name="correct_option[]" placeholder="Correct Option (A/B/C/D)" 
                               class="w-full mt-3 p-2 border rounded" required 
                               pattern="[AaBbCcDd]" title="Please enter A, B, C, or D">
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <button type="button" onclick="addQuestion()" 
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-plus mr-2"></i>Add More Question
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i>Submit Quiz
                    </button>
                </div>
            </form>
        </div>

        <!-- Display Existing Quizzes -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Your Quizzes</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quiz Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Questions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($quizzes->num_rows > 0): ?>
                            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4"><?= htmlspecialchars($quiz['title']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($quiz['course_name']) ?></td>
                                    <td class="px-6 py-4"><?= $quiz['question_count'] ?></td>
                                    <td class="px-6 py-4"><?= date('M d, Y', strtotime($quiz['created_at'])) ?></td>
                                    <td class="px-6 py-4">
                                        <button class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No quizzes created yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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