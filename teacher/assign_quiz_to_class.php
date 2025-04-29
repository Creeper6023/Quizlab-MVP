<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config.php';


if (!function_exists('set_flash_message')) {
    function set_flash_message($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}


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

if (!$quiz) {

    $sharedQuiz = $db->single("
        SELECT q.* FROM quizzes q
        JOIN quiz_shares qs ON q.id = qs.quiz_id 
        WHERE q.id = ? AND qs.shared_with_id = ? 
        AND (qs.permission_level = 'edit' OR qs.permission_level = 'full')
    ", [$quiz_id, $teacher_id]);
    
    if (!$sharedQuiz) {
        set_flash_message('error', 'You do not have permission to assign this quiz.');
        redirect(BASE_URL . '/teacher');
        exit();
    }
    

    $quiz = $sharedQuiz;
}


$classes = $db->resultSet("
    SELECT c.* FROM classes c
    WHERE c.created_by = ?
    ORDER BY c.name
", [$teacher_id]);


$currentAssignments = $db->resultSet("
    SELECT class_id FROM class_quiz_assignments
    WHERE quiz_id = ?
", [$quiz_id]);

$assignedClassIds = [];
foreach ($currentAssignments as $assignment) {
    $assignedClassIds[] = $assignment['class_id'];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assignments'])) {

    $selectedClasses = isset($_POST['class_ids']) ? $_POST['class_ids'] : [];
    $selectedStudents = isset($_POST['selected_students']) ? $_POST['selected_students'] : [];
    

    $db->query("DELETE FROM class_quiz_assignments WHERE quiz_id = ?", [$quiz_id]);
    

    if (!empty($selectedClasses)) {
        $placeholders = rtrim(str_repeat('(?,?),', count($selectedClasses)), ',');
        $params = [];
        
        foreach ($selectedClasses as $class_id) {
            $params[] = $class_id;
            $params[] = $quiz_id;
        }
        
        $db->query(
            "INSERT INTO class_quiz_assignments (class_id, quiz_id) VALUES $placeholders",
            $params
        );
    }
    

    $db->query("DELETE FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
    

    if (!empty($selectedStudents)) {
        $placeholders = rtrim(str_repeat('(?,?),', count($selectedStudents)), ',');
        $params = [];
        
        foreach ($selectedStudents as $student_id) {
            $params[] = $quiz_id;
            $params[] = $student_id;
        }
        
        $db->query(
            "INSERT INTO quiz_student_access (quiz_id, student_id) VALUES $placeholders",
            $params
        );
    }
    
    set_flash_message('success', 'Quiz assignments updated successfully.');
    redirect(BASE_URL . '/teacher/edit_quiz.php?id=' . $quiz_hash_id);
    exit();
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Assign Quiz to Classes & Students</h2>
                    <div>
                        <?php if ($quiz['is_published']): ?>
                            <a href="<?= BASE_URL ?>/teacher/unpublish_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-eye-slash"></i> Unpublish
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/teacher/publish_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check-circle"></i> Publish
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <h3 class="h5 mb-3">Quiz: <?= htmlspecialchars($quiz['title']) ?></h3>
                    
                    <?php if(isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash_message']['type'] === 'error' ? 'danger' : $_SESSION['flash_message']['type'] ?>">
                            <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Assign to Classes:</label>
                                    <?php if (!empty($classes)): ?>
                                        <div class="border p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                            <?php foreach ($classes as $class): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="class_ids[]" 
                                                        value="<?= $class['id'] ?>" 
                                                        id="class_<?= $class['id'] ?>"
                                                        <?= in_array($class['id'], $assignedClassIds) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="class_<?= $class['id'] ?>">
                                                        <?= htmlspecialchars($class['name']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <p>You don't have any classes yet. Please create a class first.</p>
                                            <a href="<?= BASE_URL ?>/teacher/classes/create_class.php" class="btn btn-sm btn-primary mt-2">
                                                <i class="fas fa-plus me-1"></i> Create Class
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="selected_students" class="form-label">Assign to Individual Students:</label>
                                    <?php 

                                    $quizStudents = $db->resultSet("SELECT student_id FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
                                    $studentIds = array_map(fn($student) => $student['student_id'], $quizStudents);
                                    

                                    $students = $db->resultSet("SELECT id, username, name FROM users WHERE role = ? ORDER BY name, username", [ROLE_STUDENT]);
                                    ?>
                                    
                                    <select id="selected_students" name="selected_students[]" multiple class="form-select" style="height: 300px;">
                                        <?php foreach ($students as $student): 
                                            $display_name = !empty($student['name']) ? $student['name'] . ' (' . $student['username'] . ')' : $student['username'];
                                        ?>
                                            <option value="<?= $student['id'] ?>" <?= in_array($student['id'], $studentIds) ? 'selected' : '' ?>><?= htmlspecialchars($display_name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="mt-2">
                                        <small class="text-muted">Hold Ctrl (or Cmd on Mac) to select multiple students</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Quiz
                            </a>
                            <button type="submit" name="save_assignments" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Assignments
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once BASE_PATH . '/includes/footer.php';
?>