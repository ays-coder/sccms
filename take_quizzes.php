<?php
session_start();
require_once 'db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle quiz submission
if (isset($_POST['submit_quiz'])) {
    $quiz_id = $_POST['quiz_id'];
    $total_marks = 0;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get all questions for this quiz
        $stmt = $conn->prepare("SELECT question_id, correct_option FROM quiz_questions WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $questions = $stmt->get_result();
        
        while ($question = $questions->fetch_assoc()) {
            $question_id = $question['question_id'];
            $selected_option = $_POST['answer_' . $question_id] ?? '';
            
            // Check if answer is correct
            if ($selected_option === $question['correct_option']) {
                $total_marks += 1;
            }
            
            // Save student's answer
            $answer_stmt = $conn->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?, ?)");
            $is_correct = ($selected_option === $question['correct_option']) ? 1 : 0;
            $answer_stmt->bind_param("iissi", $quiz_id, $_SESSION['user_id'], $question_id, $selected_option, $is_correct);
            $answer_stmt->execute();
        }
        
        // Save total score
        $score_stmt = $conn->prepare("INSERT INTO quiz_scores (quiz_id, student_id, score, completion_date) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $score_stmt->bind_param("iis", $quiz_id, $_SESSION['user_id'], $total_marks);
        $score_stmt->execute();
        
        $conn->commit();
        $message = "Quiz submitted successfully! Your score: $total_marks marks";
        $message_type = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error submitting quiz. Please try again.";
        $message_type = "error";
    }
}

// Fetch available quizzes with completion status
$sql = "
    SELECT 
        q.quiz_id,
        q.title,
        c.course_name,
        qs.score,
        qs.completion_date,
        (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as total_questions
    FROM quiz q
    JOIN courses c ON q.course_id = c.course_id
    LEFT JOIN quiz_scores qs ON q.quiz_id = qs.quiz_id 
        AND qs.student_id = ?
    ORDER BY q.quiz_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$quizzes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Quizzes | Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-md px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="bg-purple-100 p-2 rounded-full">
                    <i class="fas fa-question-circle text-purple-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Take Quizzes</h1>
                    <p class="text-sm text-gray-600">Test your knowledge</p>
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
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
                <div class="flex items-center">
                    <i class="fas <?= $message_type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?> mr-2"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-6">
            <?php if ($quizzes && $quizzes->num_rows > 0): ?>
                <?php while ($quiz = $quizzes->fetch_assoc()): 
                    $is_completed = !empty($quiz['completion_date']);
                ?>
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?= htmlspecialchars($quiz['title']) ?>
                                </h3>
                                <div class="flex items-center space-x-2">
                                    <?php if ($is_completed): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            Completed - Score: <?= $quiz['score'] ?>/<?= $quiz['total_questions'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            Not Attempted
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-3 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-book-open w-5"></i>
                                    <span><?= htmlspecialchars($quiz['course_name']) ?></span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-tasks w-5"></i>
                                    <span>Total Questions: <?= $quiz['total_questions'] ?></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <?php if (!$is_completed): ?>
                                    <form method="POST" action="attempt_quiz.php">
                                        <input type="hidden" name="quiz_id" value="<?= $quiz['quiz_id'] ?>">
                                        <button type="submit" 
                                                name="start_quiz"
                                                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                                            <i class="fas fa-play-circle"></i>
                                            <span>Start Quiz</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm text-gray-600">
                                            Completed on: <?= date('M d, Y H:i', strtotime($quiz['completion_date'])) ?>
                                        </span>
                                        <a href="view_quiz_results.php?quiz_id=<?= $quiz['quiz_id'] ?>" 
                                           class="text-purple-600 hover:text-purple-700 font-medium">
                                            View Results
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                        <i class="fas fa-question-circle text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Quizzes Available</h3>
                    <p class="text-gray-500">There are no quizzes available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>