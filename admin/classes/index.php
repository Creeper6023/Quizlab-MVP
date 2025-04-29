<?php
require_once __DIR__ . '/../../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

include_once INCLUDES_PATH . '/header.php';

$db = new Database();


$classes = $db->resultSet("
    SELECT c.*, u.username as creator_name,
           (SELECT COUNT(*) FROM class_enrollments WHERE class_id = c.id) as student_count,
           (SELECT COUNT(*) FROM class_quizzes WHERE class_id = c.id) as quiz_count
    FROM classes c
    JOIN users u ON c.created_by = u.id
    ORDER BY c.name
");

?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Classes</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
            <i class="fas fa-plus"></i> Create New Class
        </button>
    </div>

    <?php if (empty($classes)): ?>
        <div class="alert alert-info">
            <p>No classes have been created yet. Click the "Create New Class" button to get started.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($classes as $class): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($class['name']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">Teacher: <?= htmlspecialchars($class['creator_name']) ?></h6>
                            <?php if (!empty($class['description'])): ?>
                                <p class="card-text"><?= htmlspecialchars(substr($class['description'], 0, 100)) ?>
                                <?= strlen($class['description']) > 100 ? '...' : '' ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <span class="me-2"><?= $class['student_count'] ?> student<?= $class['student_count'] != 1 ? 's' : '' ?></span>
                                    <span><?= $class['quiz_count'] ?> quiz<?= $class['quiz_count'] != 1 ? 'zes' : '' ?></span>
                                </small>
                                <div class="btn-group">
                                    <a href="manage.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">Manage</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-class" data-class-id="<?= $class['id'] ?>" data-class-name="<?= htmlspecialchars($class['name']) ?>">Delete</button>
                                </div>
                            </div>
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
            <div class="modal-header">
                <h5 class="modal-title" id="createClassModalLabel">Create New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <div class="mb-3">
                        <label for="teacherSelect" class="form-label">Teacher</label>
                        <select class="form-control" id="teacherSelect" name="teacher_id" required>
                            <option value="">Select a teacher</option>
                            <?php
                            $teachers = $db->resultSet(
                                "SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username"
                            );
                            foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

            fetch('<?= BASE_URL ?>/admin/classes/create_class.php', {
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


    document.querySelectorAll('.delete-class').forEach(button => {
        button.addEventListener('click', function() {
            const classId = this.dataset.classId;
            const className = this.dataset.className;

            if (confirm(`Are you sure you want to delete the class "${className}"? This action cannot be undone.`)) {
                fetch('<?= BASE_URL ?>/admin/classes/delete_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `class_id=${classId}`
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