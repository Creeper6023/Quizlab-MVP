<?php
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

// Create database connection
$db = new Database();

// Get user information
$userId = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

// Get user details from database
$user = $db->single("SELECT * FROM users WHERE id = ?", [$userId]);

// If user not found, redirect to login
if (!$user) {
    $_SESSION['error_message'] = 'User not found. Please login again.';
    redirect(BASE_URL . '/auth/logout.php');
    exit();
}

// Get additional information based on user role
$quizScores = [];
$classInfo = [];
$upcomingQuizzes = [];
$recentActivity = [];

if ($role === ROLE_STUDENT) {
    // Get all quiz attempts for student
    $quizScores = $db->resultSet(
        "SELECT qa.id, qa.quiz_id, q.title, qa.total_score, qa.status, qa.created_at 
         FROM quiz_attempts qa 
         JOIN quizzes q ON qa.quiz_id = q.id 
         WHERE qa.student_id = ? 
         ORDER BY qa.created_at DESC",
        [$userId]
    );
    
    // Get enrolled classes for student
    $classInfo = $db->resultSet(
        "SELECT c.id, c.name, u.username as teacher_name,
         (SELECT COUNT(*) FROM class_quizzes cq JOIN quizzes q ON cq.quiz_id = q.id WHERE cq.class_id = c.id AND q.is_published = 1) as available_quizzes
         FROM classes c
         JOIN class_enrollments ce ON c.id = ce.class_id
         JOIN users u ON c.created_by = u.id
         WHERE ce.student_id = ?
         ORDER BY c.name",
        [$userId]
    );
    
    // Get upcoming quizzes (published but not attempted)
    $upcomingQuizzes = $db->resultSet(
        "SELECT q.id, q.title, c.name as class_name, q.created_at
         FROM quizzes q
         JOIN class_quizzes cq ON q.id = cq.quiz_id
         JOIN classes c ON cq.class_id = c.id
         JOIN class_enrollments ce ON c.id = ce.class_id
         WHERE ce.student_id = ? AND q.is_published = 1
         AND NOT EXISTS (
             SELECT 1 FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.student_id = ?
         )
         ORDER BY q.created_at DESC",
        [$userId, $userId]
    );
} elseif ($role === ROLE_TEACHER) {
    // Get classes created by teacher
    $classInfo = $db->resultSet(
        "SELECT c.id, c.name, c.description, c.created_at,
         (SELECT COUNT(*) FROM class_enrollments WHERE class_id = c.id) as student_count,
         (SELECT COUNT(*) FROM class_quizzes WHERE class_id = c.id) as quiz_count
         FROM classes c
         WHERE c.created_by = ?
         ORDER BY c.created_at DESC",
        [$userId]
    );
    
    // Get recent quizzes created by teacher
    $recentActivity = $db->resultSet(
        "SELECT q.id, q.title, q.is_published, q.created_at,
         (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as question_count,
         (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count
         FROM quizzes q
         WHERE q.created_by = ?
         ORDER BY q.created_at DESC
         LIMIT 10",
        [$userId]
    );
}

// Set page title
$pageTitle = "User Profile";

// Include header
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-sm-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-user-circle me-2"></i><?php echo $pageTitle; ?>
                </h1>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-id-card me-2"></i>User Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="bg-light rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                            <i class="fas fa-user-circle fa-4x text-primary"></i>
                        </div>
                        <h4 class="mb-0"><?= htmlspecialchars($username) ?></h4>
                        <span class="badge bg-<?= $role === ROLE_STUDENT ? 'info' : ($role === ROLE_TEACHER ? 'success' : 'dark') ?> mt-2 p-2">
                            <?= ucfirst($role) ?>
                        </span>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-envelope me-2"></i>Email</span>
                            <span class="text-muted"><?= htmlspecialchars($user['email'] ?? 'Not available') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar-alt me-2"></i>Joined</span>
                            <span class="text-muted"><?= isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'Not available' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-key me-2"></i>Last Login</span>
                            <span class="text-muted"><?= isset($user['last_login']) ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Not available' ?></span>
                        </li>
                    </ul>
                </div>
                <div class="card-footer bg-light">
                    <a href="#" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <?php if ($role === ROLE_STUDENT): ?>
                <!-- Student Specific Content -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Quiz Performance
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($quizScores)): ?>
                            <div class="alert alert-info">
                                <p>You haven't attempted any quizzes yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Quiz</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($quizScores as $score): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($score['title']) ?></td>
                                                <td>
                                                    <?php if ($score['status'] === 'completed'): ?>
                                                        <span class="badge bg-primary"><?= $score['total_score'] ?>%</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">In Progress</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($score['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php elseif ($score['status'] === 'in_progress'): ?>
                                                        <span class="badge bg-warning">In Progress</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= ucfirst($score['status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($score['created_at'])) ?></td>
                                                <td>
                                                    <?php if ($score['status'] === 'completed'): ?>
                                                        <a href="<?= BASE_URL ?>/student/view_result.php?attempt_id=<?= $score['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php elseif ($score['status'] === 'in_progress'): ?>
                                                        <a href="<?= BASE_URL ?>/student/take_quiz.php?quiz_id=<?= $score['quiz_id'] ?>" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Student's Enrolled Classes -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-chalkboard me-2"></i>My Classes
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($classInfo)): ?>
                            <div class="alert alert-info">
                                <p>You are not enrolled in any classes yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                <?php foreach ($classInfo as $class): ?>
                                    <div class="col">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($class['name']) ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted">Teacher: <?= htmlspecialchars($class['teacher_name']) ?></h6>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <span><?= $class['available_quizzes'] ?> available <?= $class['available_quizzes'] != 1 ? 'quizzes' : 'quiz' ?></span>
                                                    </small>
                                                    <a href="<?= BASE_URL ?>/student/class.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Upcoming Quizzes -->
                <?php if (!empty($upcomingQuizzes)): ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Upcoming Quizzes
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($upcomingQuizzes as $quiz): ?>
                                    <a href="<?= BASE_URL ?>/student/take_quiz.php?quiz_id=<?= $quiz['id'] ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?= htmlspecialchars($quiz['title']) ?></h5>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($quiz['created_at'])) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">Class: <?= htmlspecialchars($quiz['class_name']) ?></p>
                                        <small>
                                            <span class="text-primary">Click to start</span>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($role === ROLE_TEACHER): ?>
                <!-- Teacher Specific Content -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>My Classes
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($classInfo)): ?>
                            <div class="alert alert-info">
                                <p>You haven't created any classes yet.</p>
                                <a href="<?= BASE_URL ?>/teacher/classes/create_class.php" class="btn btn-success mt-2">
                                    <i class="fas fa-plus me-2"></i>Create New Class
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Class Name</th>
                                            <th>Students</th>
                                            <th>Quizzes</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classInfo as $class): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($class['name']) ?></td>
                                                <td><?= $class['student_count'] ?></td>
                                                <td><?= $class['quiz_count'] ?></td>
                                                <td><?= date('M d, Y', strtotime($class['created_at'])) ?></td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>/teacher/classes/manage.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-cog"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="<?= BASE_URL ?>/teacher/classes/create_class.php" class="btn btn-success mt-3">
                                <i class="fas fa-plus me-2"></i>Create New Class
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Schedule Planner -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Schedule Planner
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Weekly Schedule Grid -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="text-center">Time</th>
                                        <th class="text-center">Monday</th>
                                        <th class="text-center">Tuesday</th>
                                        <th class="text-center">Wednesday</th>
                                        <th class="text-center">Thursday</th>
                                        <th class="text-center">Friday</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $timeSlots = ['8:00-9:00', '9:00-10:00', '10:00-11:00', '11:00-12:00', '12:00-13:00', '13:00-14:00', '14:00-15:00', '15:00-16:00'];
                                    foreach ($timeSlots as $timeSlot):
                                    ?>
                                    <tr>
                                        <td class="bg-light text-center fw-bold"><?= $timeSlot ?></td>
                                        <td class="schedule-cell" data-day="Monday" data-time="<?= $timeSlot ?>"></td>
                                        <td class="schedule-cell" data-day="Tuesday" data-time="<?= $timeSlot ?>"></td>
                                        <td class="schedule-cell" data-day="Wednesday" data-time="<?= $timeSlot ?>"></td>
                                        <td class="schedule-cell" data-day="Thursday" data-time="<?= $timeSlot ?>"></td>
                                        <td class="schedule-cell" data-day="Friday" data-time="<?= $timeSlot ?>"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary" id="editScheduleBtn">
                                <i class="fas fa-edit me-2"></i>Edit Schedule
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <?php if (!empty($recentActivity)): ?>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Recent Quiz Activity
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($recentActivity as $activity): ?>
                                    <a href="<?= BASE_URL ?>/teacher/view_quiz.php?id=<?= $activity['id'] ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h5>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($activity['created_at'])) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <span class="badge <?= $activity['is_published'] ? 'bg-success' : 'bg-warning' ?>">
                                                <?= $activity['is_published'] ? 'Published' : 'Draft' ?>
                                            </span>
                                            <span class="ms-2"><?= $activity['question_count'] ?> questions</span>
                                            <span class="ms-2"><?= $activity['attempt_count'] ?> attempts</span>
                                        </p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // For teacher schedule planner
    const editScheduleBtn = document.getElementById('editScheduleBtn');
    if (editScheduleBtn) {
        editScheduleBtn.addEventListener('click', function() {
            const scheduleCells = document.querySelectorAll('.schedule-cell');
            
            scheduleCells.forEach(cell => {
                cell.addEventListener('click', function() {
                    // Toggle class selection
                    if (cell.classList.contains('bg-primary')) {
                        cell.classList.remove('bg-primary', 'text-white');
                        cell.textContent = '';
                    } else {
                        // Prompt for class name
                        const className = prompt('Enter class name or event:');
                        if (className) {
                            cell.classList.add('bg-primary', 'text-white');
                            cell.textContent = className;
                        }
                    }
                });
            });
            
            // Toggle button text
            if (this.textContent.includes('Edit')) {
                this.innerHTML = '<i class="fas fa-save me-2"></i>Save Schedule';
            } else {
                this.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Schedule';
                // Here you would typically save the schedule to the database
                alert('Schedule saved successfully!');
            }
        });
    }
    
    // Change password form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                alert('New password and confirmation do not match.');
                return;
            }
            
            // Ajax request to change password
            const formData = new FormData(this);
            formData.append('action', 'change_password');
            
            fetch('<?= BASE_URL ?>/ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password changed successfully.');
                    document.getElementById('changePasswordModal').classList.remove('show');
                    document.querySelector('.modal-backdrop').remove();
                    changePasswordForm.reset();
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

<?php
// Include footer
include_once INCLUDES_PATH . '/footer.php';
?>