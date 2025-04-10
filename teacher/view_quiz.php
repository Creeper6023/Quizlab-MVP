<?php
require_once '../config.php';
require_once '../database/db.php';
include_once '../includes/header.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];

// Check if quiz ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(BASE_URL . '/teacher/');
    exit();
}

$quiz_id = (int)$_GET['id'];

// Get quiz details, including creator info
$quiz = $db->single(
    "SELECT q.*, u.username as creator_username, 
            CASE WHEN q.created_by = ? THEN 1 ELSE 0 END as is_owner
     FROM quizzes q
     JOIN users u ON q.created_by = u.id
     WHERE q.id = ?",
    [$teacher_id, $quiz_id]
);

if (!$quiz) {
    redirect(BASE_URL . '/teacher/');
    exit();
}

// If this is the owner, redirect to edit page
if ($quiz['is_owner']) {
    redirect(BASE_URL . '/teacher/edit_quiz.php?id=' . $quiz_id);
    exit();
}

// Get all questions for this quiz
$questions = $db->resultSet(
    "SELECT * FROM quiz_questions 
     WHERE quiz_id = ? 
     ORDER BY question_order ASC",
    [$quiz_id]
);

// Get classes where this quiz is assigned
$classes = $db->resultSet(
    "SELECT c.id, c.name, cq.due_date 
     FROM classes c
     JOIN class_quizzes cq ON c.id = cq.class_id
     LEFT JOIN class_teachers ct ON c.id = ct.class_id
     WHERE cq.quiz_id = ? AND (c.created_by = ? OR ct.teacher_id = ?)
     ORDER BY c.name ASC",
    [$quiz_id, $teacher_id, $teacher_id]
);
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">View Quiz</li>
        </ol>
    </nav>
    
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1><?= htmlspecialchars($quiz['title']) ?></h1>
            <div>
                <span class="badge bg-secondary"><?= $quiz['is_published'] ? 'Published' : 'Draft' ?></span>
            </div>
        </div>
        
        <div class="alert alert-light">
            <small>
                <strong>Created by:</strong> <?= htmlspecialchars($quiz['creator_username']) ?><br>
                <strong>Created:</strong> <?= date('F j, Y', strtotime($quiz['created_at'])) ?>
                <?php if ($quiz['updated_at']): ?>
                    <br><strong>Last updated:</strong> <?= date('F j, Y', strtotime($quiz['updated_at'])) ?>
                <?php endif; ?>
            </small>
        </div>
        
        <?php if (!empty($quiz['description'])): ?>
            <p class="lead"><?= htmlspecialchars($quiz['description']) ?></p>
        <?php endif; ?>
        
        <?php if (!empty($classes)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assigned Classes</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($classes as $class): ?>
                            <div class="list-group-item">
                                <h6 class="mb-1"><?= htmlspecialchars($class['name']) ?></h6>
                                <?php if ($class['due_date']): ?>
                                    <small class="text-muted">Due: <?= date('M j, Y', strtotime($class['due_date'])) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Questions</h5>
        </div>
        <div class="card-body">
            <?php if (empty($questions)): ?>
                <p class="text-muted">This quiz doesn't have any questions yet.</p>
            <?php else: ?>
                <div class="accordion" id="questionAccordion">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $question['id'] ?>">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $question['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $question['id'] ?>">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div>
                                            <span class="fw-bold">Question <?= $index + 1 ?></span>
                                            <?php if ($question['points'] > 0): ?>
                                                <span class="badge bg-secondary ms-2"><?= $question['points'] ?> points</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?= $question['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $question['id'] ?>" data-bs-parent="#questionAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Question Text</strong></label>
                                        <div class="border rounded p-3 bg-light">
                                            <?= nl2br(htmlspecialchars($question['question_text'])) ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Model Answer</strong></label>
                                        <div class="border rounded p-3 bg-light">
                                            <?= nl2br(htmlspecialchars($question['model_answer'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>