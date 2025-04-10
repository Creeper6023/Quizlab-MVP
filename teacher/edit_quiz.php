<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/database/db.php';

// Check if user is a teacher
if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the quiz exists and belongs to this teacher
$quiz = $db->single("
    SELECT * FROM quizzes 
    WHERE id = ? AND created_by = ?
", [$quiz_id, $teacher_id]);

if (!$quiz) {
    // Quiz doesn't exist or doesn't belong to this teacher
    redirect(BASE_URL . '/teacher');
}

// Get questions for this quiz
$questions = $db->resultSet("
    SELECT * FROM questions 
    WHERE quiz_id = ? 
    ORDER BY id ASC
", [$quiz_id]);

//Get students for this quiz
$quizStudents = $db->resultSet("SELECT student_id FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
$studentIds = array_map(fn($student) => $student['student_id'], $quizStudents);

// Handle form submissions
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quiz'])) {
        // Update quiz details
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $allow_redo = isset($_POST['allow_redo']) ? 1 : 0;
        $selected_students = $_POST['selected_students'] ?? [];

        if (empty($title)) {
            $formError = 'Quiz title is required';
        } else {
            $db->query(
                "UPDATE quizzes SET title = ?, description = ?, allow_redo = ? WHERE id = ?",
                [$title, $description, $allow_redo, $quiz_id]
            );

            // Update student access
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

            // Refresh quiz data
            $quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);
        }
    } elseif (isset($_POST['add_question'])) {
        // Add a new question
        $question_text = trim($_POST['question_text'] ?? '');
        $model_answer = trim($_POST['model_answer'] ?? '');
        $points = (int)($_POST['points'] ?? 10);

        if (empty($question_text) || empty($model_answer)) {
            $formError = 'Question text and model answer are required';
        } else {
            $db->query(
                "INSERT INTO questions (quiz_id, question_text, model_answer, points) VALUES (?, ?, ?, ?)",
                [$quiz_id, $question_text, $model_answer, $points]
            );

            $formSuccess = 'Question added successfully!';

            // Refresh questions
            $questions = $db->resultSet("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", [$quiz_id]);
        }
    } elseif (isset($_POST['update_question'])) {
        // Update existing question
        $question_id = (int)($_POST['question_id'] ?? 0);
        $question_text = trim($_POST['question_text'] ?? '');
        $model_answer = trim($_POST['model_answer'] ?? '');
        $points = (int)($_POST['points'] ?? 10);

        if (empty($question_text) || empty($model_answer)) {
            $formError = 'Question text and model answer are required';
        } else {
            $db->query(
                "UPDATE questions SET question_text = ?, model_answer = ?, points = ? WHERE id = ? AND quiz_id = ?",
                [$question_text, $model_answer, $points, $question_id, $quiz_id]
            );

            $formSuccess = 'Question updated successfully!';

            // Refresh questions
            $questions = $db->resultSet("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", [$quiz_id]);
        }
    } elseif (isset($_POST['delete_question'])) {
        // Delete question
        $question_id = (int)($_POST['question_id'] ?? 0);

        $db->query(
            "DELETE FROM questions WHERE id = ? AND quiz_id = ?",
            [$question_id, $quiz_id]
        );

        $formSuccess = 'Question deleted successfully!';

        // Refresh questions
        $questions = $db->resultSet("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", [$quiz_id]);
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
            <?php if ($quiz['is_published']): ?>
                <a href="<?= BASE_URL ?>/teacher/unpublish_quiz.php?id=<?= $quiz_id ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-eye-slash"></i> Unpublish
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/teacher/publish_quiz.php?id=<?= $quiz_id ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-check-circle"></i> Publish
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz_id ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-chart-bar"></i> View Results
            </a>
        </div>
    </div>

    <?php if (!empty($formError)): ?>
        <div class="alert alert-danger"><?= $formError ?></div>
    <?php endif; ?>

    <?php if (!empty($formSuccess)): ?>
        <div class="alert alert-success"><?= $formSuccess ?></div>
    <?php endif; ?>

    <div class="quiz-editor-content">
        <div class="quiz-details-section">
            <h3>Quiz Details</h3>
            <form method="POST" action="" class="quiz-form">
                <div class="form-group">
                    <label for="title">Quiz Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($quiz['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="allow_redo">Allow Redo</label>
                    <input type="checkbox" id="allow_redo" name="allow_redo" <?= $quiz['allow_redo'] ? 'checked' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="selected_students">Select Students</label>
                    <select id="selected_students" name="selected_students[]" multiple>
                        <?php 
                        //Fetch all students here. Replace with your actual student fetching logic.
                        $students = $db->resultSet("SELECT id, name FROM users WHERE role = ?", [ROLE_STUDENT]);
                        foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>" <?= in_array($student['id'], $studentIds) ? 'selected' : '' ?>><?= $student['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" name="update_quiz" class="btn btn-primary">Update Quiz</button>
                </div>
            </form>
        </div>

        <div class="quiz-questions-section">
            <h3>Questions (<?= count($questions) ?>)</h3>

            <div class="add-question-form">
                <h4>Add New Question</h4>
                <form method="POST" action="" class="question-form">
                    <div class="form-group">
                        <label for="question_text">Question</label>
                        <textarea id="question_text" name="question_text" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="model_answer">Model Answer</label>
                        <textarea id="model_answer" name="model_answer" rows="5" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="points">Points (1-100)</label>
                        <input type="number" id="points" name="points" min="1" max="100" value="10" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
                    </div>
                </form>
            </div>

            <?php if (count($questions) > 0): ?>
                <div class="question-list">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <h4>Question <?= $index + 1 ?> (<?= $question['points'] ?> points)</h4>
                                <div class="question-actions">
                                    <button class="btn btn-sm btn-outline edit-question-btn" data-id="<?= $question['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" action="" class="delete-question-form" onsubmit="return confirm('Are you sure you want to delete this question?');">
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

                            <!-- Hidden edit form - shown with JavaScript -->
                            <div class="edit-question-form" id="edit-form-<?= $question['id'] ?>" style="display: none;">
                                <form method="POST" action="" class="question-form">
                                    <input type="hidden" name="question_id" value="<?= $question['id'] ?>">

                                    <div class="form-group">
                                        <label for="edit_question_text_<?= $question['id'] ?>">Question</label>
                                        <textarea id="edit_question_text_<?= $question['id'] ?>" name="question_text" rows="3" required><?= htmlspecialchars($question['question_text']) ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="edit_model_answer_<?= $question['id'] ?>">Model Answer</label>
                                        <textarea id="edit_model_answer_<?= $question['id'] ?>" name="model_answer" rows="5" required><?= htmlspecialchars($question['model_answer']) ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="edit_points_<?= $question['id'] ?>">Points (1-100)</label>
                                        <input type="number" id="edit_points_<?= $question['id'] ?>" name="points" min="1" max="100" value="<?= $question['points'] ?>" required>
                                    </div>

                                    <div class="form-group edit-actions">
                                        <button type="submit" name="update_question" class="btn btn-primary">Update Question</button>
                                        <button type="button" class="btn btn-outline cancel-edit-btn" data-id="<?= $question['id'] ?>">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-question-circle"></i>
                    <p>No questions yet. Add your first question using the form above!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// JavaScript to handle edit question UI
document.addEventListener('DOMContentLoaded', function() {
    // Edit question button click
    const editButtons = document.querySelectorAll('.edit-question-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-id');
            const editForm = document.getElementById(`edit-form-${questionId}`);
            const questionCard = this.closest('.question-card');
            const questionBody = questionCard.querySelector('.question-body');

            questionBody.style.display = 'none';
            editForm.style.display = 'block';
        });
    });

    // Cancel edit button click
    const cancelButtons = document.querySelectorAll('.cancel-edit-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-id');
            const editForm = document.getElementById(`edit-form-${questionId}`);
            const questionCard = this.closest('.question-card');
            const questionBody = questionCard.querySelector('.question-body');

            editForm.style.display = 'none';
            questionBody.style.display = 'block';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>