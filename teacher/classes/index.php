<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

include_once INCLUDES_PATH . '/header.php';

$db = new Database();
$teacher_id = $_SESSION['user_id'];

// Get all classes created by this teacher or assigned to this teacher
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
    <h1>My Classes</h1>
    
    <!-- Teachers don't need to create classes, they're assigned by admins -->
    <div class="mb-4">
        <p class="text-muted">Classes are created and assigned by administrators.</p>
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
                            // Get student count for this class
                            $student_count = $db->single(
                                "SELECT COUNT(*) as count FROM class_enrollments WHERE class_id = ?",
                                [$class['id']]
                            )['count'];
                            
                            // Get quiz count for this class
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

<!-- Teacher classes are managed by admin, no create class functionality here -->

<?php include_once INCLUDES_PATH . '/footer.php'; ?>
