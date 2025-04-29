<?php
require_once __DIR__ . '/../../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

include_once INCLUDES_PATH . '/header.php';

$db = new Database();


$quizzes = $db->resultSet("
    SELECT q.*, u.username as creator_name,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count
    FROM quizzes q
    JOIN users u ON q.created_by = u.id
    ORDER BY q.created_at DESC
");

?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Quizzes</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createQuizModal">
            <i class="fas fa-plus"></i> Create New Quiz
        </button>
    </div>

    <?php if (empty($quizzes)): ?>
        <div class="alert alert-info">
            <p>No quizzes have been created yet. Click the "Create New Quiz" button to get started.</p>
        </div>
    <?php else: ?>
        <div class="shared-quiz-container">
            <?php foreach ($quizzes as $quiz): ?>
                <div class="shared-quiz-card">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?= $quiz['is_published'] ? 'success' : 'warning' ?>">
                                <?= $quiz['is_published'] ? 'Published' : 'Draft' ?>
                            </span>
                            <span class="text-muted small">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?= date('M j, Y', strtotime($quiz['created_at'])) ?>
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                            
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <small class="text-muted">Created by: <?= htmlspecialchars($quiz['creator_name']) ?></small>
                            </div>
                            
                            <?php if (!empty($quiz['description'])): ?>
                                <p class="card-text">
                                    <?= nl2br(htmlspecialchars(substr($quiz['description'], 0, 100))) ?>
                                    <?= strlen($quiz['description']) > 100 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="row text-center mt-auto mb-0 quiz-stats-row">
                                <div class="col-6">
                                    <div class="border rounded py-2">
                                        <div class="h4 mb-0"><?= $quiz['question_count'] ?></div>
                                        <div class="small text-muted">Questions</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded py-2">
                                        <div class="h4 mb-0"><?= $quiz['attempt_count'] ?></div>
                                        <div class="small text-muted">Attempts</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                <a href="<?= BASE_URL ?>/admin/quizzes/edit_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <a href="<?= BASE_URL ?>/admin/quizzes/view_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <a href="<?= BASE_URL ?>/admin/quizzes/share_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-share-alt me-1"></i> Share
                                </a>
                                
                                <?php if ($quiz['is_published']): ?>
                                    <a href="<?= BASE_URL ?>/admin/quizzes/unpublish_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-ban me-1"></i> Unpublish
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/admin/quizzes/publish_quiz.php?id=<?= $quiz['hash_id'] ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-check-circle me-1"></i> Publish
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-sm btn-outline-danger delete-quiz" 
                                        data-quiz-id="<?= $quiz['hash_id'] ?>" 
                                        data-quiz-title="<?= htmlspecialchars($quiz['title']) ?>">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Quiz Modal -->
<div class="modal fade" id="createQuizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createQuizModalLabel">Create New Quiz</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createQuizForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="quizTitle" class="form-label">Quiz Title</label>
                        <input type="text" class="form-control" id="quizTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="quizDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="quizDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Quiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const createQuizForm = document.getElementById('createQuizForm');
    if (createQuizForm) {
        createQuizForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('<?= BASE_URL ?>/admin/quizzes/create_quiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {

                    window.location.href = '<?= BASE_URL ?>/admin/quizzes/';
                    return;
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }


    document.querySelectorAll('.delete-quiz').forEach(button => {
        button.addEventListener('click', function() {
            const quizId = this.dataset.quizId;
            const quizTitle = this.dataset.quizTitle;

            if (confirm(`Are you sure you want to delete the quiz "${quizTitle}"? This action cannot be undone.`)) {
                fetch('<?= BASE_URL ?>/admin/quizzes/delete_quiz.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `quiz_id=${quizId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });
});
</script>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>