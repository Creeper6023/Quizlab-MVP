<?php
require_once __DIR__ . '/../config.php';

// Check if user is a student
if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$student_id = $_SESSION['user_id'];
$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

// Verify the attempt exists and belongs to this student
$attempt = $db->single("
    SELECT qa.*, q.title as quiz_title, q.description as quiz_description
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.id = ? AND qa.student_id = ?
", [$attempt_id, $student_id]);

if (!$attempt) {
    // Attempt doesn't exist or doesn't belong to this student
    redirect(BASE_URL . '/student');
}

// Get all questions and answers for this attempt
$questionAnswers = $db->resultSet("
    SELECT q.id as question_id, q.question_text, q.model_answer, q.points as max_points,
           a.id as answer_id, a.answer_text, a.score, a.feedback
    FROM questions q
    LEFT JOIN student_answers a ON q.id = a.question_id AND a.attempt_id = ?
    WHERE q.quiz_id = ?
    ORDER BY q.id ASC
", [$attempt_id, $attempt['quiz_id']]);

include_once INCLUDES_PATH . '/header.php';
?>

<div class="quiz-results student-view">
    <div class="quiz-results-header">
        <h2>Quiz Results: <?= htmlspecialchars($attempt['quiz_title']) ?></h2>
        <div class="quiz-actions">
            <a href="<?= BASE_URL ?>/student" class="btn btn-sm btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="result-summary">
        <div class="result-card">
            <div class="score-display">
                <div class="score-circle">
                    <span class="score-value"><?= $attempt['total_score'] ?>%</span>
                </div>
                <div class="score-label">Your Score</div>
            </div>

            <div class="result-details">
                <div class="detail-item">
                    <span class="label">Quiz:</span>
                    <span class="value"><?= htmlspecialchars($attempt['quiz_title']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Started:</span>
                    <span class="value"><?= date('M j, Y g:i a', strtotime($attempt['start_time'] ?? '')) ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Completed:</span>
                    <span class="value"><?= date('M j, Y g:i a', strtotime($attempt['end_time'] ?? '')) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="answers-review">
        <h3>Question Review</h3>

        <?php if (count($questionAnswers) > 0): ?>
            <?php foreach ($questionAnswers as $index => $qa): ?>
                <div class="answer-card">
                    <div class="answer-header">
                        <h4>Question <?= $index + 1 ?></h4>
                        <div class="answer-score">
                            Score: <span class="score-value"><?= $qa['score'] ?? 'undefined' ?> / <?= $qa['max_points'] ?></span>
                        </div>
                    </div>

                    <div class="question-text">
                        <p><?= nl2br(htmlspecialchars($qa['question_text'])) ?></p>
                    </div>

                    <div class="answer-section">
                        <div class="student-answer">
                            <h5>Your Answer:</h5>
                            <p><?= nl2br(htmlspecialchars($qa['answer_text'] ?? '')) ?></p>
                        </div>

                        <!-- Model answers hidden -->

                    </div>

                    <?php if (!empty($qa['feedback'])): ?>
                        <div class="feedback-section">
                            <h5>AI Feedback:</h5>
                            <p><?= nl2br(htmlspecialchars($qa['feedback'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <p>No questions or answers found for this attempt.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>