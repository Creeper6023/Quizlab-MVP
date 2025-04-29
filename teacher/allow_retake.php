<?php
require_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../includes/header.php';


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];


$quiz_hash_id = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['quiz_id']) ? $_GET['quiz_id'] : '');

if (empty($quiz_hash_id)) {
    redirect(BASE_URL . '/teacher/');
    exit();
}


$quiz_id = getIdFromHash('quizzes', $quiz_hash_id);

if (!$quiz_id) {
    redirect(BASE_URL . '/teacher/');
    exit();
}


$quiz = $db->single(
    "SELECT q.*, u.username as creator_username,
            CASE WHEN q.created_by = ? THEN 1 ELSE 0 END as is_owner
     FROM quizzes q
     JOIN users u ON q.created_by = u.id
     WHERE q.id = ?",
    [$teacher_id, $quiz_id]
);


if (!$quiz) {
    $quiz = $db->single(
        "SELECT q.*, u.username as creator_username, 0 as is_owner
         FROM quizzes q
         JOIN users u ON q.created_by = u.id
         JOIN quiz_shares qs ON q.id = qs.quiz_id
         WHERE q.id = ? AND qs.shared_with_id = ?",
        [$quiz_id, $teacher_id]
    );
}

if (!$quiz) {
    redirect(BASE_URL . '/teacher/');
    exit();
}


$students = $db->resultSet(
    "SELECT u.id, u.username, u.name, MAX(qa.total_score) as best_score, COUNT(qa.id) as attempt_count
     FROM users u
     JOIN quiz_attempts qa ON u.id = qa.student_id
     WHERE qa.quiz_id = ? AND qa.status = 'completed'
     GROUP BY u.id
     ORDER BY u.username ASC",
    [$quiz_id]
);


$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';


