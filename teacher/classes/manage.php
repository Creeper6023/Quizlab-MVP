<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';
include_once INCLUDES_PATH . '/header.php';


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(BASE_URL . '/teacher/classes/');
    exit();
}

$class_id = (int)$_GET['id'];


$class = $db->single(
    "SELECT c.*, 
            CASE 
                WHEN c.created_by = ? THEN 'creator' 
                ELSE 'assigned' 
            END as role_type
     FROM classes c
     LEFT JOIN class_teachers ct ON c.id = ct.class_id
     WHERE c.id = ? AND (c.created_by = ? OR ct.teacher_id = ?)",
    [$teacher_id, $class_id, $teacher_id, $teacher_id]
);

if (!$class) {
    redirect(BASE_URL . '/teacher/classes/');
    exit();
}


$creator = $db->single(
    "SELECT id, username, email FROM users WHERE id = ?",
    [$class['created_by']]
);


$enrolled_students = $db->resultSet(
    "SELECT u.id, u.username, u.email, ce.enrolled_at 
     FROM users u
     JOIN class_enrollments ce ON u.id = ce.user_id
     WHERE ce.class_id = ? AND u.role = ?
     ORDER BY u.username",
    [$class_id, ROLE_STUDENT]
);


$class_quizzes = $db->resultSet(
    "SELECT q.id, q.title, q.description, q.is_published, cq.due_date,
            u.username as created_by_username,
            CASE WHEN q.created_by = ? THEN 1 ELSE 0 END as is_owner
     FROM quizzes q
     JOIN class_quizzes cq ON q.id = cq.quiz_id
     JOIN users u ON q.created_by = u.id
     WHERE cq.class_id = ?
     ORDER BY cq.added_at DESC",
    [$teacher_id, $class_id]
);


$available_quizzes = $db->resultSet(
    "SELECT q.id, q.title, q.description, q.is_published, q.created_at  
     FROM quizzes q
     WHERE q.created_by = ? AND q.id NOT IN (
         SELECT quiz_id FROM class_quizzes WHERE class_id = ?
     )
     ORDER BY q.created_at DESC",
    [$teacher_id, $class_id]
);


