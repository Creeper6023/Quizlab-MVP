<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];


$quiz_hash_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($quiz_hash_id)) {
    redirect(BASE_URL . '/teacher');
    exit();
}


$quiz_id = getIdFromHash('quizzes', $quiz_hash_id);

if (!$quiz_id) {
    redirect(BASE_URL . '/teacher');
    exit();
}


$quiz = $db->single("
    SELECT * FROM quizzes WHERE id = ? AND created_by = ?
", [$quiz_id, $teacher_id]);

$isOwner = false;
$canEdit = false;
$canPublish = false;

if ($quiz) {
    $isOwner = true;
    $canEdit = true;
    $canPublish = true;
} else {

    $quiz = $db->single("
        SELECT q.* FROM quizzes q
        JOIN quiz_shares qs ON q.id = qs.quiz_id
        WHERE q.id = ? AND qs.shared_with_id = ?
    ", [$quiz_id, $teacher_id]);
    
    if ($quiz) {

        $permission = $db->single("
            SELECT permission_level FROM quiz_shares
            WHERE quiz_id = ? AND shared_with_id = ?
        ", [$quiz_id, $teacher_id]);
        
        if ($permission) {
            $canEdit = ($permission['permission_level'] == 'edit' || $permission['permission_level'] == 'full');
            $canPublish = ($permission['permission_level'] == 'full');
        }
    }
}

if (!$quiz) {

    set_flash_message('error', 'You do not have permission to edit this quiz.');
    redirect(BASE_URL . '/teacher');
    exit();
}


$quiz['can_edit'] = $canEdit;
$quiz['can_publish'] = $canPublish;
$quiz['is_owner'] = $isOwner;


if (!$quiz['can_edit']) {
    set_flash_message('error', 'You only have view permission for this quiz.');
    redirect(BASE_URL . '/teacher');
    exit();
}


$questions = $db->resultSet("
    SELECT * FROM questions 
    WHERE quiz_id = ? 
    ORDER BY id ASC
", [$quiz_id]);


$quizStudents = $db->resultSet("SELECT student_id FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
$studentIds = array_map(fn($student) => $student['student_id'], $quizStudents);


$studentCount = count($studentIds);


$attemptStats = $db->single("
    SELECT COUNT(DISTINCT student_id) as unique_students, COUNT(*) as total_attempts
    FROM quiz_attempts
    WHERE quiz_id = ? AND status = 'completed'
", [$quiz_id]);


$studentCompletion = 0;
if ($studentCount > 0 && isset($attemptStats['unique_students'])) {
    $studentCompletion = round(($attemptStats['unique_students'] / $studentCount) * 100);
}


$avgScore = $db->single("
    SELECT AVG(total_score) as average_score
    FROM quiz_attempts
    WHERE quiz_id = ? AND status = 'completed'
", [$quiz_id]);


$allowRetakes = (bool)$quiz['allow_redo'];


$retakeCount = $db->single("
    SELECT COUNT(*) as total_retakes
    FROM quiz_retakes
    WHERE quiz_id = ?
", [$quiz_id]);


$lastModified = date('M j, Y', strtotime($quiz['updated_at'] ?? $quiz['created_at']));


$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';


$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quiz'])) {

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $allow_redo = isset($_POST['allow_redo']) ? 1 : 0;
        $selected_students = $_POST['selected_students'] ?? [];

        if (empty($title)) {
            $formError = 'Quiz title is required';
            

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $formError
                ]);
                exit;
            }
        } else {
            $db->query(
                "UPDATE quizzes SET title = ?, description = ?, allow_redo = ? WHERE id = ?",
                [$title, $description, $allow_redo, $quiz_id]
            );


            $db->query("DELETE FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
            if (!empty($selected_students)) {
                $values = implode(',', array_fill(0, count($selected_students), '(?,?)'));
                $params = [];
                foreach ($selected_students as $student_id) {
                    $params[] = $quiz_id;
                    $params[] = $student_id;
                }
                $db->query(
                    "INSERT INTO quiz_student_access (quiz_id, student_id) VALUES " . $values,
                    $params
                );
            }

            $formSuccess = 'Quiz updated successfully!';


            $quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);
            

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $formSuccess
                ]);
                exit;
            }
        }
    } elseif (isset($_POST['add_question'])) {

        $question_text = trim($_POST['question_text'] ?? '');
        $model_answer = trim($_POST['model_answer'] ?? '');
        $points = (int)($_POST['points'] ?? 10);

        if (empty($question_text) || empty($model_answer)) {
            $formError = 'Question text and model answer are required';
            

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $formError
                ]);
                exit;
            }
        } else {
            $db->query(
                "INSERT INTO questions (quiz_id, question_text, model_answer, points) VALUES (?, ?, ?, ?)",
                [$quiz_id, $question_text, $model_answer, $points]
            );

            $formSuccess = 'Question added successfully!';


            $questions = $db->resultSet("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", [$quiz_id]);
            

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $formSuccess
                ]);
                exit;
            }
        }
    } elseif (isset($_POST['update_question'])) {

        $question_id = (int)($_POST['question_id'] ?? 0);
        $question_text = trim($_POST['question_text'] ?? '');
        $model_answer = trim($_POST['model_answer'] ?? '');
        $points = (int)($_POST['points'] ?? 10);

        if (empty($question_text) || empty($model_answer)) {
            $formError = 'Question text and model answer are required';
            

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $formError
                ]);
                exit;
            }
        } else {
            $db->query(
                "UPDATE questions SET question_text = ?, model_answer = ?, points = ? WHERE id = ? AND quiz_id = ?",
                [$question_text, $model_answer, $points, $question_id, $quiz_id]
            );

            $formSuccess = 'Question updated successfully!';


            $questions = $db->resultSet("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", [$quiz_id]);
            

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $formSuccess
                ]);
                exit;
            }
        }
    } elseif (isset($_POST['delete_question'])) {

        $question_id = (int)($_POST['question_id'] ?? 0);

        $db->query(
            "DELETE FROM questions WHERE id = ? AND quiz_id = ?",
            [$question_id, $quiz_id]
        );

        $formSuccess = 'Question deleted successfully!';


        $questions = $db->resultSet("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", [$quiz_id]);
        

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $formSuccess
            ]);
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="quiz-editor">
    <div class="quiz-editor-header">
        <h2>Edit Quiz: <?= htmlspecialchars($quiz['title']) ?></h2>
        <div class="quiz-actions">
            <a href="<?= BASE_URL ?>/teacher" class="btn btn-sm btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-chart-bar"></i> View Results
            </a>
            <a href="<?= BASE_URL ?>/teacher/assign_quiz_to_class.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-info">
                <i class="fas fa-users"></i> Assign & Publish
            </a>
            <?php if (!$quiz['can_publish']): ?>
                <button class="btn btn-sm btn-outline disabled" title="You don't have permission to publish/unpublish this quiz">
                    <i class="fas fa-eye-slash"></i> <?= $quiz['is_published'] ? 'Published' : 'Not Published' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($formError)): ?>
        <div class="alert alert-danger"><?= $formError ?></div>
    <?php endif; ?>

    <?php if (!empty($formSuccess)): ?>
        <div class="alert alert-success"><?= $formSuccess ?></div>
    <?php endif; ?>
    
    <!-- Dashboard Overview Section -->
    <div class="dashboard-overview mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Status</h5>
                        <p class="card-text fs-2"><?= $quiz['is_published'] ? '<i class="fas fa-check-circle"></i> Published' : '<i class="fas fa-clock"></i> Draft' ?></p>
                        <p class="card-text small">Last updated: <?= $lastModified ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Questions</h5>
                        <p class="card-text fs-2"><i class="fas fa-question-circle"></i> <?= count($questions) ?></p>
                        <p class="card-text small">Total points: <?= array_sum(array_column($questions, 'points')) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Students</h5>
                        <p class="card-text fs-2"><i class="fas fa-users"></i> <?= $studentCount ?></p>
                        <p class="card-text small">
                            <?php if (isset($attemptStats['unique_students'])): ?>
                                <?= $attemptStats['unique_students'] ?> completed (<?= $studentCompletion ?>%)
                            <?php else: ?>
                                No attempts yet
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Retakes</h5>
                        <p class="card-text fs-2">
                            <?php if ($allowRetakes): ?>
                                <i class="fas fa-sync-alt"></i> Allowed
                            <?php else: ?>
                                <i class="fas fa-user-check"></i> <?= $retakeCount['total_retakes'] ?? 0 ?>
                            <?php endif; ?>
                        </p>
                        <a href="<?= BASE_URL ?>/teacher/allow_retake.php?id=<?= $quiz_hash_id ?>" class="card-text small text-white">Manage retakes <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="quiz-editor-content">
        <div class="quiz-details-section">
            <h3>Quiz Details</h3>
            <div id="quizUpdateStatus" class="text-success mb-2" style="display:none;">
                <i class="fas fa-check-circle"></i> Quiz details saved automatically
            </div>
            <form method="POST" action="" class="quiz-form" id="quizDetailsForm">
                <div class="form-group mb-3">
                    <label for="title">Quiz Title</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($quiz['title']) ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($quiz['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="allow_redo" name="allow_redo" value="1" <?= $quiz['allow_redo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="allow_redo">Allow unlimited retakes for all students</label>
                </div>

                <!-- Hidden submit button - will be triggered by JavaScript -->
                <div class="form-group d-none">
                    <button type="submit" id="updateQuizBtn" name="update_quiz" class="btn btn-primary">Update Quiz</button>
                </div>
            </form>
        </div>

        <div class="quiz-questions-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Questions (<?= count($questions) ?>)</h3>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                    <i class="fas fa-plus"></i> Add New Question
                </button>
            </div>
            
            <!-- Add Question Modal -->
            <div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="addQuestionModalLabel">Add New Question</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div id="addQuestionStatus" class="alert alert-info mx-3 mt-2" style="display:none;">
                            <i class="fas fa-spinner fa-spin"></i> Adding question...
                        </div>
                        <form method="post" action="" id="addQuestionForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="question_text" class="form-label">Question Text</label>
                                    <textarea class="form-control" id="question_text" name="question_text" rows="4" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="model_answer" class="form-label">Model Answer</label>
                                    <textarea class="form-control" id="model_answer" name="model_answer" rows="4" required></textarea>
                                    <div class="form-text">Provide a comprehensive model answer that will be used to grade student responses.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="points" class="form-label">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" min="1" max="100" value="10" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_question" id="addQuestionBtn" class="btn btn-primary">Add Question</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (count($questions) > 0): ?>
                <div class="question-list">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <h4>Question <?= $index + 1 ?> (<?= $question['points'] ?> points)</h4>
                                <div class="question-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-question-btn" data-bs-toggle="modal" data-bs-target="#editQuestionModal<?= $question['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" action="" class="delete-question-form">
                                        <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                        <button type="submit" name="delete_question" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="question-body">
                                <div class="question-text">
                                    <p><?= nl2br(htmlspecialchars($question['question_text'])) ?></p>
                                </div>
                                <div class="model-answer">
                                    <h5>Model Answer:</h5>
                                    <p><?= nl2br(htmlspecialchars($question['model_answer'])) ?></p>
                                </div>
                            </div>

                            <!-- Edit Question Modal -->
                            <div class="modal fade editQuestionModal" id="editQuestionModal<?= $question['id'] ?>" tabindex="-1" aria-labelledby="editQuestionModalLabel<?= $question['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title" id="editQuestionModalLabel<?= $question['id'] ?>">Edit Question</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div id="editQuestionStatus<?= $question['id'] ?>" class="alert alert-info mx-3 mt-2" style="display:none;">
                                            <i class="fas fa-spinner fa-spin"></i> Saving question...
                                        </div>
                                        <form method="post" action="" id="editQuestionForm<?= $question['id'] ?>" class="edit-question-form" data-question-id="<?= $question['id'] ?>">
                                            <div class="modal-body">
                                                <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                                <div class="mb-3">
                                                    <label for="edit_question_text_<?= $question['id'] ?>" class="form-label">Question Text</label>
                                                    <textarea class="form-control edit-question-text" id="edit_question_text_<?= $question['id'] ?>" name="question_text" rows="4" required><?= htmlspecialchars($question['question_text']) ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_model_answer_<?= $question['id'] ?>" class="form-label">Model Answer</label>
                                                    <textarea class="form-control edit-model-answer" id="edit_model_answer_<?= $question['id'] ?>" name="model_answer" rows="4" required><?= htmlspecialchars($question['model_answer']) ?></textarea>
                                                    <div class="form-text">Provide a comprehensive model answer that will be used to grade student responses.</div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_points_<?= $question['id'] ?>" class="form-label">Points</label>
                                                    <input type="number" class="form-control edit-points" id="edit_points_<?= $question['id'] ?>" name="points" min="1" max="100" value="<?= $question['points'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_question" class="btn btn-primary update-question-btn">Update Question</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                    <p class="lead">No questions yet. Click the "Add New Question" button to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quizDetailsForm = document.getElementById('quizDetailsForm');
    const quizUpdateStatus = document.getElementById('quizUpdateStatus');
    const addQuestionForm = document.getElementById('addQuestionForm');
    const addQuestionStatus = document.getElementById('addQuestionStatus');
    

    if (quizDetailsForm) {
        const titleInput = document.getElementById('title');
        const descriptionInput = document.getElementById('description');
        const allowRedoCheckbox = document.getElementById('allow_redo');
        const updateQuizBtn = document.getElementById('updateQuizBtn');
        

        let saveTimeout;
        
        const autoSaveQuizDetails = () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                quizUpdateStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                quizUpdateStatus.style.display = 'block';
                quizUpdateStatus.className = 'text-info mb-2';
                

                const formData = new FormData(quizDetailsForm);
                formData.append('update_quiz', '1');
                if (!allowRedoCheckbox.checked) {
                    formData.delete('allow_redo');
                }
                

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        quizUpdateStatus.innerHTML = '<i class="fas fa-check-circle"></i> Quiz details saved successfully';
                        quizUpdateStatus.className = 'text-success mb-2';
                        setTimeout(() => {
                            quizUpdateStatus.style.display = 'none';
                        }, 2000);
                    } else {
                        throw new Error('Network response was not ok');
                    }
                })
                .catch(error => {
                    quizUpdateStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error saving quiz details';
                    quizUpdateStatus.className = 'text-danger mb-2';
                });
            }, 1000); // 1 second delay before saving
        };
        

        titleInput.addEventListener('input', autoSaveQuizDetails);
        descriptionInput.addEventListener('input', autoSaveQuizDetails);
        allowRedoCheckbox.addEventListener('change', autoSaveQuizDetails);
        

        quizDetailsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            autoSaveQuizDetails();
        });
    }
    

    if (addQuestionForm) {
        addQuestionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            addQuestionStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding question...';
            addQuestionStatus.style.display = 'block';
            addQuestionStatus.className = 'alert alert-info mx-3 mt-2';
            

            const formData = new FormData(addQuestionForm);
            formData.append('add_question', '1');
            

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {

                window.location.reload();
            })
            .catch(error => {
                addQuestionStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error adding question';
                addQuestionStatus.className = 'alert alert-danger mx-3 mt-2';
            });
        });
    }
    

    const deleteQuestionForms = document.querySelectorAll('.delete-question-form');
    deleteQuestionForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this question?')) {
                const formData = new FormData(form);
                formData.append('delete_question', '1');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {

                        window.location.reload();
                    } else {
                        alert(data.message || 'Error deleting question');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the question');
                });
            }
        });
    });
    

    const editQuestionForms = document.querySelectorAll('.edit-question-form');
    editQuestionForms.forEach(form => {
        const questionId = form.dataset.questionId;
        const statusElement = document.getElementById(`editQuestionStatus${questionId}`);
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving question...';
            statusElement.style.display = 'block';
            statusElement.className = 'alert alert-info mx-3 mt-2';
            

            const formData = new FormData(form);
            formData.append('update_question', '1');
            

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {

                window.location.reload();
            })
            .catch(error => {
                statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error saving question';
                statusElement.className = 'alert alert-danger mx-3 mt-2';
            });
        });
        

        const inputs = form.querySelectorAll('.form-control');
        let saveTimeout;
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {

                    const questionText = form.querySelector('.edit-question-text').value;
                    const modelAnswer = form.querySelector('.edit-model-answer').value;
                    
                    if (questionText.length > 10 && modelAnswer.length > 10) {
                        statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Auto-saving...';
                        statusElement.style.display = 'block';
                        statusElement.className = 'alert alert-info mx-3 mt-2';
                        

                        const formData = new FormData(form);
                        formData.append('update_question', '1');
                        

                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (response.ok) {
                                statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Question saved successfully';
                                statusElement.className = 'alert alert-success mx-3 mt-2';
                                setTimeout(() => {
                                    statusElement.style.display = 'none';
                                }, 2000);
                            } else {
                                throw new Error('Network response was not ok');
                            }
                        })
                        .catch(error => {
                            statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error saving question';
                            statusElement.className = 'alert alert-danger mx-3 mt-2';
                        });
                    }
                }, 2000); // 2 second delay before auto-saving
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>