<?php
require_once __DIR__ . '/../config.php';
require_once LIB_PATH . '/database/db.php';

// Check if user is a teacher
if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];

// Get the teacher's quizzes
$quizzes = $db->resultSet("
    SELECT q.*, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as completed_count
    FROM quizzes q
    WHERE q.created_by = ?
    ORDER BY q.created_at DESC
", [$teacher_id]);

// Handle form submissions
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_quiz'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($title)) {
            $formError = 'Quiz title is required';
        } else {
            $db->query(
                "INSERT INTO quizzes (title, description, created_by, is_published) VALUES (?, ?, ?, 0)",
                [$title, $description, $teacher_id]
            );
            
            // Get the new quiz ID
            $newQuizId = $db->lastInsertId();
            
            $formSuccess = 'Quiz created successfully!';
            
            // Refresh quizzes
            $quizzes = $db->resultSet("
                SELECT q.*, 
                       (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
                       (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
                       (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as completed_count
                FROM quizzes q
                WHERE q.created_by = ?
                ORDER BY q.created_at DESC
            ", [$teacher_id]);
            
            // Redirect to the edit page for the new quiz
            redirect(BASE_URL . '/teacher/edit_quiz.php?id=' . $newQuizId);
        }
    } elseif (isset($_POST['delete_quiz'])) {
        $quiz_id = (int)($_POST['quiz_id'] ?? 0);
        
        // Check if the quiz belongs to this teacher
        $quiz = $db->single("
            SELECT * FROM quizzes 
            WHERE id = ? AND created_by = ?
        ", [$quiz_id, $teacher_id]);
        
        if ($quiz) {
            // Check if there are any attempts
            $attempts = $db->single("
                SELECT COUNT(*) as count FROM quiz_attempts 
                WHERE quiz_id = ?
            ", [$quiz_id]);
            
            if ($attempts && $attempts['count'] > 0) {
                $formError = 'Cannot delete a quiz that has been taken by students.';
            } else {
                // Delete questions first (due to foreign key constraints)
                $db->query("DELETE FROM questions WHERE quiz_id = ?", [$quiz_id]);
                
                // Then delete the quiz
                $db->query("DELETE FROM quizzes WHERE id = ?", [$quiz_id]);
                
                $formSuccess = 'Quiz deleted successfully!';
                
                // Refresh quizzes
                $quizzes = $db->resultSet("
                    SELECT q.*, 
                           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
                           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
                           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as completed_count
                    FROM quizzes q
                    WHERE q.created_by = ?
                    ORDER BY q.created_at DESC
                ", [$teacher_id]);
            }
        } else {
            $formError = 'Quiz not found or does not belong to you.';
        }
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-sm-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Teacher Dashboard
                </h1>
                <div class="d-flex">
                    <a href="<?= BASE_URL ?>/teacher/classes/" class="btn btn-primary me-2">
                        <i class="fas fa-users me-1"></i> Manage Classes
                    </a>
                    <button class="btn btn-success" id="create-quiz-btn">
                        <i class="fas fa-plus me-1"></i> Create New Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="row mb-4">
        <div class="col-12">
            <?php if (!empty($formError)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $formError ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($formSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $formSuccess ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (defined('AI_GRADING_ENABLED') && AI_GRADING_ENABLED): ?>
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="fas fa-robot fa-lg me-3"></i>
                    <div>
                        <strong>AI Grading Enabled:</strong> The system is using the DeepSeek AI for advanced answer grading. This provides more accurate and detailed feedback for students.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fas fa-info-circle fa-lg me-3"></i>
                    <div>
                        <strong>AI Grading Disabled:</strong> The system is using the basic keyword-matching grading system. Contact your administrator to enable AI grading for better results.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    
    <!-- Quizzes List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-book me-2"></i>Your Quizzes</h5>
                </div>
                <div class="card-body">
                    <?php if (count($quizzes) > 0): ?>
                        <div class="row">
                            <?php foreach ($quizzes as $quiz): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <?php if ($quiz['is_published']): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Draft</span>
                                            <?php endif; ?>
                                            <span class="text-muted small">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?= date('M j, Y', strtotime($quiz['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                            <?php if (!empty($quiz['description'])): ?>
                                                <p class="card-text mb-3">
                                                    <?= nl2br(htmlspecialchars(substr($quiz['description'], 0, 100))) ?>
                                                    <?= strlen($quiz['description']) > 100 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="row text-center mb-3">
                                                <div class="col-4">
                                                    <div class="border rounded py-2">
                                                        <div class="h4 mb-0"><?= $quiz['question_count'] ?></div>
                                                        <div class="small text-muted">Questions</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded py-2">
                                                        <div class="h4 mb-0"><?= $quiz['attempt_count'] ?></div>
                                                        <div class="small text-muted">Attempts</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded py-2">
                                                        <div class="h4 mb-0"><?= $quiz['completed_count'] ?></div>
                                                        <div class="small text-muted">Completed</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                                <?php if ($quiz['completed_count'] > 0): ?>
                                                    <a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-chart-bar me-1"></i> Results
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($quiz['attempt_count'] == 0): ?>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this quiz? This cannot be undone.');">
                                                    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                                    <button type="submit" name="delete_quiz" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <p class="lead">You haven't created any quizzes yet. Click the "Create New Quiz" button to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createQuizBtn = document.getElementById('create-quiz-btn');
    const modalElement = document.getElementById('create-quiz-modal');
    
    if (typeof bootstrap !== 'undefined') {
        const createQuizModal = new bootstrap.Modal(modalElement);
        
        // Show create quiz modal
        createQuizBtn.addEventListener('click', function() {
            createQuizModal.show();
        });
        
        // Auto-focus on title field when modal opens
        modalElement.addEventListener('shown.bs.modal', function () {
            document.getElementById('title').focus();
        });
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