if ($isAjax) {

    header('Content-Type: application/json');
    

    ob_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents(__DIR__ . '/../debug.log', '[' . date('Y-m-d H:i:s') . '] POST request received: ' . 
                     print_r($_POST, true) . PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/../debug.log', '[' . date('Y-m-d H:i:s') . '] Headers: ' . 
                     print_r(getallheaders(), true) . PHP_EOL, FILE_APPEND);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_retake_settings'])) {

    file_put_contents(__DIR__ . '/../debug.log', '[' . date('Y-m-d H:i:s') . '] Update retake settings POST branch reached: ' . 
                     print_r($_POST, true) . PHP_EOL, FILE_APPEND);
    

    $retake_mode = isset($_POST['retake_mode']) ? $_POST['retake_mode'] : 'none';
    

    $allow_redo = ($retake_mode === 'unlimited') ? 1 : 0;
    

    $exam_mode = isset($_POST['exam_mode']) && $_POST['exam_mode'] == 1 ? 1 : 0;
    

    if (!isset($quiz['exam_mode'])) {
        include_once __DIR__ . '/../add_exam_mode_column.php';
    }
    

    $db->query(
        "UPDATE quizzes SET allow_redo = ?, exam_mode = ? WHERE id = ?",
        [$allow_redo, $exam_mode, $quiz_id]
    );
    
    if ($isAjax) {

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
        exit();
    } else {

        redirect(BASE_URL . "/teacher/allow_retake.php?id=$quiz_hash_id&updated=1");
        exit();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_retakes'])) {

    $selectedStudentIds = isset($_POST['retake_students']) ? $_POST['retake_students'] : [];
    

    $allStudents = $db->resultSet(
        "SELECT DISTINCT student_id 
         FROM quiz_attempts 
         WHERE quiz_id = ? AND status = 'completed'",
        [$quiz_id]
    );
    
    $allStudentIds = array_map(function($s) { return $s['student_id']; }, $allStudents);
    

    $db->query(
        "DELETE FROM quiz_retakes 
         WHERE quiz_id = ? AND used = 0 AND student_id NOT IN (" . implode(',', array_fill(0, count($selectedStudentIds) ?: 1, '?')) . ")",
        array_merge([$quiz_id], $selectedStudentIds ?: [0])
    );
    

    foreach ($selectedStudentIds as $studentId) {

        $existingPermission = $db->single(
            "SELECT * FROM quiz_retakes WHERE quiz_id = ? AND student_id = ? AND used = 0",
            [$quiz_id, $studentId]
        );
        
        if (!$existingPermission) {

            $db->query(
                "INSERT INTO quiz_retakes (quiz_id, student_id, granted_by, granted_at) 
                 VALUES (?, ?, ?, NOW())",
                [$quiz_id, $studentId, $teacher_id]
            );
        }
    }
    
    if ($isAjax) {

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Retake permissions updated successfully']);
        exit();
    } else {

        redirect(BASE_URL . "/teacher/allow_retake.php?id=$quiz_hash_id&retakes_updated=1");
        exit();
    }
}


$updated_message = '';
if (isset($_GET['updated']) && $_GET['updated'] == 1) {
    $updated_message = 'Quiz retake settings updated successfully.';
} elseif (isset($_GET['retakes_updated']) && $_GET['retakes_updated'] == 1) {
    $updated_message = 'Student retake permissions updated successfully.';
}


if ($isAjax) {

    $output = ob_get_clean();
    

    if (!empty($output)) {

        file_put_contents(__DIR__ . '/../debug.log', '[' . date('Y-m-d H:i:s') . '] AJAX Error caught: ' . 
                         $output . PHP_EOL, FILE_APPEND);
                         

        echo json_encode(['success' => false, 'message' => 'Error processing request', 'debug' => $output]);
        exit();
    }
}
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz_hash_id ?>">Quiz Results</a></li>
            <li class="breadcrumb-item active" aria-current="page">Manage Retakes</li>
        </ol>
    </nav>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>
    
    <?php if (!empty($updated_message)): ?>
        <div class="alert alert-success"><?= $updated_message ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Manage Retakes: <?= htmlspecialchars($quiz['title']) ?></h2>
            <div>
                <a href="<?= BASE_URL ?>/teacher/view_results.php?id=<?= $quiz_hash_id ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Results
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                You can grant individual students permission to retake this quiz, or enable unlimited retakes for all students.
            </div>
            
            <!-- Quiz Settings -->
            <div class="mb-4">
                <h4><i class="fas fa-cog me-2"></i>Quiz Settings</h4>
                <div class="card">
                    <div class="card-body">
                        <div id="settingsSaveStatus" class="alert alert-info mb-3" style="display:none;">
                            <i class="fas fa-spinner fa-spin me-2"></i> Saving settings...
                        </div>
                        
                        <form id="quizSettingsForm" method="POST" action="">
                            <input type="hidden" name="update_retake_settings" value="1">
                            
                            <!-- Retake Mode Options - Simplified to just No Retakes or Unlimited -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Retake Mode</label>
                                <div class="retake-mode-options">
                                    <?php

                                    $retake_mode = ($quiz['allow_redo'] == 1) ? 'unlimited' : 'none';
                                    ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input retake-mode-radio" type="radio" name="retake_mode" 
                                               id="retakeMode-none" value="none" <?= $retake_mode === 'none' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="retakeMode-none">
                                            <strong>No Retakes</strong> - Students can only take the quiz once
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input retake-mode-radio" type="radio" name="retake_mode" 
                                               id="retakeMode-unlimited" value="unlimited" <?= $retake_mode === 'unlimited' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="retakeMode-unlimited">
                                            <strong>Unlimited Retakes</strong> - Students can retake the quiz as many times as they want
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exam Mode Setting -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Exam Security</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="examModeSwitch" 
                                           name="exam_mode" value="1" <?= isset($quiz['exam_mode']) && $quiz['exam_mode'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="examModeSwitch">
                                        Enable Exam Mode
                                    </label>
                                </div>
                                <div class="ms-4">
                                    <p class="text-muted small mt-2">
                                        When enabled, students cannot leave or refresh the quiz page. 
                                        If they attempt to exit, you will be notified. This helps ensure 
                                        exam integrity and prevents unauthorized assistance.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary" id="saveSettingsBtn">
                                    <i class="fas fa-save me-2"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Individual Student Retakes -->
            <h4>Grant Individual Retake Permissions</h4>
            <?php if (count($students) > 0): ?>
                <div id="studentRetakeStatus" class="text-success mb-2" style="display:none;">
                    <i class="fas fa-check-circle"></i> Retake permissions saved automatically
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="student-retakes-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Best Score</th>
                                <th>Attempts</th>
                                <th>Allow Retake</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <?php 

                                $hasRetakePermission = $db->single(
                                    "SELECT * FROM quiz_retakes WHERE quiz_id = ? AND student_id = ? AND used = 0",
                                    [$quiz_id, $student['id']]
                                );
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($student['name'])): ?>
                                            <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['username']) ?>)
                                        <?php else: ?>
                                            <?= htmlspecialchars($student['username']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($student['best_score'], 1) ?>%</td>
                                    <td><?= $student['attempt_count'] ?></td>
                                    <td>
                                        <div class="form-check form-switch retake-switch">
                                            <input class="form-check-input student-retake-checkbox" type="checkbox" 
                                                   id="student-retake-<?= $student['id'] ?>" 
                                                   data-student-id="<?= $student['id'] ?>"
                                                   <?= $hasRetakePermission ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="student-retake-<?= $student['id'] ?>">
                                                Allow Retake
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No students have attempted this quiz yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>

.retake-switch {
    display: flex;
    justify-content: center;
}

.retake-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
}

