<?php
require_once __DIR__ . '/../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];


$quiz_hash_id = $_GET['id'] ?? '';
$quiz_id = null;


$quiz = $db->single("SELECT id FROM quizzes WHERE hash_id = ?", [$quiz_hash_id]);
if ($quiz) {
    $quiz_id = $quiz['id'];
} else {

    $quiz_id = (int)$quiz_hash_id;
}

if (!$quiz_id) {
    redirect(BASE_URL . '/teacher');
    exit();
}


$quiz = $db->single("
    SELECT q.*, 
           CASE WHEN q.created_by = ? THEN 'owner'
                ELSE qs.permission_level
           END as access_level,
           u.username as creator_name
    FROM quizzes q
    LEFT JOIN quiz_shares qs ON q.id = qs.quiz_id AND qs.shared_with_id = ?
    LEFT JOIN users u ON q.created_by = u.id
    WHERE q.id = ? AND (q.created_by = ? OR qs.shared_with_id = ?)
", [$teacher_id, $teacher_id, $quiz_id, $teacher_id, $teacher_id]);

if (!$quiz) {

    set_flash_message('error', 'You do not have permission to view this quiz.');
    redirect(BASE_URL . '/teacher');
    exit();
}


$questions = $db->resultSet("
    SELECT * FROM questions 
    WHERE quiz_id = ? 
    ORDER BY id ASC
", [$quiz_id]);

require_once INCLUDES_PATH . '/header.php';
?>

<div class="quiz-view-container">
    <div class="quiz-view-header">
        <h2>View Quiz: <?= htmlspecialchars($quiz['title']) ?></h2>
        <div class="quiz-actions">
            <a href="<?= BASE_URL ?>/teacher/quizzes.php" class="btn btn-sm btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Quizzes
            </a>
            
            <?php if ($quiz['access_level'] === 'edit' || $quiz['access_level'] === 'full' || $quiz['access_level'] === 'owner'): ?>
                <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i> Edit Quiz
                </a>
            <?php endif; ?>
            
            <?php if ($quiz['access_level'] === 'full' || $quiz['access_level'] === 'owner'): ?>
                <?php if ($quiz['is_published']): ?>
                    <a href="<?= BASE_URL ?>/teacher/unpublish_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-outline">
                        <i class="fas fa-eye-slash"></i> Unpublish
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/teacher/publish_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-check-circle"></i> Publish
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-info">
                <i class="fas fa-chart-bar"></i> View Results
            </a>
        </div>
    </div>
    
    <div class="quiz-info-card">
        <div class="info-header">
            <div>
                <span class="status-pill <?= $quiz['is_published'] ? 'published' : 'draft' ?>">
                    <?= $quiz['is_published'] ? 'Published' : 'Draft' ?>
                </span>
                <?php if ($quiz['access_level'] !== 'owner'): ?>
                    <span class="access-level-pill <?= $quiz['access_level'] ?>">
                        <?= ucfirst($quiz['access_level']) ?> Access
                    </span>
                <?php endif; ?>
            </div>
            <div class="created-info">
                <?php if ($quiz['access_level'] !== 'owner'): ?>
                    <div class="owner-info">
                        <i class="fas fa-user"></i> Created by: <?= htmlspecialchars($quiz['creator_name']) ?>
                    </div>
                <?php endif; ?>
                <div class="date-info">
                    <i class="fas fa-calendar-alt"></i> Created: <?= date('M j, Y', strtotime($quiz['created_at'])) ?>
                </div>
            </div>
        </div>
        
        <div class="info-content">
            <div class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></div>
            <?php if (!empty($quiz['description'])): ?>
                <div class="quiz-description"><?= nl2br(htmlspecialchars($quiz['description'])) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="info-footer">
            <div class="quiz-stats">
                <div class="stat-item">
                    <div class="stat-value"><?= count($questions) ?></div>
                    <div class="stat-label">Questions</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">
                        <?= $quiz['allow_redo'] ? 'Yes' : 'No' ?>
                    </div>
                    <div class="stat-label">Allow Redo</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="quiz-questions">
        <h3>Questions (<?= count($questions) ?>)</h3>
        
        <?php if (count($questions) > 0): ?>
            <div class="question-list">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card view-only">
                        <div class="question-header">
                            <h4>Question <?= $index + 1 ?> (<?= $question['points'] ?> points)</h4>
                        </div>
                        <div class="question-body">
                            <div class="question-text">
                                <p><?= nl2br(htmlspecialchars($question['question_text'])) ?></p>
                            </div>
                            <div class="model-answer">
                                <h5>Model Answer:</h5>
                                <p><?= nl2br(htmlspecialchars($question['model_answer'])) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                <p class="lead">This quiz doesn't have any questions yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.quiz-view-container {
    max-width: 100%;
    margin: 0 auto;
}

.quiz-view-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.quiz-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.quiz-info-card {
    background-color: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 2rem;
    overflow: hidden;
}

.info-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.status-pill, .access-level-pill {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    margin-right: 0.5rem;
}

.status-pill.published {
    background-color: #d4edda;
    color: #155724;
}

.status-pill.draft {
    background-color: #e2e3e5;
    color: #383d41;
}

.access-level-pill {
    background-color: #cce5ff;
    color: #004085;
}

.access-level-pill.view {
    background-color: #cce5ff;
    color: #004085;
}

.access-level-pill.edit {
    background-color: #d1ecf1;
    color: #0c5460;
}

.access-level-pill.full {
    background-color: #fff3cd;
    color: #856404;
}

.created-info {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: right;
}

.owner-info, .date-info {
    margin-bottom: 0.25rem;
}

.info-content {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.quiz-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.quiz-description {
    color: #6c757d;
    white-space: pre-line;
}

.info-footer {
    padding: 1rem;
    background-color: #f8f9fa;
}

.quiz-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #495057;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.quiz-questions {
    margin-top: 2rem;
}

.quiz-questions h3 {
    margin-bottom: 1.5rem;
}

.question-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.question-card {
    background-color: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    overflow: hidden;
}

.question-header {
    padding: 1rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.question-header h4 {
    margin: 0;
    font-size: 1.1rem;
}

.question-body {
    padding: 1.5rem;
}

.question-text {
    margin-bottom: 1.5rem;
}

.question-text p {
    margin: 0;
}

.model-answer h5 {
    margin: 0 0 0.75rem 0;
    font-size: 1rem;
    color: #495057;
}

.model-answer p {
    margin: 0;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    border-left: 4px solid #007bff;
}
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>