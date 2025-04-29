<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

$db = new Database();

$quiz_hash_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($quiz_hash_id)) {
    redirect(BASE_URL . '/admin/quizzes');
    exit();
}

$quiz_id = getIdFromHash('quizzes', $quiz_hash_id);

if (!$quiz_id) {
    redirect(BASE_URL . '/admin/quizzes');
    exit();
}

$quiz = $db->single("
    SELECT q.*, u.username as creator_name
    FROM quizzes q
    JOIN users u ON q.created_by = u.id
    WHERE q.id = ?
", [$quiz_id]);

if (!$quiz) {
    redirect(BASE_URL . '/admin/quizzes');
    exit();
}

$questions = $db->resultSet("
    SELECT * FROM questions
    WHERE quiz_id = ?
    ORDER BY id ASC
", [$quiz_id]);

$classes = $db->resultSet("
    SELECT c.name
    FROM classes c
    JOIN class_quizzes cq ON c.id = cq.class_id
    WHERE cq.quiz_id = ?
", [$quiz_id]);
$attemptStats = $db->single("
    SELECT 
        COUNT(*) as total_attempts,
        COALESCE(AVG(total_score), 0) as avg_score,
        COUNT(DISTINCT student_id) as student_count
    FROM quiz_attempts
    WHERE quiz_id = ? AND status = 'completed'
", [$quiz_id]);

include_once INCLUDES_PATH . '/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($quiz['title']) ?></h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/quizzes/edit_quiz.php?id=<?= $quiz_id ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Quiz
            </a>
            <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#shareQuizModal">
                <i class="fas fa-share-alt"></i> Share Quiz
            </button>
            <button type="button" class="btn btn-outline-info ms-2" data-bs-toggle="modal" data-bs-target="#alreadySharedModal">
                <i class="fas fa-users"></i> Manage Shares
            </button>
            <a href="<?= BASE_URL ?>/admin/quizzes" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Back to Quizzes
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quiz Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Description:</strong> <?= !empty($quiz['description']) ? nl2br(htmlspecialchars($quiz['description'])) : 'No description provided' ?></p>
                    <p><strong>Created by:</strong> <?= htmlspecialchars($quiz['creator_name']) ?></p>
                    <p><strong>Created:</strong> <?= date('F j, Y, g:i a', strtotime($quiz['created_at'])) ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= $quiz['is_published'] ? 'success' : 'warning' ?>">
                            <?= $quiz['is_published'] ? 'Published' : 'Draft' ?>
                        </span>
                    </p>
                    <p><strong>ID:</strong> <?= $quiz['id'] ?></p>
                    <p><strong>Hash ID:</strong> <?= $quiz['hash_id'] ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Questions</h5>
                </div>
                <div class="card-body">
                    <?php if (count($questions) > 0): ?>
                        <div class="accordion" id="questionsAccordion">
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $question['id'] ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?= $question['id'] ?>" aria-expanded="false" 
                                                aria-controls="collapse<?= $question['id'] ?>">
                                            Question <?= $index + 1 ?> (<?= $question['points'] ?> points)
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $question['id'] ?>" class="accordion-collapse collapse" 
                                         aria-labelledby="heading<?= $question['id'] ?>" data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <h6>Question:</h6>
                                                <p><?= nl2br(htmlspecialchars($question['question_text'])) ?></p>
                                            </div>
                                            <div>
                                                <h6>Model Answer:</h6>
                                                <p><?= nl2br(htmlspecialchars($question['model_answer'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            This quiz has no questions yet. Add questions using the Edit Quiz function.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total Attempts:</strong> <?= $attemptStats['total_attempts'] ?></p>
                    <p><strong>Average Score:</strong> <?= number_format($attemptStats['avg_score'], 1) ?>%</p>
                    <p><strong>Unique Students:</strong> <?= $attemptStats['student_count'] ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Assigned Classes</h5>
                </div>
                <div class="card-body">
                    <?php if (count($classes) > 0): ?>
                        <ul class="list-group">
                            <?php foreach ($classes as $class): ?>
                                <li class="list-group-item"><?= htmlspecialchars($class['name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>This quiz has not been assigned to any classes yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Quiz Modal -->
<div class="modal fade" id="shareQuizModal" tabindex="-1" aria-labelledby="shareQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareQuizModalLabel">Share Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="shareQuizForm" action="<?= BASE_URL ?>/ajax.php" method="post">
                    <input type="hidden" name="action" value="share_quiz">
                    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                    
                    <div class="mb-3">
                        <label for="shareWithSelect" class="form-label">Share with Teacher:</label>
                        <select class="form-select" id="shareWithSelect" name="shared_with" required>
                            <option value="">Select a teacher</option>
                            <?php
                            $teachers = $db->resultSet("
                                SELECT id, username, name 
                                FROM users 
                                WHERE role = ? AND id != ?
                                ORDER BY username ASC
                            ", [ROLE_TEACHER, $_SESSION['user_id']]);
                            
                            foreach ($teachers as $teacher) {
                                $display_name = !empty($teacher['name']) ? $teacher['name'] . ' (' . $teacher['username'] . ')' : $teacher['username'];
                                echo '<option value="' . $teacher['id'] . '">' . htmlspecialchars($display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="canEditCheck" name="can_edit" value="1">
                        <label class="form-check-label" for="canEditCheck">Allow editing</label>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle"></i> Sharing this quiz will allow the selected teacher to view it in their dashboard.
                            If "Allow editing" is checked, they will also be able to make changes to the quiz.
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="shareQuizBtn">Share</button>
            </div>
        </div>
    </div>
</div>

<!-- Already Shared With Modal -->
<div class="modal fade" id="alreadySharedModal" tabindex="-1" aria-labelledby="alreadySharedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alreadySharedModalLabel">Shared With</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                $sharedWith = $db->resultSet("
                    SELECT qs.id as share_id, qs.shared_at, qs.can_edit, u.id, u.username, u.name
                    FROM quiz_shares qs
                    JOIN users u ON qs.shared_with = u.id
                    WHERE qs.quiz_id = ? AND qs.shared_by = ?
                    ORDER BY qs.shared_at DESC
                ", [$quiz_id, $_SESSION['user_id']]);
                
                if (count($sharedWith) > 0) {
                    echo '<ul class="list-group">';
                    foreach ($sharedWith as $share) {
                        $display_name = !empty($share['name']) ? $share['name'] . ' (' . $share['username'] . ')' : $share['username'];
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                        echo htmlspecialchars($display_name);
                        
                        echo '<div>';
                        
                        if ($share['can_edit']) {
                            echo '<span class="badge bg-warning me-2">Can Edit</span>';
                        } else {
                            echo '<span class="badge bg-secondary me-2">View Only</span>';
                        }
                        
                        echo '<button type="button" class="btn btn-sm btn-danger remove-share-btn" data-share-id="' . $share['share_id'] . '">';
                        echo '<i class="fas fa-times"></i>';
                        echo '</button>';
                        
                        echo '</div>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>This quiz has not been shared with anyone yet.</p>';
                }
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const shareQuizBtn = document.getElementById('shareQuizBtn');
    if (shareQuizBtn) {
        shareQuizBtn.addEventListener('click', function() {
            const form = document.getElementById('shareQuizForm');
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Quiz shared successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('shareQuizModal'));
                    modal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sharing the quiz.');
            });
        });
    }
    
    const removeShareBtns = document.querySelectorAll('.remove-share-btn');
    removeShareBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const shareId = this.getAttribute('data-share-id');
            
            if (confirm('Are you sure you want to remove this share?')) {
                const formData = new FormData();
                formData.append('action', 'remove_quiz_share');
                formData.append('share_id', shareId);
                
                fetch('<?= BASE_URL ?>/ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const listItem = btn.closest('li');
                        listItem.remove();
                        
                        const sharesList = document.querySelector('#alreadySharedModal .list-group');
                        if (sharesList && sharesList.children.length === 0) {
                            const modalBody = document.querySelector('#alreadySharedModal .modal-body');
                            modalBody.innerHTML = '<p>This quiz has not been shared with anyone yet.</p>';
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the share.');
                });
            }
        });
    });
});
</script>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>