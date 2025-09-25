<?php
session_start();
require_once 'db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Get quiz_id from URL
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if (!$quiz_id) {
    header("Location: take_quizzes.php");
    exit();
}

// Fetch quiz details and student's score
$quiz_sql = "
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
    WHERE q.quiz_id = ? AND qs.student_id = ?
";

$stmt = $conn->prepare($quiz_sql);
$stmt->bind_param("ii", $quiz_id, $_SESSION['user_id']);
$stmt->execute();
$quiz_result = $stmt->get_result()->fetch_assoc();

if (!$quiz_result) {
    header("Location: take_quizzes.php");
    exit();
}

// Fetch student's answers and correct answers
$answers_sql = "
    SELECT 
        qq.question,
        qq.option_a,
        qq.option_b,
        qq.option_c,
        qq.option_d,
        qq.correct_option,
        qa.selected_option,
        qa.is_correct
    FROM quiz_questions qq
    LEFT JOIN quiz_attempts qa ON qq.question_id = qa.question_id 
        AND qa.student_id = ?
    WHERE qq.quiz_id = ?
    ORDER BY qq.question_id ASC
";

$stmt = $conn->prepare($answers_sql);
$stmt->bind_param("ii", $_SESSION['user_id'], $quiz_id);
$stmt->execute();
$answers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Results | Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-md px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="bg-purple-100 p-2 rounded-full">
                    <i class="fas fa-clipboard-check text-purple-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Quiz Results</h1>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($quiz_result['title']) ?></p>
                </div>
            </div>
            <a href="take_quizzes.php" 
               class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition duration-150">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Quizzes</span>
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-8 px-4">
        <!-- Quiz Summary -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600">Course</div>
                    <div class="font-semibold text-gray-800"><?= htmlspecialchars($quiz_result['course_name']) ?></div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600">Score</div>
                    <div class="font-semibold text-gray-800"><?= $quiz_result['score'] ?> / <?= $quiz_result['total_questions'] ?></div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600">Percentage</div>
                    <div class="font-semibold text-gray-800"><?= round(($quiz_result['score'] / $quiz_result['total_questions']) * 100, 1) ?>%</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600">Completion Date</div>
                    <div class="font-semibold text-gray-800"><?= date('M d, Y H:i', strtotime($quiz_result['completion_date'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Questions and Answers -->
        <div class="space-y-6">
            <?php 
            $question_number = 1;
            while ($answer = $answers->fetch_assoc()): 
            ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center <?= $answer['is_correct'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $question_number ?>
                            </div>
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-lg font-medium text-gray-900 mb-4"><?= htmlspecialchars($answer['question']) ?></h3>
                            
                            <?php 
                            $options = [
                                'A' => $answer['option_a'],
                                'B' => $answer['option_b'],
                                'C' => $answer['option_c'],
                                'D' => $answer['option_d']
                            ];
                            
                            foreach ($options as $key => $option): 
                                $is_selected = $answer['selected_option'] === $key;
                                $is_correct = $answer['correct_option'] === $key;
                                $bg_color = $is_selected ? ($is_correct ? 'bg-green-50' : 'bg-red-50') : 'bg-gray-50';
                                $text_color = $is_selected ? ($is_correct ? 'text-green-700' : 'text-red-700') : 'text-gray-700';
                            ?>
                                <div class="mb-2">
                                    <div class="flex items-center space-x-3 p-3 rounded-lg <?= $bg_color ?>">
                                        <span class="font-medium <?= $text_color ?>"><?= $key ?>.</span>
                                        <span class="<?= $text_color ?>"><?= htmlspecialchars($option) ?></span>
                                        <?php if ($is_selected): ?>
                                            <i class="fas fa-check ml-auto <?= $is_correct ? 'text-green-500' : 'text-red-500' ?>"></i>
                                        <?php elseif ($is_correct): ?>
                                            <i class="fas fa-check ml-auto text-green-500"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php 
            $question_number++;
            endwhile; 
            ?>
        </div>
    </main>
</body>
</html>