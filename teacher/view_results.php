<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/database/db.php';

// Check if user is a teacher
if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the quiz exists and belongs to this teacher
$quiz = $db->single("
    SELECT * FROM quizzes 
    WHERE id = ? AND created_by = ?
", [$quiz_id, $teacher_id]);

if (!$quiz) {
    // Quiz doesn't exist or doesn't belong to this teacher
    redirect(BASE_URL . '/teacher');
}

// Get all attempts for this quiz
$attempts = $db->resultSet("
    SELECT qa.id, qa.student_id, qa.start_time, qa.end_time, qa.status, qa.total_score,
           u.username as student_name
    FROM quiz_attempts qa
    JOIN users u ON qa.student_id = u.id
    WHERE qa.quiz_id = ?
    ORDER BY qa.start_time DESC
", [$quiz_id]);

// If an attempt is selected, get the details
$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : null;
$selectedAttempt = null;
$answers = [];

if ($attempt_id) {
    $selectedAttempt = $db->single("
        SELECT qa.*, u.username as student_name
        FROM quiz_attempts qa
        JOIN users u ON qa.student_id = u.id
        WHERE qa.id = ? AND qa.quiz_id = ?
    ", [$attempt_id, $quiz_id]);

    if ($selectedAttempt) {
        // Get all answers for this attempt - FIX: Corrected order by
        $answers = $db->resultSet("
            SELECT a.*, q.question_text, q.model_answer, q.points as max_points
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.attempt_id = ?
            ORDER BY a.id ASC
        ", [$attempt_id]);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="quiz-results">
    <div class="quiz-results-header">
        <h2>Quiz Results: <?= htmlspecialchars($quiz['title']) ?></h2>
        <div class="quiz-actions">
            <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz_id ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i> Edit Quiz
            </a>
            <a href="<?= BASE_URL ?>/teacher" class="btn btn-sm btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="quiz-results-content">
        <div class="attempts-list">
            <h3>Student Attempts (<?= count($attempts) ?>)</h3>

            <?php if (count($attempts) > 0): ?>
                <table class="attempts-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr class="<?= $attempt_id == $attempt['id'] ? 'selected' : '' ?>">
                                <td><?= htmlspecialchars($attempt['student_name']) ?></td>
                                <td><?= date('M j, Y g:i a', strtotime($attempt['start_time'])) ?></td>
                                <td>
                                    <?php if ($attempt['status'] === 'completed'): ?>
                                        <span class="badgecomp badge-success">Completed</span>
                                    <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                        <span class="badge badge-warning">In Progress</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= ucfirst($attempt['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attempt['status'] === 'completed'): ?>
                                        <?= $attempt['total_score'] ?>%
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?id=<?= $quiz_id ?>&attempt_id=<?= $attempt['id'] ?>" class="btn btn-sm btn-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No attempts yet. Students will appear here once they start taking this quiz.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($selectedAttempt): ?>
            <div class="attempt-details">
                <h3>Attempt Details</h3>

                <div class="attempt-info mb-4">
                    <div class="info-item mb-3">
                        <span class="label">Student:</span>
                        <span class="value"><?= htmlspecialchars($selectedAttempt['student_name']) ?></span>
                    </div>
                    <div class="info-item mb-3">
                        <span class="label">Started:</span>
                        <span class="value"><?= date('M j, Y g:i a', strtotime($selectedAttempt['start_time'])) ?></span>
                    </div>

                    <?php if ($selectedAttempt['status'] === 'completed'): ?>
                        <div class="info-item">
                            <span class="label">Completed:</span>
                            <span class="value"><?= date('M j, Y g:i a', strtotime($selectedAttempt['end_time'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Total Score:</span>
                            <span class="value score"><?= $selectedAttempt['total_score'] ?>%</span>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <span class="label">Status:</span>
                            <span class="value">
                                <span class="badge badge-warning">In Progress</span>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($answers) > 0): ?>
                    <div class="answers-list">
                        <h4>Answers</h4>

                        <?php foreach ($answers as $index => $answer): ?>
                            <div class="answer-card">
                                <div class="answer-header">
                                    <h5>Question <?= $index + 1 ?></h5>
                                    <?php if ($selectedAttempt['status'] === 'completed'): ?>
                                        <div class="answer-score">
                                            Score: <span class="score-value"><?= $answer['score'] ?> / <?= $answer['max_points'] ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="question-text">
                                    <p><?= nl2br(htmlspecialchars($answer['question_text'])) ?></p>
                                </div>

                                <div class="answer-section">
                                    <div class="student-answer">
                                        <h6>Student's Answer:</h6>
                                        <p><?= empty($answer['answer_text']) ? '<em>No answer provided</em>' : nl2br(htmlspecialchars($answer['answer_text'])) ?></p>
                                    </div>

                                    <div class="model-answer">
                                        <h6>Model Answer:</h6>
                                        <p><?= nl2br(htmlspecialchars($answer['model_answer'])) ?></p>
                                    </div>
                                </div>

                                <?php if ($selectedAttempt['status'] === 'completed' && !empty($answer['feedback'])): ?>
                                    <div class="feedback-section">
                                        <h6>AI Feedback:</h6>
                                        <p><?= nl2br(htmlspecialchars($answer['feedback'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <p>No answers have been submitted yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif (count($attempts) > 0): ?>
            <div class="empty-state select-attempt">
                <i class="fas fa-hand-pointer"></i>
                <p>Select an attempt from the list to view details.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>