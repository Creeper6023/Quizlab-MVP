<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

include_once INCLUDES_PATH . '/header.php';

$db = new Database();
$teacher_id = $_SESSION['user_id'];


$classes = $db->resultSet(
    "SELECT c.*, 
            CASE 
                WHEN c.created_by = ? THEN 'creator' 
                ELSE 'assigned' 
            END as role_type
     FROM classes c
     LEFT JOIN class_teachers ct ON c.id = ct.class_id
     WHERE c.created_by = ? OR ct.teacher_id = ?
     GROUP BY c.id
     ORDER BY c.created_at DESC",
    [$teacher_id, $teacher_id, $teacher_id]
);
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Classes</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
            <i class="fas fa-plus"></i> Create New Class
        </button>
    </div>
    
    <?php if (empty($classes)): ?>
        <div class="alert alert-info">
            You don't have any assigned classes yet. Please contact an administrator to be assigned to classes.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($classes as $class): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($class['name']) ?></h5>
                                <?php if ($class['role_type'] === 'creator'): ?>
                                    <span class="badge bg-primary">Creator</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Co-teacher</span>
                                <?php endif; ?>
                            </div>
                            <p class="card-text mt-2"><?= htmlspecialchars($class['description']) ?></p>
                            <?php

                            $student_count = $db->single(
                                "SELECT COUNT(*) as count FROM class_enrollments WHERE class_id = ?",
                                [$class['id']]
                            )['count'];
                            

                            $quiz_count = $db->single(
                                "SELECT COUNT(*) as count FROM class_quizzes WHERE class_id = ?",
                                [$class['id']]
                            )['count'];
                            ?>
                            <p class="card-text">
                                <small class="text-muted">
                                    <?= $student_count ?> Student<?= $student_count !== 1 ? 's' : '' ?> • 
                                    <?= $quiz_count ?> Quiz<?= $quiz_count !== 1 ? 'zes' : '' ?>
                                </small>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <a href="<?= BASE_URL ?>/teacher/classes/manage.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">Manage Class</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Class Modal -->
<div class="modal fade" id="createClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createClassModalLabel">Create New Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createClassForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="className" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="className" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="classDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="classDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const createClassForm = document.getElementById('createClassForm');
    if (createClassForm) {
        createClassForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('<?= BASE_URL ?>/teacher/classes/create_class.php', {
                method: 'POST',
                body: formData
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
        });
    }
});
</script>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>
