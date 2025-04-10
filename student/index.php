<?php
require_once __DIR__ . '/../config.php';

// Check if user is a student
if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$student_id = $_SESSION['user_id'];

// Get student's enrolled classes
$enrolledClasses = $db->resultSet("
    SELECT c.*, u.username as teacher_name, ce.enrolled_at
    FROM classes c
    JOIN class_enrollments ce ON c.id = ce.class_id
    JOIN users u ON c.created_by = u.id
    WHERE ce.user_id = ?
    ORDER BY c.name
", [$student_id]);

// Get available quizzes (published) from enrolled classes
$availableQuizzes = $db->resultSet("
    SELECT q.*, u.username as teacher_name, cq.class_id, c.name as class_name, cq.due_date
    FROM quizzes q
    JOIN users u ON q.created_by = u.id
    JOIN class_quizzes cq ON q.id = cq.quiz_id
    JOIN classes c ON cq.class_id = c.id
    JOIN class_enrollments ce ON c.id = ce.class_id
    WHERE q.is_published = 1 AND ce.user_id = ?
    GROUP BY q.id
    ORDER BY q.created_at DESC
", [$student_id]);

// Get the student's attempts
$attempts = $db->resultSet("
    SELECT qa.*, q.title as quiz_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.student_id = ?
    ORDER BY qa.start_time DESC
", [$student_id]);

// Convert attempts to a map for easy lookup
$attemptsMap = [];
foreach ($attempts as $attempt) {
    if (!isset($attemptsMap[$attempt['quiz_id']])) {
        $attemptsMap[$attempt['quiz_id']] = [];
    }
    $attemptsMap[$attempt['quiz_id']][] = $attempt;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-sm-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-tachometer-alt me-2"></i>Student Dashboard
                </h1>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Enrolled Classes Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-graduation-cap me-2"></i>Your Classes
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($enrolledClasses) > 0): ?>
                        <div class="row">
                            <?php foreach ($enrolledClasses as $class): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 border-left-primary shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title text-primary"><?= htmlspecialchars($class['name']) ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                                <?= htmlspecialchars($class['teacher_name']) ?>
                                            </h6>
                                            <?php if (!empty($class['description'])): ?>
                                                <p class="card-text"><?= htmlspecialchars(substr($class['description'], 0, 100)) ?>
                                                <?= strlen($class['description']) > 100 ? '...' : '' ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-light">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                Joined: <?= date('M j, Y', strtotime($class['enrolled_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="fas fa-info-circle me-3 fa-lg"></i>
                            <div>You are not enrolled in any classes yet. Please contact your teacher.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Available Quizzes Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-book me-2"></i>Available Quizzes
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($availableQuizzes) > 0): ?>
                        <div class="row">
                            <?php foreach ($availableQuizzes as $quiz): ?>
                                <?php 
                                $hasCompleted = false;
                                $hasInProgress = false;
                                
                                if (isset($attemptsMap[$quiz['id']])) {
                                    foreach ($attemptsMap[$quiz['id']] as $attempt) {
                                        if ($attempt['status'] === 'completed') {
                                            $hasCompleted = true;
                                        } elseif ($attempt['status'] === 'in_progress') {
                                            $hasInProgress = true;
                                        }
                                    }
                                }
                                ?>
                                
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <span>
                                                <?php if ($hasCompleted): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($hasInProgress): ?>
                                                    <span class="badge bg-warning text-dark">In Progress</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">New</span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($quiz['due_date']): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-clock me-1"></i>Due: <?= date('M j, Y', strtotime($quiz['due_date'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center text-muted small mb-1">
                                                    <i class="fas fa-chalkboard-teacher me-1"></i>
                                                    <span><?= htmlspecialchars($quiz['teacher_name']) ?></span>
                                                </div>
                                                <div class="d-flex align-items-center text-muted small mb-1">
                                                    <i class="fas fa-graduation-cap me-1"></i>
                                                    <span><?= htmlspecialchars($quiz['class_name']) ?></span>
                                                </div>
                                                <div class="d-flex align-items-center text-muted small">
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    <span>Added: <?= date('M j, Y', strtotime($quiz['created_at'])) ?></span>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($quiz['description'])): ?>
                                                <p class="card-text">
                                                    <?= nl2br(htmlspecialchars(substr($quiz['description'], 0, 150))) ?>
                                                    <?= strlen($quiz['description']) > 150 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white border-top d-grid">
                                            <?php if ($hasCompleted): ?>
                                                <a href="<?= BASE_URL ?>/student/view_result.php?attempt_id=<?= $attemptsMap[$quiz['id']][0]['id'] ?>" class="btn btn-success">
                                                    <i class="fas fa-check-circle me-1"></i> View Results
                                                </a>
                                            <?php elseif ($hasInProgress): ?>
                                                <a href="<?= BASE_URL ?>/student/take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-play-circle me-1"></i> Continue Quiz
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/student/take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-play-circle me-1"></i> Start Quiz
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <p class="lead">No quizzes are available at the moment. Check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quiz History Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-history me-2"></i>Your Quiz History
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($attempts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Quiz</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attempts as $attempt): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($attempt['quiz_title']) ?></td>
                                            <td><?= date('M j, Y g:i a', strtotime($attempt['start_time'])) ?></td>
                                            <td>
                                                <?php if ($attempt['status'] === 'completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                                    <span class="badge bg-warning text-dark">In Progress</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= ucfirst($attempt['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($attempt['status'] === 'completed'): ?>
                                                    <span class="badge bg-primary rounded-pill px-3"><?= $attempt['total_score'] ?>%</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($attempt['status'] === 'completed'): ?>
                                                    <a href="<?= BASE_URL ?>/student/view_result.php?attempt_id=<?= $attempt['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i> View Results
                                                    </a>
                                                <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                                    <a href="<?= BASE_URL ?>/student/take_quiz.php?id=<?= $attempt['quiz_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-play me-1"></i> Continue
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="lead">You haven't taken any quizzes yet. Start one from the available quizzes list.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
