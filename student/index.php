<?php
require_once __DIR__ . '/../config.php';


if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$student_id = $_SESSION['user_id'];


$enrolledClasses = $db->resultSet("
    SELECT c.*, u.username as teacher_name, ce.enrolled_at
    FROM classes c
    JOIN class_enrollments ce ON c.id = ce.class_id
    JOIN users u ON c.created_by = u.id
    WHERE ce.user_id = ?
    ORDER BY c.name
", [$student_id]);


$availableQuizzes = $db->resultSet("
    SELECT q.*, u.username as teacher_name, cqa.class_id, c.name as class_name
    FROM quizzes q
    JOIN users u ON q.created_by = u.id
    JOIN class_quiz_assignments cqa ON q.id = cqa.quiz_id
    JOIN classes c ON cqa.class_id = c.id
    JOIN class_enrollments ce ON c.id = ce.class_id
    WHERE q.is_published = 1 AND ce.user_id = ?
    GROUP BY q.id
    ORDER BY q.created_at DESC
", [$student_id]);


$directlyAssignedQuizzes = $db->resultSet("
    SELECT q.*, u.username as teacher_name, NULL as class_id, 'Direct Assignment' as class_name
    FROM quizzes q
    JOIN users u ON q.created_by = u.id
    JOIN quiz_student_access qsa ON q.id = qsa.quiz_id
    WHERE q.is_published = 1 AND qsa.student_id = ?
    AND q.id NOT IN (
        SELECT q2.id FROM quizzes q2
        JOIN class_quiz_assignments cqa ON q2.id = cqa.quiz_id
        JOIN classes c ON cqa.class_id = c.id
        JOIN class_enrollments ce ON c.id = ce.class_id
        WHERE ce.user_id = ?
    )
    ORDER BY q.created_at DESC
", [$student_id, $student_id]);


$availableQuizzes = array_merge($availableQuizzes, $directlyAssignedQuizzes);


$attempts = $db->resultSet("
    SELECT qa.*, q.title as quiz_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.student_id = ?
    ORDER BY qa.start_time DESC
", [$student_id]);


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
    <div class="row mb-4" id="classes">
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
    <div class="row mb-4" id="quizzes">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-book me-2"></i>Available Quizzes
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($availableQuizzes) > 0): ?>
                        <div class="quiz-list">
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

                                <div class="quiz-card">
                                    <div class="quiz-card-header">
                                        <h4><?= htmlspecialchars($quiz['title']) ?></h4>
                                        <div>
                                            <?php if ($hasCompleted): ?>
                                                <span class="quiz-status published">Completed</span>
                                            <?php elseif ($hasInProgress): ?>
                                                <span class="quiz-status draft">In Progress</span>
                                            <?php else: ?>
                                                <span class="quiz-status published">New</span>
                                            <?php endif; ?>

                                            <?php if (isset($quiz['due_date']) && $quiz['due_date']): ?>
                                                <span class="badge bg-danger ms-2">
                                                    <i class="fas fa-clock me-1"></i>Due: <?= date('M j, Y', strtotime($quiz['due_date'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="quiz-card-body">
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
                                        <div class="quiz-card-footer">
                                            <?php if ($hasCompleted): ?>
                                                <a href="<?= BASE_URL ?>/student/view_result.php?attempt_id=<?= $attemptsMap[$quiz['id']][0]['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-eye"></i> View Results
                                                </a>
                                                <?php 

                                                $canRetake = false;


                                                $quizAllowsRedo = $db->single(
                                                    "SELECT id FROM quizzes WHERE id = ? AND allow_redo = 1", 
                                                    [$quiz['id']]
                                                );





                                                $hasRetakePermission = $db->single(
                                                    "SELECT id FROM quiz_retakes WHERE quiz_id = ? AND student_id = ? AND used = 0",
                                                    [$quiz['id'], $student_id]
                                                );


                                                $log_message = "Retake check - Quiz: {$quiz['id']}, Student: $student_id, " . 
                                                               "Global allow_redo: " . ($quizAllowsRedo ? 'Yes' : 'No') . ", " .
                                                               "Individual permission: " . ($hasRetakePermission ? 'Yes' : 'No');
                                                file_put_contents(__DIR__ . '/../debug.log', $log_message . PHP_EOL, FILE_APPEND);

                                                if ($quizAllowsRedo || $hasRetakePermission) {
                                                    $canRetake = true;
                                                }

                                                if ($canRetake): 
                                                ?>
                                                <a href="<?= BASE_URL ?>/student/take_quiz.php?id=<?= $quiz['id'] ?>&retake=1" class="btn-retake">
                                                    <i class="fas fa-redo"></i> Retake Quiz
                                                </a>
                                                <?php endif; ?>
                                            <?php elseif ($hasInProgress): ?>
                                                <a href="<?= BASE_URL ?>/student/take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-play-circle"></i> Continue Quiz
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/student/take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-play-circle"></i> Start Quiz
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
    <div class="row mb-4" id="history">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-history me-2"></i>Your Quiz History
                    </h5>
                </div>
                <div class="card-body">
                    <?php 

    $completedAttempts = array_filter($attempts, function($attempt) {
        return $attempt['status'] === 'completed';
    });

    if (count($completedAttempts) > 0): ?>
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
                                    <?php foreach ($completedAttempts as $attempt): ?>
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