.retake-switch .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.retake-switch .form-check-label {
    margin-left: 8px;
    cursor: pointer;
}


#student-retakes-form tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const tableRows = document.querySelectorAll('#student-retakes-table tbody tr');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {

            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;

                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });
    

    const retakeModeRadios = document.querySelectorAll('.retake-mode-radio');
    

    const urlParams = new URLSearchParams(window.location.search);
    const useTraditionalSubmit = urlParams.has('no-ajax');
    

    const quizSettingsForm = document.getElementById('quizSettingsForm');
    const settingsSaveStatus = document.getElementById('settingsSaveStatus');
    

    const currentUrl = window.location.href;
    

    if (useTraditionalSubmit) {

        console.log('Using traditional form submission (AJAX disabled)');
        

        const reloadWithAjaxBtn = document.createElement('button');
        reloadWithAjaxBtn.type = 'button';
        reloadWithAjaxBtn.className = 'btn btn-link';
        reloadWithAjaxBtn.textContent = 'Enable AJAX saving';
        reloadWithAjaxBtn.onclick = function() {

            const url = new URL(window.location.href);
            url.searchParams.delete('no-ajax');
            window.location.href = url.toString();
        };
        

        const saveBtn = document.getElementById('saveSettingsBtn');
        saveBtn.parentNode.insertBefore(reloadWithAjaxBtn, saveBtn.nextSibling);
    } else {

        const disableAjaxLink = document.createElement('div');
        disableAjaxLink.className = 'mt-2';
        disableAjaxLink.innerHTML = '<a href="' + currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'no-ajax=1" class="text-muted small">Use traditional form submission</a>';
        quizSettingsForm.appendChild(disableAjaxLink);
        

        quizSettingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            

            settingsSaveStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving settings...';
            settingsSaveStatus.className = 'alert alert-info mb-3';
            settingsSaveStatus.style.display = 'block';
            

            const formData = new FormData(quizSettingsForm);
            

            console.log('Form data being submitted via AJAX:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json' // Explicitly request JSON response
                }
            })
            .then(response => {
                console.log('Response status:', response.status);

                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response: ' + e.message);
                    }
                });
            })
            .then(data => {
                console.log('Parsed response:', data);
                if (data.success) {

                    settingsSaveStatus.innerHTML = '<i class="fas fa-check-circle me-2"></i> ' + (data.message || 'Settings saved successfully');
                    settingsSaveStatus.className = 'alert alert-success mb-3';
                    setTimeout(() => {
                        settingsSaveStatus.style.display = 'none';
                    }, 2000);
                    

                    window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'updated=1';
                } else {

                    throw new Error(data.message || 'Server returned an error');
                }
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                settingsSaveStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Error saving settings: ' + error.message + 
                                           '<br>Try <a href="' + currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'no-ajax=1">disabling AJAX</a>';
                settingsSaveStatus.className = 'alert alert-danger mb-3';
                

                console.log('Trying regular form submission as fallback');
                setTimeout(() => {
                    const fallbackSubmitBtn = document.createElement('button');
                    fallbackSubmitBtn.type = 'button';
                    fallbackSubmitBtn.className = 'btn btn-warning mt-2';
                    fallbackSubmitBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Try submitting without AJAX';
                    fallbackSubmitBtn.onclick = function() {
                        quizSettingsForm.removeEventListener('submit', arguments.callee);
                        quizSettingsForm.submit();
                    };
                    

                    settingsSaveStatus.appendChild(document.createElement('br'));
                    settingsSaveStatus.appendChild(fallbackSubmitBtn);
                }, 1000);
            });
        });
    }
});
    

    const studentRetakeCheckboxes = document.querySelectorAll('.student-retake-checkbox');
    const studentRetakeStatus = document.getElementById('studentRetakeStatus');
    

    const createStudentRetakeForm = (checkedIds) => {

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        form.style.display = 'none';
        

        const updateRetakesInput = document.createElement('input');
        updateRetakesInput.type = 'hidden';
        updateRetakesInput.name = 'update_retakes';
        updateRetakesInput.value = '1';
        form.appendChild(updateRetakesInput);
        

        checkedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'retake_students[]';
            input.value = id;
            form.appendChild(input);
        });
        

        document.body.appendChild(form);
        return form;
    };
    

    if (useTraditionalSubmit) {

        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'btn btn-primary mb-3';
        saveBtn.innerHTML = '<i class="fas fa-save me-2"></i> Save Student Retake Permissions';
        

        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.parentNode.insertBefore(saveBtn, tableContainer);
            

            saveBtn.addEventListener('click', function() {

                const checkedStudentIds = [];
                document.querySelectorAll('.student-retake-checkbox:checked').forEach(cb => {
                    checkedStudentIds.push(cb.dataset.studentId);
                });
                

                const form = createStudentRetakeForm(checkedStudentIds);
                form.submit();
            });
        }
    } else {

        studentRetakeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                studentRetakeStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                studentRetakeStatus.style.display = 'block';
                studentRetakeStatus.className = 'text-info mb-2';
                

                const checkedStudentIds = [];
                document.querySelectorAll('.student-retake-checkbox:checked').forEach(cb => {
                    checkedStudentIds.push(cb.dataset.studentId);
                });
                

                const formData = new FormData();
                formData.append('update_retakes', '1');
                checkedStudentIds.forEach(id => {
                    formData.append('retake_students[]', id);
                });
                

                console.log('Student retake permissions data:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
                

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Student retake permission response status:', response.status);

                    return response.text().then(text => {
                        console.log('Raw student retake response:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Error parsing JSON for student retake:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response: ' + e.message);
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed student retake response:', data);
                    if (data.success) {

                        studentRetakeStatus.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'Retake permissions saved successfully');
                        studentRetakeStatus.className = 'text-success mb-2';
                        setTimeout(() => {
                            studentRetakeStatus.style.display = 'none';
                        }, 2000);
                    } else {

                        throw new Error(data.message || 'Server returned an error');
                    }
                })
                .catch(error => {
                    console.error('Error saving retake permissions:', error);
                    studentRetakeStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error saving permissions: ' + error.message;
                    studentRetakeStatus.className = 'text-danger mb-2';
                    

                    const fallbackBtn = document.createElement('button');
                    fallbackBtn.type = 'button';
                    fallbackBtn.className = 'btn btn-warning btn-sm ms-2';
                    fallbackBtn.innerHTML = 'Try saving without AJAX';
                    fallbackBtn.onclick = function() {
                        const form = createStudentRetakeForm(checkedStudentIds);
                        form.submit();
                    };
                    
                    studentRetakeStatus.appendChild(fallbackBtn);
                });
            });
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>