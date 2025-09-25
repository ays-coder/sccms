<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Check if quiz_id is provided
if (!isset($_POST['quiz_id']) && !isset($_GET['quiz_id'])) {
    header("Location: take_quizzes.php");
    exit();
}

// Get quiz_id from POST or GET
$quiz_id = $_POST['quiz_id'] ?? $_GET['quiz_id'] ?? null;
error_log("Attempting to fetch quiz ID: " . $quiz_id);

$quiz_id = $_POST['quiz_id'] ?? $_GET['quiz_id'];

// Debug: Print the quiz_id
error_log("Quiz ID: " . $quiz_id);

// Check if quiz exists
$quiz_check = $conn->prepare("SELECT COUNT(*) as count FROM quiz_questions WHERE quiz_id = ?");
$quiz_check->bind_param("i", $quiz_id);
$quiz_check->execute();
$quiz_exists = $quiz_check->get_result()->fetch_assoc();

if ($quiz_exists['count'] == 0) {
    header("Location: take_quizzes.php?error=no_questions");
    exit();
}

// Check if student has already completed this quiz
$check_stmt = $conn->prepare("SELECT score FROM quiz_scores WHERE quiz_id = ? AND student_id = ?");
$check_stmt->bind_param("is", $quiz_id, $_SESSION['user_id']);
$check_stmt->execute();
$existing_score = $check_stmt->get_result()->fetch_assoc();

if ($existing_score) {
    header("Location: take_quizzes.php");
    exit();
}

// Get quiz details
$quiz_stmt = $conn->prepare("
    SELECT q.quiz_id, q.title, q.time_limit, c.course_name 
    FROM quiz q 
    JOIN courses c ON q.course_id = c.course_id 
    WHERE q.quiz_id = ?
");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz = $quiz_stmt->get_result()->fetch_assoc();

if (!$quiz) {
    header("Location: take_quizzes.php");
    exit();
}

// Get quiz questions
$questions_stmt = $conn->prepare("
    SELECT 
        question_id,
        quiz_id,
        question,
        option_a,
        option_b,
        option_c,
        option_d,
        correct_option
    FROM quiz_questions 
    WHERE quiz_id = ? 
    ORDER BY question_id
");

if (!$questions_stmt) {
    error_log("Failed to prepare statement: " . $conn->error);
    die("Failed to prepare statement");
}

$questions_stmt->bind_param("i", $quiz_id);
if (!$questions_stmt->execute()) {
    error_log("Failed to execute statement: " . $questions_stmt->error);
    die("Failed to execute statement");
}

$questions = $questions_stmt->get_result();

// Debug: Print the number of questions found
$num_questions = $questions->num_rows;
error_log("Number of questions found: " . $num_questions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title']) ?> | Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        // Timer functionality
        function startTimer(duration) {
            let timer = duration * 60;
            const timerDisplay = document.getElementById('timer');
            const quizForm = document.getElementById('quiz-form');
            
            const countdown = setInterval(function() {
                const minutes = Math.floor(timer / 60);
                const seconds = timer % 60;
                
                timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (--timer < 0) {
                    clearInterval(countdown);
                    quizForm.submit();
                }
            }, 1000);
        }
        
        // Auto-save functionality
        function autoSaveAnswers() {
            const form = document.getElementById('quiz-form');
            const formData = new FormData(form);
            const answers = {};
            
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('answer_')) {
                    answers[key] = value;
                }
            }
            
            localStorage.setItem('quiz_<?= $quiz_id ?>_answers', JSON.stringify(answers));
        }
        
        // Load saved answers
        function loadSavedAnswers() {
            const saved = localStorage.getItem('quiz_<?= $quiz_id ?>_answers');
            if (saved) {
                const answers = JSON.parse(saved);
                for (const [key, value] of Object.entries(answers)) {
                    const input = document.querySelector(`input[name="${key}"][value="${value}"]`);
                    if (input) input.checked = true;
                }
            }
        }
        
        // Initialize on page load
        window.onload = function() {
            startTimer(<?= $quiz['time_limit'] ?>);
            loadSavedAnswers();
            
            // Auto-save on any change
            document.querySelectorAll('input[type="radio"]').forEach(input => {
                input.addEventListener('change', autoSaveAnswers);
            });
        };
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-md px-6 py-4 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="bg-purple-100 p-2 rounded-full">
                    <i class="fas fa-question-circle text-purple-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($quiz['title']) ?></h1>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($quiz['course_name']) ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="bg-yellow-100 px-4 py-2 rounded-lg">
                    <span class="font-medium text-yellow-800">Time Remaining: </span>
                    <span id="timer" class="font-bold text-yellow-800"></span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto py-8 px-4">
        <form id="quiz-form" method="POST" action="take_quizzes.php" class="space-y-8">
            <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
            <input type="hidden" name="submit_quiz" value="1">
            
            <?php 
            $question_number = 1;
            while ($question = $questions->fetch_assoc()): 
            ?>
                <div class="bg-white rounded-lg shadow-sm p-6 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-start space-x-4">
                        <div class="bg-purple-100 px-3 py-1 rounded-full text-purple-700 font-medium">
                            Q<?= $question_number ?>
                        </div>
                        <div class="flex-grow">
                            <?php
                            // Debug: Print the question data
                            error_log("Question Data: " . print_r($question, true));
                            ?>
                            <p class="text-gray-800 font-medium mb-4">
                                <?= htmlspecialchars($question['question'] ?? 'Question not available') ?>
                            </p>
                            
                            <div class="space-y-3">
                                <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="radio" 
                                           name="answer_<?= $question['question_id'] ?>" 
                                           value="A"
                                           class="w-4 h-4 text-purple-600 focus:ring-purple-500"
                                           required>
                                    <span class="text-gray-700">
                                        <?= htmlspecialchars($question['option_a'] ?? 'Option A not available') ?>
                                    </span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="radio" 
                                           name="answer_<?= $question['question_id'] ?>" 
                                           value="B"
                                           class="w-4 h-4 text-purple-600 focus:ring-purple-500"
                                           required>
                                    <span class="text-gray-700">
                                        <?= htmlspecialchars($question['option_b'] ?? 'Option B not available') ?>
                                    </span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="radio" 
                                           name="answer_<?= $question['question_id'] ?>" 
                                           value="C"
                                           class="w-4 h-4 text-purple-600 focus:ring-purple-500"
                                           required>
                                    <span class="text-gray-700">
                                        <?= htmlspecialchars($question['option_c'] ?? 'Option C not available') ?>
                                    </span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="radio" 
                                           name="answer_<?= $question['question_id'] ?>" 
                                           value="D"
                                           class="w-4 h-4 text-purple-600 focus:ring-purple-500"
                                           required>
                                    <span class="text-gray-700">
                                        <?= htmlspecialchars($question['option_d'] ?? 'Option D not available') ?>
                                    </span>
                                </label>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
            $question_number++;
            endwhile; 
            ?>
            
            <div class="flex justify-end pt-6">
                <button type="submit"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Quiz</span>
                </button>
            </div>
        </form>
    </main>
</body>
</html>