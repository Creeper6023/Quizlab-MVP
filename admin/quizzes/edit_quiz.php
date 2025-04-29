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

$quizStudents = $db->resultSet("SELECT student_id FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
$studentIds = array_map(fn($student) => $student['student_id'], $quizStudents);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quiz'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $selected_students = $_POST['selected_students'] ?? [];

    if (empty($title)) {
        $_SESSION['error'] = 'Quiz title is required';
    } else {
        try {
            $db->query(
                "UPDATE quizzes SET title = ?, description = ? WHERE id = ?",
                [$title, $description, $quiz_id]
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
            
            $_SESSION['success'] = 'Quiz updated successfully';
            
            $quiz = $db->single("
                SELECT q.*, u.username as creator_name
                FROM quizzes q
                JOIN users u ON q.created_by = u.id
                WHERE q.id = ?
            ", [$quiz_id]);
            
            $quizStudents = $db->resultSet("SELECT student_id FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
            $studentIds = array_map(fn($student) => $student['student_id'], $quizStudents);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to update quiz: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question_text'] ?? '');
    $model_answer = trim($_POST['model_answer'] ?? '');
    $points = (int)($_POST['points'] ?? 1);
    
    if (empty($question_text)) {
        $_SESSION['error'] = 'Question text is required';
    } else if (empty($model_answer)) {
        $_SESSION['error'] = 'Model answer is required';
    } else if ($points <= 0) {
        $_SESSION['error'] = 'Points must be greater than zero';
    } else {
        try {
            $db->query(
                "INSERT INTO questions (quiz_id, question_text, model_answer, points) VALUES (?, ?, ?, ?)",
                [$quiz_id, $question_text, $model_answer, $points]
            );
            $_SESSION['success'] = 'Question added successfully';
            
            $questions = $db->resultSet("
                SELECT * FROM questions
                WHERE quiz_id = ?
                ORDER BY id ASC
            ", [$quiz_id]);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to add question: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_question'])) {
    $question_id = (int)($_POST['question_id'] ?? 0);
    $question_text = trim($_POST['question_text'] ?? '');
    $model_answer = trim($_POST['model_answer'] ?? '');
    $points = (int)($_POST['points'] ?? 1);
    
    if ($question_id <= 0) {
        $_SESSION['error'] = 'Invalid question ID';
    } else if (empty($question_text)) {
        $_SESSION['error'] = 'Question text is required';
    } else if (empty($model_answer)) {
        $_SESSION['error'] = 'Model answer is required';
    } else if ($points <= 0) {
        $_SESSION['error'] = 'Points must be greater than zero';
    } else {
        try {
            $db->query(
                "UPDATE questions SET question_text = ?, model_answer = ?, points = ? WHERE id = ? AND quiz_id = ?",
                [$question_text, $model_answer, $points, $question_id, $quiz_id]
            );
            $_SESSION['success'] = 'Question updated successfully';
            
            $questions = $db->resultSet("
                SELECT * FROM questions
                WHERE quiz_id = ?
                ORDER BY id ASC
            ", [$quiz_id]);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to update question: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question'])) {
    $question_id = (int)($_POST['question_id'] ?? 0);
    
    if ($question_id <= 0) {
        $_SESSION['error'] = 'Invalid question ID';
    } else {
        try {
            $answers = $db->resultSet("
                SELECT sa.* 
                FROM student_answers sa
                JOIN quiz_attempts qa ON sa.attempt_id = qa.id
                WHERE qa.quiz_id = ? AND sa.question_id = ?
            ", [$quiz_id, $question_id]);
            
            if (count($answers) > 0) {
                $_SESSION['error'] = 'This question has student answers and cannot be deleted';
            } else {
                $db->query(
                    "DELETE FROM questions WHERE id = ? AND quiz_id = ?",
                    [$question_id, $quiz_id]
                );
                $_SESSION['success'] = 'Question deleted successfully';
                
                $questions = $db->resultSet("
                    SELECT * FROM questions
                    WHERE quiz_id = ?
                    ORDER BY id ASC
                ", [$quiz_id]);
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to delete question: ' . $e->getMessage();
        }
    }
}

include_once INCLUDES_PATH . '/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Quiz</h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/quizzes/view_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Quiz
            </a>
            <a href="<?= BASE_URL ?>/admin/quizzes" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Back to Quizzes
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Quiz Details</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Quiz Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($quiz['description']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="selected_students" class="form-label">Select Students</label>
                            <select id="selected_students" name="selected_students[]" multiple class="form-select" style="height: 150px;">
                                <?php 
                                $students = $db->resultSet("SELECT id, username, name FROM users WHERE role = ? ORDER BY name, username", [ROLE_STUDENT]);
                                foreach ($students as $student): 
                                    $display_name = !empty($student['name']) ? $student['name'] . ' (' . $student['username'] . ')' : $student['username'];
                                ?>
                                    <option value="<?= $student['id'] ?>" <?= in_array($student['id'], $studentIds) ? 'selected' : '' ?>><?= htmlspecialchars($display_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">Hold Ctrl (or Cmd on Mac) to select multiple students. If no students are selected, all students can access the quiz.</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" name="update_quiz" class="btn btn-primary">Update Quiz</button>
                            </div>
                            <div>
                                <span class="text-muted">Created by: <?= htmlspecialchars($quiz['creator_name']) ?> | Status: 
                                    <span class="badge bg-<?= $quiz['is_published'] ? 'success' : 'warning' ?>">
                                        <?= $quiz['is_published'] ? 'Published' : 'Draft' ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Questions</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </div>
                <div class="card-body">
                    <?php if (count($questions) > 0): ?>
                        <div class="row">
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="col-md-12 mb-3">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Question <?= $index + 1 ?> (<?= $question['points'] ?> points)</h6>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-question-btn" 
                                                        data-bs-toggle="modal" data-bs-target="#editQuestionModal"
                                                        data-question-id="<?= $question['id'] ?>"
                                                        data-question-text="<?= htmlspecialchars($question['question_text']) ?>"
                                                        data-model-answer="<?= htmlspecialchars($question['model_answer']) ?>"
                                                        data-points="<?= $question['points'] ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-question-btn"
                                                        data-bs-toggle="modal" data-bs-target="#deleteQuestionModal"
                                                        data-question-id="<?= $question['id'] ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
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
                            <p>This quiz has no questions yet. Add questions using the "Add Question" button.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addQuestionModalLabel">Add New Question</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
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
                        <input type="number" class="form-control" id="points" name="points" min="1" value="10" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Question Modal -->
<div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editQuestionModalLabel">Edit Question</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_question_id" name="question_id">
                    <div class="mb-3">
                        <label for="edit_question_text" class="form-label">Question Text</label>
                        <textarea class="form-control" id="edit_question_text" name="question_text" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_model_answer" class="form-label">Model Answer</label>
                        <textarea class="form-control" id="edit_model_answer" name="model_answer" rows="4" required></textarea>
                        <div class="form-text">Provide a comprehensive model answer that will be used to grade student responses.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_points" class="form-label">Points</label>
                        <input type="number" class="form-control" id="edit_points" name="points" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_question" class="btn btn-primary">Update Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Question Modal -->
<div class="modal fade" id="deleteQuestionModal" tabindex="-1" aria-labelledby="deleteQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteQuestionModalLabel">Delete Question</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this question? This action cannot be undone.</p>
                <p><strong>Note:</strong> If students have already answered this question, it cannot be deleted.</p>
            </div>
            <form method="post" action="">
                <input type="hidden" id="delete_question_id" name="question_id">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_question" class="btn btn-danger">Delete Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-question-btn');
    if (editButtons) {
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const questionId = this.getAttribute('data-question-id');
                const questionText = this.getAttribute('data-question-text');
                const modelAnswer = this.getAttribute('data-model-answer');
                const points = this.getAttribute('data-points');
                
                document.getElementById('edit_question_id').value = questionId;
                document.getElementById('edit_question_text').value = questionText;
                document.getElementById('edit_model_answer').value = modelAnswer;
                document.getElementById('edit_points').value = points;
            });
        });
    }
    
    const deleteButtons = document.querySelectorAll('.delete-question-btn');
    if (deleteButtons) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const questionId = this.getAttribute('data-question-id');
                document.getElementById('delete_question_id').value = questionId;
            });
        });
    }
});
</script>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>