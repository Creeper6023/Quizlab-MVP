<?php
require_once __DIR__ . '/../config.php';


if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$student_id = $_SESSION['user_id'];
$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;


$attempt = $db->single("
    SELECT qa.*, q.title as quiz_title, q.description as quiz_description
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.id = ? AND qa.student_id = ?
", [$attempt_id, $student_id]);

if (!$attempt) {

    redirect(BASE_URL . '/student');
}


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
            <?php $quiz_hash_id = getHashFromId('quizzes', $attempt['quiz_id']); ?>
            <a href="<?= BASE_URL ?>/student/take_quiz.php?id=<?= $quiz_hash_id ?>&retake=1" class="btn-retake me-2">
                <i class="fas fa-redo"></i> Retake Quiz
            </a>
            <a href="<?= BASE_URL ?>/student" class="btn btn-sm btn-outline-primary">
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
                            <?php 
                                $feedback = json_decode($qa['feedback'], true);
                                if ($feedback && is_array($feedback)): 
                            ?>
                                <div class="score-section mb-3">
                                    <span class="badge bg-<?= $feedback['score'] >= 70 ? 'success' : ($feedback['score'] >= 40 ? 'warning' : 'danger') ?>">
                                        Score: <?= $feedback['score'] ?>%
                                    </span>
                                </div>

                                <?php if (!empty($feedback['feedback'])): ?>
                                    <div class="mb-3">
                                        <strong>Feedback:</strong>
                                        <p><?= nl2br(htmlspecialchars($feedback['feedback'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($feedback['key_points_addressed'])): ?>
                                    <div class="mb-3">
                                        <strong>Key Points Addressed:</strong>
                                        <ul class="list-group">
                                            <?php foreach ($feedback['key_points_addressed'] as $point): ?>
                                                <li class="list-group-item list-group-item-success">
                                                    <i class="fas fa-check me-2"></i><?= htmlspecialchars($point) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($feedback['key_points_missed'])): ?>
                                    <div class="mb-3">
                                        <strong>Areas for Improvement:</strong>
                                        <ul class="list-group">
                                            <?php foreach ($feedback['key_points_missed'] as $point): ?>
                                                <li class="list-group-item list-group-item-danger">
                                                    <i class="fas fa-times me-2"></i><?= htmlspecialchars($point) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($feedback['improvement_suggestions'])): ?>
                                    <div class="mb-3">
                                        <strong>Suggestions for Improvement:</strong>
                                        <div class="alert alert-info">
                                            <i class="fas fa-lightbulb me-2"></i>
                                            <?= nl2br(htmlspecialchars($feedback['improvement_suggestions'])) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p><?= nl2br(htmlspecialchars($qa['feedback'])) ?></p>
                            <?php endif; ?>
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