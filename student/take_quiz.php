<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$student_id = $_SESSION['user_id'];
$retake = isset($_GET['retake']) ? (bool)$_GET['retake'] : false;


$quiz_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($quiz_id)) {
    redirect(BASE_URL . '/student');
    exit();
}

if (!$quiz_id) {
    redirect(BASE_URL . '/student');
    exit();
}


$quiz = $db->single("
    SELECT * FROM quizzes 
    WHERE id = ? AND is_published = 1
", [$quiz_id]);

if (!$quiz) {

    redirect(BASE_URL . '/student');
}


$examMode = isset($quiz['exam_mode']) ? (bool)$quiz['exam_mode'] : false;
$allowRedo = isset($quiz['allow_redo']) ? (bool)$quiz['allow_redo'] : false;


$hasAccess = false;


$classAccess = $db->single("
    SELECT 1 FROM class_quiz_assignments cqa
    JOIN class_enrollments ce ON cqa.class_id = ce.class_id
    WHERE cqa.quiz_id = ? AND ce.user_id = ?
", [$quiz_id, $student_id]);

if ($classAccess) {
    $hasAccess = true;
} else {

    $studentCount = $db->single("SELECT COUNT(*) as count FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);

    if ($studentCount && $studentCount['count'] > 0) {

        $studentAccess = $db->single("SELECT * FROM quiz_student_access WHERE quiz_id = ? AND student_id = ?", 
            [$quiz_id, $student_id]);

        if ($studentAccess) {
            $hasAccess = true;
        }
    } else {

        $classAssignments = $db->single("SELECT COUNT(*) as count FROM class_quiz_assignments WHERE quiz_id = ?", [$quiz_id]);

        if (!$classAssignments || $classAssignments['count'] == 0) {

            $hasAccess = true;
        }
    }
}

if (!$hasAccess) {
    $_SESSION['error_message'] = "You don't have access to this quiz.";
    redirect(BASE_URL . '/student');
    exit;
}


if ($retake) {

    $retakePermission = $db->single("
        SELECT * FROM quiz_retakes 
        WHERE quiz_id = ? AND student_id = ? AND used = 0
    ", [$quiz_id, $student_id]);


    $unlimitedRetakes = $db->single("
        SELECT allow_redo FROM quizzes WHERE id = ? AND allow_redo = 1
    ", [$quiz_id]);


    $log_message = "Retake attempt - Quiz: $quiz_id, Student: $student_id, " .
                   "Has permission: " . ($retakePermission ? 'Yes' : 'No') . ", " .
                   "Unlimited retakes: " . ($unlimitedRetakes ? 'Yes' : 'No');
    file_put_contents(__DIR__ . '/../debug.log', $log_message . PHP_EOL, FILE_APPEND);

    if ($retakePermission) {

        $db->query("
            UPDATE quiz_retakes 
            SET used = 1, used_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ", [$retakePermission['id']]);


        $db->query(
            "INSERT INTO quiz_attempts (quiz_id, student_id, status) VALUES (?, ?, 'in_progress')",
            [$quiz_id, $student_id]
        );


        $attempt = $db->single("
            SELECT * FROM quiz_attempts 
            WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
            ORDER BY start_time DESC LIMIT 1
        ", [$student_id, $quiz_id]);
    } elseif ($unlimitedRetakes) {

        $db->query(
            "INSERT INTO quiz_attempts (quiz_id, student_id, status) VALUES (?, ?, 'in_progress')",
            [$quiz_id, $student_id]
        );


        $attempt = $db->single("
            SELECT * FROM quiz_attempts 
            WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
            ORDER BY start_time DESC LIMIT 1
        ", [$student_id, $quiz_id]);
    } else {

        $_SESSION['error_message'] = "You don't have permission to retake this quiz. Please contact your teacher.";
        redirect(BASE_URL . '/student');
        exit;
    }
} else {

    $attempt = $db->single("
        SELECT * FROM quiz_attempts 
        WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
        ORDER BY start_time DESC LIMIT 1
    ", [$student_id, $quiz_id]);

    if (!$attempt) {

        $db->query(
            "INSERT INTO quiz_attempts (quiz_id, student_id, status) VALUES (?, ?, 'in_progress')",
            [$quiz_id, $student_id]
        );


        $attempt = $db->single("
            SELECT * FROM quiz_attempts 
            WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
            ORDER BY start_time DESC LIMIT 1
        ", [$student_id, $quiz_id]);
    }
}

$attempt_id = $attempt['id'];


$questions = $db->resultSet("
    SELECT * FROM questions 
    WHERE quiz_id = ? 
    ORDER BY id ASC
", [$quiz_id]);


$savedAnswers = $db->resultSet("
    SELECT * FROM student_answers 
    WHERE attempt_id = ? 
    ORDER BY question_id ASC
", [$attempt_id]);


file_put_contents(
    __DIR__ . '/../debug.log', 
    '[' . date('Y-m-d H:i:s') . '] Loading answers for attempt ' . $attempt_id . '. Found: ' . count($savedAnswers) . PHP_EOL, 
    FILE_APPEND
);


$answersMap = [];
foreach ($savedAnswers as $answer) {
    $answersMap[$answer['question_id']] = $answer;


    file_put_contents(
        __DIR__ . '/../debug.log', 
        '[' . date('Y-m-d H:i:s') . '] Loaded answer for question ' . $answer['question_id'] . 
        ': ' . substr($answer['answer_text'], 0, 30) . (strlen($answer['answer_text']) > 30 ? '...' : '') . PHP_EOL, 
        FILE_APPEND
    );
}


$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['answers']) && is_array($_POST['answers'])) {

        file_put_contents(
            __DIR__ . '/../debug.log', 
            '[' . date('Y-m-d H:i:s') . '] Saving answers batch for attempt: ' . $attempt_id . PHP_EOL, 
            FILE_APPEND
        );

        foreach ($_POST['answers'] as $question_id => $answer_text) {
            $question_id = (int)$question_id;
            $answer_text = trim($answer_text);

            if ($question_id <= 0) {
                continue; // Skip invalid question IDs
            }


            $existingAnswer = $db->single("
                SELECT * FROM student_answers 
                WHERE attempt_id = ? AND question_id = ?
            ", [$attempt_id, $question_id]);


            file_put_contents(
                __DIR__ . '/../debug.log', 
                '[' . date('Y-m-d H:i:s') . '] Processing answer for question ' . $question_id . 
                ', text length: ' . strlen($answer_text) . PHP_EOL, 
                FILE_APPEND
            );

            if ($existingAnswer) {

                $db->query(
                    "UPDATE student_answers SET answer_text = ? WHERE id = ?",
                    [$answer_text, $existingAnswer['id']]
                );
            } else {

                $db->query(
                    "INSERT INTO student_answers (attempt_id, question_id, answer_text) VALUES (?, ?, ?)",
                    [$attempt_id, $question_id, $answer_text]
                );
            }
        }

        $formSuccess = 'All answers saved successfully!';
    }


    if (isset($_POST['submit_quiz'])) {

        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            file_put_contents(
                __DIR__ . '/../debug.log', 
                '[' . date('Y-m-d H:i:s') . '] Auto-saving all answers before submission for attempt: ' . $attempt_id . PHP_EOL, 
                FILE_APPEND
            );

            foreach ($_POST['answers'] as $question_id => $answer_text) {
                $question_id = (int)$question_id;
                $answer_text = trim($answer_text);

                if ($question_id <= 0) {
                    continue; // Skip invalid question IDs
                }


                $existingAnswer = $db->single("
                    SELECT * FROM student_answers 
                    WHERE attempt_id = ? AND question_id = ?
                ", [$attempt_id, $question_id]);

                if ($existingAnswer) {

                    $db->query(
                        "UPDATE student_answers SET answer_text = ? WHERE id = ?",
                        [$answer_text, $existingAnswer['id']]
                    );
                } else {

                    $db->query(
                        "INSERT INTO student_answers (attempt_id, question_id, answer_text) VALUES (?, ?, ?)",
                        [$attempt_id, $question_id, $answer_text]
                    );
                }
            }
        }


        $savedAnswers = $db->resultSet("
            SELECT * FROM student_answers 
            WHERE attempt_id = ? 
            ORDER BY question_id ASC
        ", [$attempt_id]);


        $answersMap = [];
        foreach ($savedAnswers as $answer) {
            $answersMap[$answer['question_id']] = $answer;
        }


        $unansweredCount = 0;
        $unansweredQuestions = [];

        foreach ($questions as $index => $question) {
            $questionNum = $index + 1;
            if (!isset($answersMap[$question['id']]) || 
                !isset($answersMap[$question['id']]['answer_text']) || 
                trim($answersMap[$question['id']]['answer_text']) === '') {
                $unansweredCount++;
                $unansweredQuestions[] = $questionNum;
            }
        }

        if ($unansweredCount > 0) {
            $unansweredList = implode(', ', $unansweredQuestions);
            $formError = "You have $unansweredCount unanswered question(s): Questions $unansweredList. Please answer all questions before submitting.";


            $debug_message = "Submission validation failed. Quiz ID: $quiz_id, Attempt ID: $attempt_id, Unanswered: $unansweredCount";
            file_put_contents(__DIR__ . '/../debug.log', $debug_message . PHP_EOL, FILE_APPEND);
        } else {

            require_once __DIR__ . '/../lib/ai/DeepSeek.php';
require_once __DIR__ . '/../lib/ai/ai_prompts.php';

function gradeAnswer($studentAnswer, $modelAnswer) {
    $ai = create_ai();
    $content = check_answer('General', '', $modelAnswer, $studentAnswer);
    $response = $ai->send_message($content);
    $result = json_decode($response, true);

    if (isset($result['error'])) {
        return ['score' => 0, 'feedback' => 'Error grading answer: ' . $result['error']];
    }


    if (preg_match('/```json\s*({[\s\S]*})\s*```/', $result['content'], $matches)) {
        $content = $matches[1];
    } else {

        $content = preg_replace('/^[^{]*({.*})[^}]*$/s', '$1', $result['content']);
    }

    $grading = json_decode(trim($content), true);
    if (!$grading) {
        return ['score' => 0, 'feedback' => 'Error parsing AI response: ' . json_last_error_msg()];
    }

    return [
        'score' => $grading['score'] ?? 0,
        'feedback' => $grading['feedback'] ?? 'No feedback available',
        'key_points_addressed' => $grading['key_points_addressed'] ?? [],
        'key_points_missed' => $grading['key_points_missed'] ?? [],
        'improvement_suggestions' => $grading['improvement_suggestions'] ?? ''
    ];
}


            $totalPoints = 0;
            $maxPoints = 0;


            foreach ($questions as $question) {
                $maxPoints += $question['points'];

                if (isset($answersMap[$question['id']])) {
                    $answer = $answersMap[$question['id']];
                    $studentAnswer = $answer['answer_text'];
                    $modelAnswer = $question['model_answer'];


                    $aiResult = gradeAnswer($studentAnswer, $modelAnswer);


                    $score = round(($aiResult['score'] / 100) * $question['points'], 1);
                    $totalPoints += $score;


                    $db->query(
                        "UPDATE student_answers SET score = ?, feedback = ? WHERE id = ?",
                        [$score, $aiResult['feedback'], $answer['id']]
                    );
                }
            }


            $percentageScore = round(($totalPoints / $maxPoints) * 100);


            $db->query(
                "UPDATE quiz_attempts SET status = 'completed', end_time = CURRENT_TIMESTAMP, total_score = ? WHERE id = ?",
                [$percentageScore, $attempt_id]
            );


            redirect(BASE_URL . '/student/view_result.php?attempt_id=' . $attempt_id);
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .answer-status-message {
        padding: 5px 10px;
        border-radius: 4px;
        margin-top: 5px;
        display: inline-block;
    }

    .answer-textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        transition: border-color 0.3s;
    }

    .answer-textarea:focus {
        border-color: #4a89dc;
        box-shadow: 0 0 0 0.2rem rgba(74, 137, 220, 0.25);
    }

    .quiz-progress {
        margin-bottom: 20px;
    }

    .progress-label {
        margin-bottom: 5px;
        font-weight: 500;
    }

    .progress-bar {
        height: 8px;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background-color: #4a89dc;
        transition: width 0.3s ease;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s;
    }

    .loading-overlay.active {
        opacity: 1;
        pointer-events: all;
    }
    
    .loading-container {
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-align: center;
    }
    
    .loading-spinner {
        width: 70px;
        height: 70px;
        border: 8px solid #f3f3f3;
        border-top: 8px solid #4a89dc;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .loading-message {
        font-size: 1.5rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
    }
    
    .loading-description {
        font-size: 1.1rem;
        color: #666;
        margin-bottom: 10px;
    }

    .real-time-save-indicator {
        font-size: 14px;
        color: #4a89dc;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        background-color: rgba(255, 255, 255, 0.8);
        border-radius: 50%;
        box-shadow: 0 0 3px rgba(0, 0, 0, 0.1);
    }
    
    
    .exam-mode-alert {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .exam-mode-alert i {
        margin-right: 8px;
    }
    
    .exam-mode-alert .alert-content {
        flex: 1;
    }
    
    .exam-mode-alert .alert-title {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .exam-mode-warning {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(220, 53, 69, 0.9);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        text-align: center;
        padding: 20px;
    }
    
    .exam-mode-warning-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        color: white;
    }
    
    .exam-mode-warning-title {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 15px;
    }
    
    .exam-mode-warning-description {
        font-size: 1.2rem;
        max-width: 600px;
        margin-bottom: 30px;
    }
    
    .exam-mode-warning-button {
        background-color: white;
        color: #dc3545;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .exam-mode-warning-button:hover {
        background-color: #f8f9fa;
        transform: scale(1.05);
    }
</style>

<div class="quiz-taker">
    <div class="quiz-taker-header">
        <h2><?= htmlspecialchars($quiz['title']) ?></h2>
        <div class="quiz-actions">
            <a href="<?= BASE_URL ?>/student" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($formError)): ?>
        <div class="alert alert-danger"><?= $formError ?></div>
    <?php endif; ?>

    <?php if (!empty($formSuccess)): ?>
        <div class="alert alert-success"><?= $formSuccess ?></div>
    <?php endif; ?>
    
    <?php if ($examMode): ?>
        <div class="exam-mode-alert">
            <div class="alert-content">
                <div class="alert-title"><i class="fas fa-user-shield"></i> Exam Mode Enabled</div>
                <div class="alert-text">
                    This quiz is in exam mode. Do not leave this page or refresh the browser. 
                    If you attempt to navigate away, your teacher will be notified and your attempt may be invalidated.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($quiz['description']): ?>
        <div class="quiz-description">
            <p><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="quiz-progress">
        <div class="progress-label">Progress: <span id="progress-count">0</span>/<span id="total-questions"><?= count($questions) ?></span> questions answered</div>
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
        </div>
    </div>

    <div class="questions-container">
        <?php if (count($questions) > 0): ?>
            <form method="POST" action="" id="quiz-form">
                <?php foreach ($questions as $index => $question): ?>
                    <?php 
                    $hasAnswer = isset($answersMap[$question['id']]) && !empty($answersMap[$question['id']]['answer_text']);
                    $answerText = $hasAnswer ? $answersMap[$question['id']]['answer_text'] : '';
                    ?>
                    <div class="question-card <?= $hasAnswer ? 'answered' : '' ?>" data-question-id="<?= $question['id'] ?>">
                        <div class="question-header">
                            <h3>Question <?= $index + 1 ?> <small>(<?= $question['points'] ?> points)</small></h3>
                            <div class="question-status">
                                <?php if ($hasAnswer): ?>
                                    <span class="status-badge answered">
                                        <i class="fas fa-check-circle"></i> Answered
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge unanswered">
                                        <i class="fas fa-exclamation-circle"></i> Not answered
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="question-text">
                            <p><?= nl2br(htmlspecialchars($question['question_text'])) ?></p>
                        </div>

                        <div class="form-group">
                            <label for="answer_<?= $question['id'] ?>">Your Answer:</label>
                            <textarea 
                                id="answer_<?= $question['id'] ?>" 
                                name="answers[<?= $question['id'] ?>]" 
                                rows="5" 
                                class="answer-textarea"
                                data-question-id="<?= $question['id'] ?>"
                            ><?= htmlspecialchars($answerText) ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="submit-quiz-container">
                    <div class="mb-3 text-center">
                        <button type="submit" name="submit_quiz" class="btn btn-lg btn-success" id="submit-quiz-btn">
                            <i class="fas fa-paper-plane"></i> Submit Quiz
                        </button>
                    </div>
                    <p class="submit-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Warning: Once submitted, you cannot modify your answers. Make sure all questions are answered.
                    </p>
                </div>
            </form>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-question-circle"></i>
                <p>No questions available for this quiz.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Loading overlay for AI grading -->
<div id="loading-overlay" class="loading-overlay">
    <div class="loading-container">
        <div class="loading-spinner"></div>
        <div class="loading-message">Please wait</div>
        <div class="loading-description">Your quiz is being graded by AI. This may take a moment...</div>
    </div>
</div>

<!-- Exam mode warning overlay -->
<?php if ($examMode): ?>
<div id="exam-mode-warning" class="exam-mode-warning" style="display: none;">
    <i class="fas fa-exclamation-triangle exam-mode-warning-icon"></i>
    <div class="exam-mode-warning-title">Warning! Exam Exit Detected</div>
    <div class="exam-mode-warning-description">
        You are attempting to leave the exam. This action has been logged and your teacher will be notified.
        Please return to the exam immediately. Multiple exit attempts may result in your exam being invalidated.
    </div>
    <button id="return-to-exam-btn" class="exam-mode-warning-button">
        Return to Exam
    </button>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    <?php if ($examMode): ?>
    const examModeWarning = document.getElementById('exam-mode-warning');
    const returnToExamBtn = document.getElementById('return-to-exam-btn');
    

    let exitAttempts = 0;
    

    function logExamExitAttempt() {
        const attemptId = <?= json_encode($attempt_id) ?>;
        const studentId = <?= json_encode($student_id) ?>;
        const quizId = <?= json_encode($quiz_id) ?>;
        

        const formData = new FormData();
        formData.append('action', 'log_exam_exit');
        formData.append('attempt_id', attemptId);
        formData.append('student_id', studentId);
        formData.append('quiz_id', quizId);
        

        fetch('<?= BASE_URL ?>/ajax/log_exam_exit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Exit attempt logged:', data);
        })
        .catch(error => {
            console.error('Error logging exit attempt:', error);
        });
        
        exitAttempts++;
    }
    

    window.addEventListener('beforeunload', function(e) {

        logExamExitAttempt();
        

        e.preventDefault();
        

        e.returnValue = 'Are you sure you want to leave? This action will be reported to your teacher.';
        

        return 'Are you sure you want to leave? This action will be reported to your teacher.';
    });
    

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {

            logExamExitAttempt();
            

            document.addEventListener('visibilitychange', function showWarningOnReturn() {
                if (document.visibilityState === 'visible') {
                    examModeWarning.style.display = 'flex';

                    document.removeEventListener('visibilitychange', showWarningOnReturn);
                }
            });
        }
    });
    

    if (returnToExamBtn) {
        returnToExamBtn.addEventListener('click', function() {
            examModeWarning.style.display = 'none';
        });
    }
    

    window.addEventListener('blur', function() {
        logExamExitAttempt();
    });
    <?php endif; ?>

    function updateProgress() {
        const totalQuestions = <?= count($questions) ?>;
        const answeredQuestions = document.querySelectorAll('.question-card.answered').length;

        document.getElementById('progress-count').textContent = answeredQuestions;
        document.getElementById('total-questions').textContent = totalQuestions;

        const progressPercentage = totalQuestions > 0 ? (answeredQuestions / totalQuestions) * 100 : 0;
        document.getElementById('progress-fill').style.width = progressPercentage + '%';
    }


    function markAsAnswered(questionId) {
        const questionCard = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
        if (questionCard) {
            questionCard.classList.add('answered');
            const statusBadge = questionCard.querySelector('.question-status .status-badge');
            statusBadge.className = 'status-badge answered';
            statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Answered';
            updateProgress();
        }
    }


    function markAsUnanswered(questionId) {
        const questionCard = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
        if (questionCard) {
            questionCard.classList.remove('answered');
            const statusBadge = questionCard.querySelector('.question-status .status-badge');
            statusBadge.className = 'status-badge unanswered';
            statusBadge.innerHTML = '<i class="fas fa-exclamation-circle"></i> Not answered';
            updateProgress();
        }
    }


    const localStorageKeyPrefix = 'quizlab_answer_';
    const attemptId = <?= json_encode($attempt_id) ?>;


    function saveAnswerToLocalStorage(questionId, text) {
        const key = `${localStorageKeyPrefix}${attemptId}_${questionId}`;
        localStorage.setItem(key, text);
    }


    function getAnswerFromLocalStorage(questionId) {
        const key = `${localStorageKeyPrefix}${attemptId}_${questionId}`;
        return localStorage.getItem(key);
    }


    function clearLocalStorageForAttempt() {
        Object.keys(localStorage).forEach(key => {
            if (key.startsWith(`${localStorageKeyPrefix}${attemptId}_`)) {
                localStorage.removeItem(key);
            }
        });
    }


    function loadAnswersFromLocalStorage() {
        answerTextareas.forEach(textarea => {
            const questionId = textarea.getAttribute('data-question-id');
            const savedAnswer = getAnswerFromLocalStorage(questionId);



            if (savedAnswer && textarea.value.trim() === '') {
                textarea.value = savedAnswer;
                console.log(`Restored answer for question ${questionId} from local storage`);
                if (savedAnswer.trim()) {
                    markAsAnswered(questionId);
                }
            } else if (textarea.value.trim()) {

                console.log(`Using server-loaded answer for question ${questionId}`);
            }
        });
        updateProgress();
    }

    const answerTextareas = document.querySelectorAll('.answer-textarea');


    answerTextareas.forEach(textarea => {
        const questionId = textarea.getAttribute('data-question-id');


        if (textarea.value.trim()) {
            markAsAnswered(questionId);
        }


        textarea.addEventListener('input', function() {
            const questionId = this.getAttribute('data-question-id');
            const answerText = this.value;


            saveAnswerToLocalStorage(questionId, answerText);


            if (answerText.trim()) {
                markAsAnswered(questionId);
            } else {
                markAsUnanswered(questionId);
            }
        });
    });


    loadAnswersFromLocalStorage();


    const quizForm = document.getElementById('quiz-form');
    if (quizForm) {
        const submitBtn = document.getElementById('submit-quiz-btn');

        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                const totalQuestions = <?= count($questions) ?>;


                answerTextareas.forEach(textarea => {
                    const questionId = textarea.getAttribute('data-question-id');
                    if (textarea.value.trim()) {
                        markAsAnswered(questionId);
                    } else {
                        markAsUnanswered(questionId);
                    }
                });


                const answeredQuestions = document.querySelectorAll(".question-card.answered").length;
                console.log(`Checking submission: ${answeredQuestions} of ${totalQuestions} questions answered`);

                if (answeredQuestions < totalQuestions) {
                    e.preventDefault();


                    const unansweredQuestions = [];
                    document.querySelectorAll(".question-card:not(.answered)").forEach(card => {
                        const questionNum = parseInt(card.querySelector('.question-header h3').textContent.match(/Question (\d+)/)[1]);
                        unansweredQuestions.push(questionNum);
                    });

                    alert(`You have ${totalQuestions - answeredQuestions} unanswered question(s): Questions ${unansweredQuestions.join(', ')}. Please answer all questions before submitting.`);
                    return false;
                }

                if (!confirm("Are you sure you want to submit this quiz? You will not be able to change your answers after submission.")) {
                    e.preventDefault();
                    return false;
                }


                const loadingOverlay = document.getElementById("loading-overlay");
                loadingOverlay.classList.add("active");
            });
        }
    }


    updateProgress();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>