$available_students = $db->resultSet(
    "SELECT id, username, email FROM users 
     WHERE role = ? AND id NOT IN (
         SELECT user_id FROM class_enrollments WHERE class_id = ?
     )
     ORDER BY username",
    [ROLE_STUDENT, $class_id]
);
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/classes/">My Classes</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($class['name']) ?></li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0"><?= htmlspecialchars($class['name']) ?></h1>
            <?php if ($class['role_type'] === 'creator'): ?>
                <span class="badge bg-primary mb-2">You are the Creator</span>
            <?php else: ?>
                <span class="badge bg-info mb-2">You are a Co-teacher</span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($class['role_type'] === 'creator'): ?>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editClassModal">
                    Edit Class
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <p class="lead"><?= htmlspecialchars($class['description']) ?></p>
    
    <div class="alert alert-light">
        <small>
            <strong>Created by:</strong> <?= htmlspecialchars($creator['username']) ?> 
            <span class="text-muted">(<?= htmlspecialchars($creator['email'] ?? 'No email') ?>)</span>
            <br>
            <strong>Created:</strong> <?= date('F j, Y', strtotime($class['created_at'])) ?>
            <?php if ($class['updated_at']): ?>
                <br><strong>Last updated:</strong> <?= date('F j, Y', strtotime($class['updated_at'])) ?>
            <?php endif; ?>
        </small>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Students</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentsModal">
                        Add Students
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($enrolled_students)): ?>
                        <p class="text-muted">No students enrolled in this class yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrolled_students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['username']) ?></td>
                                            <td><?= htmlspecialchars($student['email'] ?? 'N/A') ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-student" 
                                                        data-student-id="<?= $student['id'] ?>" 
                                                        data-student-name="<?= htmlspecialchars($student['username']) ?>">
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Quizzes</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addQuizzesModal">
                        Add Quizzes
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($class_quizzes)): ?>
                        <p class="text-muted">No quizzes assigned to this class yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($class_quizzes as $quiz): ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($quiz['title']) ?></h6>
                                        <small class="text-muted">
                                            <?= $quiz['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>' ?>
                                            <?= $quiz['due_date'] ? ' • Due: ' . date('M j, Y', strtotime($quiz['due_date'])) : '' ?>
                                            • Created by: <?= htmlspecialchars($quiz['created_by_username']) ?>
                                            <?= $quiz['is_owner'] ? '<span class="badge bg-primary">Your Quiz</span>' : '' ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php if ($quiz['is_owner']): ?>
                                            <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/teacher/view_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-secondary me-1">View</a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-quiz" 
                                                data-quiz-id="<?= $quiz['id'] ?>" 
                                                data-quiz-title="<?= htmlspecialchars($quiz['title']) ?>">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editClassForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editClassName" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="editClassName" name="name" value="<?= htmlspecialchars($class['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="editClassDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editClassDescription" name="description" rows="3"><?= htmlspecialchars($class['description']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Students Modal -->
<div class="modal fade" id="addStudentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Students to Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudentsForm">
                <div class="modal-body">
                    <?php if (empty($available_students)): ?>
                        <p class="text-muted">All students are already enrolled in this class.</p>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Select Students</label>
                            <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($available_students as $student): ?>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="student_ids[]" value="<?= $student['id'] ?>">
                                        <?= htmlspecialchars($student['username']) ?> 
                                        <?= $student['email'] ? '(' . htmlspecialchars($student['email']) . ')' : '' ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if (!empty($available_students)): ?>
                        <button type="submit" class="btn btn-primary">Add Students</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Quizzes Modal -->
<div class="modal fade" id="addQuizzesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Quizzes to Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addQuizzesForm">
                <div class="modal-body">
                    <?php if (empty($available_quizzes)): ?>
                        <p class="text-muted">All your quizzes are already assigned to this class or you haven't created any quizzes yet.</p>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Select Quizzes</label>
                            <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($available_quizzes as $quiz): ?>
                                    <label class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <input class="form-check-input me-2" type="checkbox" name="quiz_ids[]" value="<?= $quiz['id'] ?>">
                                                <strong><?= htmlspecialchars($quiz['title']) ?></strong>
                                                <div class="small text-muted mt-1">
                                                    <?= $quiz['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>' ?>
                                                    <span class="ms-2">Created: <?= date('M j, Y', strtotime($quiz['created_at'])) ?></span>
                                                </div>
                                                <?php if (!empty($quiz['description'])): ?>
                                                    <div class="small text-truncate mt-1" style="max-width: 400px;">
                                                        <?= htmlspecialchars($quiz['description']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                View
                                            </a>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="quizDueDate" class="form-label">Due Date (Optional)</label>
                            <input type="date" class="form-control" id="quizDueDate" name="due_date">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if (!empty($available_quizzes)): ?>
                        <button type="submit" class="btn btn-primary">Add Quizzes</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classId = <?= $class_id ?>;
    

    const editClassForm = document.getElementById('editClassForm');
    editClassForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('class_id', classId);
        
        fetch('<?= BASE_URL ?>/teacher/classes/update_class.php', {
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
    

    const addStudentsForm = document.getElementById('addStudentsForm');
    if (addStudentsForm) {
        addStudentsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('class_id', classId);
            
            fetch('<?= BASE_URL ?>/teacher/classes/add_students.php', {
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
    

    const addQuizzesForm = document.getElementById('addQuizzesForm');
    if (addQuizzesForm) {
        addQuizzesForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('class_id', classId);
            
            fetch('<?= BASE_URL ?>/teacher/classes/add_quizzes.php', {
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
    

    document.querySelectorAll('.remove-student').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;
            
            if (confirm(`Are you sure you want to remove ${studentName} from this class?`)) {
                fetch('<?= BASE_URL ?>/teacher/classes/remove_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `class_id=${classId}&student_id=${studentId}`
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
    

    document.querySelectorAll('.remove-quiz').forEach(button => {
        button.addEventListener('click', function() {
            const quizId = this.dataset.quizId;
            const quizTitle = this.dataset.quizTitle;
            
            if (confirm(`Are you sure you want to remove "${quizTitle}" from this class?`)) {
                fetch('<?= BASE_URL ?>/teacher/classes/remove_quiz.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `class_id=${classId}&quiz_id=${quizId}`
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
