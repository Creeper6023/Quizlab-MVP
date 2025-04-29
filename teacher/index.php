<?php
require_once __DIR__ . '/../config.php';
require_once LIB_PATH . '/database/db.php';

if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];

$quizzes = $db->resultSet("
    SELECT q.*, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as completed_count,
           0 as is_shared
    FROM quizzes q
    WHERE q.created_by = ?
    ORDER BY q.created_at DESC
", [$teacher_id]);
$sharedQuizzes = $db->resultSet("
    SELECT q.*, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as completed_count,
           u.username as owner_name,
           s.permission_level,
           1 as is_shared
    FROM quiz_shares s
    JOIN quizzes q ON s.quiz_id = q.id
    JOIN users u ON q.created_by = u.id
    WHERE s.shared_with_id = ?
    ORDER BY s.shared_at DESC
", [$teacher_id]);

$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_quiz'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($title)) {
            $formError = 'Quiz title is required';
        } else {
            $hashId = generateHashId();
            
            while ($db->resultSet("SELECT id FROM quizzes WHERE hash_id = ?", [$hashId])) {
                $hashId = generateHashId();
            }
            
            $db->query(
                "INSERT INTO quizzes (title, description, created_by, is_published, hash_id) VALUES (?, ?, ?, 0, ?)",
                [$title, $description, $teacher_id, $hashId]
            );
            
            $newQuizId = $db->lastInsertId();
            
            $formSuccess = 'Quiz created successfully!';
            
            $quizzes = $db->resultSet("
                SELECT q.*, 
                       (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
                       (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
                       (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as completed_count
                FROM quizzes q
                WHERE q.created_by = ?
                ORDER BY q.created_at DESC
            ", [$teacher_id]);
            

            redirect(BASE_URL . '/teacher/edit_quiz.php?id=' . $hashId);
        }
    } elseif (isset($_POST['delete_quiz'])) {
        $quiz_id = (int)($_POST['quiz_id'] ?? 0);
        

        $quiz = $db->single("
            SELECT * FROM quizzes 
            WHERE id = ? AND created_by = ?
        ", [$quiz_id, $teacher_id]);
        
        if ($quiz) {

            $attempts = $db->single("
                SELECT COUNT(*) as count FROM quiz_attempts 
                WHERE quiz_id = ?
            ", [$quiz_id]);
            
            if ($attempts && $attempts['count'] > 0) {
                $formError = 'Cannot delete a quiz that has been taken by students.';
            } else {

                $db->query("DELETE FROM questions WHERE quiz_id = ?", [$quiz_id]);
                

                $db->query("DELETE FROM quizzes WHERE id = ?", [$quiz_id]);
                
                $formSuccess = 'Quiz deleted successfully!';
                

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
    <div class="row" id="quizzes">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
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
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                            <?php if (!empty($quiz['description'])): ?>
                                                <p class="card-text">
                                                    <?= nl2br(htmlspecialchars(substr($quiz['description'], 0, 100))) ?>
                                                    <?= strlen($quiz['description']) > 100 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="row text-center mt-auto mb-0 quiz-stats-row">
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
                                        
                                        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap">
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                                <?php if ($quiz['completed_count'] > 0): ?>
                                                    <a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-chart-bar me-1"></i> Results
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= BASE_URL ?>/teacher/share_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-share-alt me-1"></i> Share
                                                </a>
                                            </div>
                                            
                                            <?php if ($quiz['attempt_count'] == 0): ?>
                                                <form method="POST" action="" class="mt-2 mt-sm-0" onsubmit="return confirm('Are you sure you want to delete this quiz? This cannot be undone.');">
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
            
            <!-- Shared Quizzes -->
            <?php if (count($sharedQuizzes) > 0): ?>
            <div class="card shadow-sm" id="shared-quizzes">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-share-alt me-2"></i>Shared With You</h5>
                </div>
                <div class="card-body">
                    <div class="shared-quiz-container">
                        <?php foreach ($sharedQuizzes as $quiz): ?>
                            <div class="shared-quiz-card">
                                <div class="card h-100 shadow-sm border-primary border-opacity-25">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($quiz['is_published']): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Draft</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-info">
                                            <?= ucfirst($quiz['permission_level']) ?> Access
                                        </span>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <small class="text-muted">Shared by: <?= htmlspecialchars($quiz['owner_name']) ?></small>
                                        </div>
                                        
                                        <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                        <?php if (!empty($quiz['description'])): ?>
                                            <p class="card-text">
                                                <?= nl2br(htmlspecialchars(substr($quiz['description'], 0, 100))) ?>
                                                <?= strlen($quiz['description']) > 100 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="row text-center mt-auto mb-0 quiz-stats-row">
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
                                    
                                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="btn-group">
                                            <a href="<?= BASE_URL ?>/teacher/view_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                            
                                            <?php if (in_array($quiz['permission_level'], ['edit', 'full'])): ?>
                                                <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($quiz['completed_count'] > 0): ?>
                                                <a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-chart-bar me-1"></i> Results
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Quiz Modal -->
<div class="modal fade" id="create-quiz-modal" tabindex="-1" aria-labelledby="createQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createQuizModalLabel">Create New Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Quiz Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_quiz" class="btn btn-primary">Create Quiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createQuizBtn = document.getElementById('create-quiz-btn');
    const modalElement = document.getElementById('create-quiz-modal');
    
    if (typeof bootstrap !== 'undefined') {
        const createQuizModal = new bootstrap.Modal(modalElement);
        

        createQuizBtn.addEventListener('click', function() {
            createQuizModal.show();
        });
        

        modalElement.addEventListener('shown.bs.modal', function () {
            document.getElementById('title').focus();
        });
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
