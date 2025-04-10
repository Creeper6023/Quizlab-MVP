<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';

// Check if user is a student
if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the quiz exists and is published
$quiz = $db->single("
    SELECT * FROM quizzes 
    WHERE id = ? AND is_published = 1
", [$quiz_id]);

if (!$quiz) {
    // Quiz doesn't exist or isn't published
    redirect(BASE_URL . '/student');
}

// Check if there's an existing attempt for this student
$attempt = $db->single("
    SELECT * FROM quiz_attempts 
    WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
    ORDER BY start_time DESC LIMIT 1
", [$student_id, $quiz_id]);

if (!$attempt) {
    // Create a new attempt
    $db->query(
        "INSERT INTO quiz_attempts (quiz_id, student_id, status) VALUES (?, ?, 'in_progress')",
        [$quiz_id, $student_id]
    );
    
    // Get the new attempt
    $attempt = $db->single("
        SELECT * FROM quiz_attempts 
        WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
        ORDER BY start_time DESC LIMIT 1
    ", [$student_id, $quiz_id]);
}

$attempt_id = $attempt['id'];

// Get all questions for this quiz
$questions = $db->resultSet("
    SELECT * FROM questions 
    WHERE quiz_id = ? 
    ORDER BY id ASC
", [$quiz_id]);

// Get answers that have already been saved
$savedAnswers = $db->resultSet("
    SELECT * FROM student_answers 
    WHERE attempt_id = ? 
    ORDER BY question_id ASC
", [$attempt_id]);

// Convert to a map for easy lookup
$answersMap = [];
foreach ($savedAnswers as $answer) {
    $answersMap[$answer['question_id']] = $answer;
}

// Handle form submissions
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_answer'])) {
        $question_id = (int)$_POST['question_id'];
        $answer_text = trim($_POST['answer_text'] ?? '');
        
        // Check if there's an existing answer for this question
        $existingAnswer = $db->single("
            SELECT * FROM student_answers 
            WHERE attempt_id = ? AND question_id = ?
        ", [$attempt_id, $question_id]);
        
        if ($existingAnswer) {
            // Update existing answer
            $db->query(
                "UPDATE student_answers SET answer_text = ? WHERE id = ?",
                [$answer_text, $existingAnswer['id']]
            );
        } else {
            // Insert new answer
            $db->query(
                "INSERT INTO student_answers (attempt_id, question_id, answer_text) VALUES (?, ?, ?)",
                [$attempt_id, $question_id, $answer_text]
            );
        }
        
        $formSuccess = 'Answer saved successfully!';
        
        // Refresh the answers
        $savedAnswers = $db->resultSet("
            SELECT * FROM student_answers 
            WHERE attempt_id = ? 
            ORDER BY question_id ASC
        ", [$attempt_id]);
        
        // Update the map
        $answersMap = [];
        foreach ($savedAnswers as $answer) {
            $answersMap[$answer['question_id']] = $answer;
        }
    } elseif (isset($_POST['submit_quiz'])) {
        // Check if all questions have answers
        $unansweredCount = 0;
        foreach ($questions as $question) {
            if (!isset($answersMap[$question['id']]) || empty($answersMap[$question['id']]['answer_text'])) {
                $unansweredCount++;
            }
        }
        
        if ($unansweredCount > 0) {
            $formError = "You have $unansweredCount unanswered question(s). Please answer all questions before submitting.";
        } else {
            // Process all answers and get scores
            require_once __DIR__ . '/../deepseek.php';
            
            // Initialize total points
            $totalPoints = 0;
            $maxPoints = 0;
            
            // Process each answer
            foreach ($questions as $question) {
                $maxPoints += $question['points'];
                
                if (isset($answersMap[$question['id']])) {
                    $answer = $answersMap[$question['id']];
                    $studentAnswer = $answer['answer_text'];
                    $modelAnswer = $question['model_answer'];
                    
                    // Use AI to grade the answer
                    $aiResult = gradeAnswer($studentAnswer, $modelAnswer);
                    
                    // Calculate score based on percentage * max points
                    $score = round(($aiResult['score'] / 100) * $question['points'], 1);
                    $totalPoints += $score;
                    
                    // Update the answer with score and feedback
                    $db->query(
                        "UPDATE student_answers SET score = ?, feedback = ? WHERE id = ?",
                        [$score, $aiResult['feedback'], $answer['id']]
                    );
                }
            }
            
            // Calculate overall percentage
            $percentageScore = round(($totalPoints / $maxPoints) * 100);
            
            // Mark the attempt as completed
            $db->query(
                "UPDATE quiz_attempts SET status = 'completed', end_time = CURRENT_TIMESTAMP, total_score = ? WHERE id = ?",
                [$percentageScore, $attempt_id]
            );
            
            // Redirect to the results page
            redirect(BASE_URL . '/student/view_result.php?attempt_id=' . $attempt_id);
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="quiz-taker">
    <div class="quiz-taker-header">
        <h2><?= htmlspecialchars($quiz['title']) ?></h2>
        <div class="quiz-actions">
            <a href="<?= BASE_URL ?>/student" class="btn btn-sm btn-outline">
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
                    
                    <form method="POST" action="" class="answer-form">
                        <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                        
                        <div class="form-group">
                            <label for="answer_<?= $question['id'] ?>">Your Answer:</label>
                            <textarea 
                                id="answer_<?= $question['id'] ?>" 
                                name="answer_text" 
                                rows="5" 
                                class="answer-textarea"
                                data-question-id="<?= $question['id'] ?>"
                            ><?= htmlspecialchars($answerText) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="save_answer" class="btn btn-primary save-answer-btn">
                                <i class="fas fa-save"></i> Save Answer
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
            
            <div class="submit-quiz-container">
                <form method="POST" action="" id="submit-quiz-form">
                    <button type="submit" name="submit_quiz" class="btn btn-lg btn-success" id="submit-quiz-btn">
                        <i class="fas fa-paper-plane"></i> Submit Quiz
                    </button>
                </form>
                <p class="submit-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Warning: Once submitted, you cannot modify your answers. Make sure all questions are answered.
                </p>
            </div>
            
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
    <div class="loading-spinner"></div>
    <div class="loading-message">AI is grading your answers...</div>
    <div class="loading-progress">
        <div class="loading-progress-bar"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update progress bar
    function updateProgress() {
        const totalQuestions = <?= count($questions) ?>;
        const answeredQuestions = document.querySelectorAll('.question-card.answered').length;
        
        document.getElementById('progress-count').textContent = answeredQuestions;
        document.getElementById('total-questions').textContent = totalQuestions;
        
        const progressPercentage = totalQuestions > 0 ? (answeredQuestions / totalQuestions) * 100 : 0;
        document.getElementById('progress-fill').style.width = progressPercentage + '%';
    }
    
    // Mark a question as answered
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
    
    // Mark a question as unanswered
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
    
    // Track answer status when typing
    const answerTextareas = document.querySelectorAll('.answer-textarea');
    answerTextareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            const questionId = this.getAttribute('data-question-id');
            if (this.value.trim()) {
                markAsAnswered(questionId);
            } else {
                markAsUnanswered(questionId);
            }
        });
    });
    
    // Confirm submission
    const submitForm = document.getElementById('submit-quiz-form');
    // Show loading screen when submitting quiz
    const submitForm = document.getElementById("submit-quiz-form");
    if (submitForm) {
        submitForm.addEventListener("submit", function(e) {
            const totalQuestions = <?= count($questions) ?>;
            const answeredQuestions = document.querySelectorAll(".question-card.answered").length;
            
            if (answeredQuestions < totalQuestions) {
                e.preventDefault();
                alert(`You have ${totalQuestions - answeredQuestions} unanswered question(s). Please answer all questions before submitting.`);
                return false;
            }
            
            if (!confirm("Are you sure you want to submit this quiz? You will not be able to change your answers after submission.")) {
                e.preventDefault();
                return false;
            }
            
            // Show loading overlay
            const loadingOverlay = document.getElementById("loading-overlay");
            loadingOverlay.classList.add("active");
            
            // Submit after a short delay to allow loading screen to display
            setTimeout(() => {
                return true;
            }, 100);
        });
    }
                return false;
            }
            
            if (!confirm('Are you sure you want to submit this quiz? You will not be able to change your answers after submission.')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
    
    // Initialize progress
    updateProgress();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
