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
    SELECT q.* FROM quizzes q
    LEFT JOIN quiz_shares qs ON q.id = qs.quiz_id AND qs.shared_with_id = ?
    WHERE q.id = ? AND (q.created_by = ? OR qs.shared_with_id = ?)
", [$teacher_id, $quiz_id, $teacher_id, $teacher_id]);

if (!$quiz) {

    set_flash_message('error', 'You do not have permission to view this quiz\'s results.');
    redirect(BASE_URL . '/teacher');
    exit();
}


$attempts = $db->resultSet("
    SELECT qa.id, qa.student_id, qa.start_time, qa.end_time, qa.status, qa.total_score,
           u.username as student_name
    FROM quiz_attempts qa
    JOIN users u ON qa.student_id = u.id
    WHERE qa.quiz_id = ?
    ORDER BY qa.start_time DESC
", [$quiz_id]);


$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : null;
$selectedAttempt = null;
$answers = [];

if ($attempt_id) {
    $selectedAttempt = $db->single("
        SELECT qa.*, u.username as student_name
        FROM quiz_attempts qa
        JOIN users u ON qa.student_id = u.id
        WHERE qa.id = ? AND qa.quiz_id = ?
    ", [$attempt_id, $quiz_id]);

    if ($selectedAttempt) {

        $answers = $db->resultSet("
            SELECT a.*, q.question_text, q.model_answer, q.points as max_points
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.attempt_id = ?
            ORDER BY a.id ASC
        ", [$attempt_id]);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="quiz-results">
    <div class="quiz-results-header">
        <h2>Quiz Results: <?= htmlspecialchars($quiz['title']) ?></h2>
        <div class="quiz-actions">
            <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $quiz_hash_id ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i> Edit Quiz
            </a>
            <a href="<?= BASE_URL ?>/teacher" class="btn btn-sm btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="quiz-results-content">
        <!-- Group attempts by student and eliminate duplicates for in-progress quizzes -->
        <?php

        $groupedAttempts = [];
        $latestStudentAttempts = [];
        
        foreach ($attempts as $attempt) {
            $studentId = $attempt['student_id'];
            

            if ($attempt['status'] === 'completed') {
                if (!isset($groupedAttempts[$studentId])) {
                    $groupedAttempts[$studentId] = [];
                }
                $groupedAttempts[$studentId][] = $attempt;
            } else {

                if (!isset($latestStudentAttempts[$studentId]) || 
                    strtotime($attempt['start_time']) > strtotime($latestStudentAttempts[$studentId]['start_time'])) {
                    $latestStudentAttempts[$studentId] = $attempt;
                }
            }
        }
        

        foreach ($latestStudentAttempts as $studentId => $attempt) {
            if (!isset($groupedAttempts[$studentId])) {
                $groupedAttempts[$studentId] = [];
            }
            array_unshift($groupedAttempts[$studentId], $attempt);
        }
        

        $totalUniqueAttempts = 0;
        foreach ($groupedAttempts as $studentAttempts) {
            $totalUniqueAttempts += count($studentAttempts);
        }
        ?>
        
        <h3 class="mb-4">Student Attempts (<?= $totalUniqueAttempts ?>)</h3>
        <div class="attempts-list card">
            <div class="card-body">
                <?php if (count($groupedAttempts) > 0): ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 20px; margin-bottom: 20px;">
                        <?php foreach ($groupedAttempts as $studentId => $studentAttempts): ?>
                            <?php foreach ($studentAttempts as $attempt): ?>
                                <div style="flex: 1; min-width: 300px; max-width: calc(33.333% - 20px);">
                                    <div class="attempt-card card h-100 <?= $attempt_id == $attempt['id'] ? 'selected-card' : '' ?>">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0"><?= htmlspecialchars($attempt['student_name']) ?></h5>
                                            <?php if ($attempt['status'] === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                                <span class="badge bg-warning">In Progress</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= ucfirst($attempt['status']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <div class="attempt-info">
                                                <div class="info-row">
                                                    <span class="info-label"><i class="fas fa-calendar-alt"></i> Date:</span>
                                                    <span class="info-value"><?= date('M j, Y g:i a', strtotime($attempt['start_time'])) ?></span>
                                                </div>
                                                
                                                <?php if ($attempt['status'] === 'completed'): ?>
                                                    <div class="info-row">
                                                        <span class="info-label"><i class="fas fa-chart-pie"></i> Score:</span>
                                                        <span class="info-value">
                                                            <div class="score-pill"><?= $attempt['total_score'] ?>%</div>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <div class="d-flex justify-content-between">
                                                <a href="?id=<?= $quiz_hash_id ?>&attempt_id=<?= $attempt['id'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                                <?php if ($attempt['status'] === 'completed'): ?>
                                                <a href="<?= BASE_URL ?>/teacher/allow_retake.php?id=<?= $quiz_hash_id ?>&student_id=<?= $attempt['student_id'] ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-redo"></i> Manage Retakes
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No attempts yet. Students will appear here once they start taking this quiz.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selectedAttempt): ?>
            <div class="attempt-details mt-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Attempt Details</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 20px; margin-bottom: 20px;">
                            <div style="flex: 1; min-width: 300px; max-width: calc(33.333% - 20px);">
                                <div class="attempt-info-card card h-100">
                                    <div class="card-header bg-light">
                                        <h4 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Information</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="attempt-info">
                                            <div class="info-item mb-3 d-flex">
                                                <span class="info-label flex-shrink-0 me-2"><i class="fas fa-user me-2"></i>Student:</span>
                                                <span class="info-value fw-bold"><?= htmlspecialchars($selectedAttempt['student_name']) ?></span>
                                            </div>
                                            <div class="info-item mb-3 d-flex">
                                                <span class="info-label flex-shrink-0 me-2"><i class="fas fa-calendar-day me-2"></i>Started:</span>
                                                <span class="info-value"><?= date('M j, Y g:i a', strtotime($selectedAttempt['start_time'])) ?></span>
                                            </div>
                
                                            <?php if ($selectedAttempt['status'] === 'completed'): ?>
                                                <div class="info-item mb-3 d-flex">
                                                    <span class="info-label flex-shrink-0 me-2"><i class="fas fa-calendar-check me-2"></i>Completed:</span>
                                                    <span class="info-value"><?= date('M j, Y g:i a', strtotime($selectedAttempt['end_time'])) ?></span>
                                                </div>
                                                <div class="score-container text-center my-4">
                                                    <div class="score-circle 
                                                        <?php if ($selectedAttempt['total_score'] >= 90): ?>score-excellent
                                                        <?php elseif ($selectedAttempt['total_score'] >= 70): ?>score-good
                                                        <?php elseif ($selectedAttempt['total_score'] >= 50): ?>score-average
                                                        <?php else: ?>score-needs-improvement<?php endif; ?>">
                                                        <?= $selectedAttempt['total_score'] ?>%
                                                    </div>
                                                    <div class="score-label mt-2">Total Score</div>
                                                </div>
                                                <div class="retake-action text-center mt-4">
                                                    <a href="<?= BASE_URL ?>/teacher/allow_retake.php?id=<?= $quiz_hash_id ?>&student_id=<?= $selectedAttempt['student_id'] ?>" 
                                                       class="btn btn-primary">
                                                        <i class="fas fa-redo me-2"></i> Manage Retakes
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="info-item mb-3 d-flex">
                                                    <span class="info-label flex-shrink-0 me-2"><i class="fas fa-clock me-2"></i>Status:</span>
                                                    <span class="info-value">
                                                        <span class="badge bg-warning">In Progress</span>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($selectedAttempt['status'] === 'completed'): ?>
                            <div style="flex: 1; min-width: 300px; max-width: calc(33.333% - 20px);">
                                <div class="retake-settings-card card h-100">
                                    <div class="card-header bg-light">
                                        <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Quiz Settings</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php 

                                            $quizSettings = $db->single("
                                                SELECT allow_redo, max_retakes, exam_mode
                                                FROM quizzes
                                                WHERE id = ?
                                            ", [$quiz_id]);
                                            

                                            $studentRetakeCount = $db->single("
                                                SELECT COUNT(*) as count
                                                FROM quiz_attempts
                                                WHERE quiz_id = ? AND student_id = ? AND status = 'completed'
                                            ", [$quiz_id, $selectedAttempt['student_id']]);
                                            $retakeCount = $studentRetakeCount ? $studentRetakeCount['count'] : 0;
                                            

                                            $hasRetakePermission = $db->single("
                                                SELECT * FROM quiz_retakes
                                                WHERE quiz_id = ? AND student_id = ? AND used = 0
                                            ", [$quiz_id, $selectedAttempt['student_id']]);
                                            

                                            $examExitLogs = $db->resultSet("
                                                SELECT * FROM exam_exit_logs
                                                WHERE attempt_id = ?
                                                ORDER BY exit_time DESC
                                            ", [$attempt_id]);
                                        ?>
                                        
                                        <div class="setting-item mb-3">
                                            <div class="setting-label"><i class="fas fa-redo-alt me-2"></i>Retake Mode:</div>
                                            <div class="setting-value">
                                                <?php if ($quizSettings['allow_redo']): ?>
                                                    <span class="badge bg-success">Unlimited Retakes</span>
                                                <?php elseif ($quizSettings['max_retakes'] > 0): ?>
                                                    <span class="badge bg-info">Limited Retakes (<?= $quizSettings['max_retakes'] ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Retakes</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="setting-item mb-3">
                                            <div class="setting-label"><i class="fas fa-user-clock me-2"></i>Student Attempts:</div>
                                            <div class="setting-value"><?= $retakeCount ?> attempt(s)</div>
                                        </div>
                                        
                                        <div class="setting-item mb-3">
                                            <div class="setting-label"><i class="fas fa-key me-2"></i>Retake Permission:</div>
                                            <div class="setting-value">
                                                <?php if ($hasRetakePermission): ?>
                                                    <span class="badge bg-success">Granted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">None</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="setting-item mb-3">
                                            <div class="setting-label"><i class="fas fa-user-shield me-2"></i>Exam Mode:</div>
                                            <div class="setting-value">
                                                <?php if ($quizSettings['exam_mode']): ?>
                                                    <span class="badge bg-danger">Enabled</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Disabled</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add exam exits log column -->
                            <?php if ($quizSettings['exam_mode']): ?>
                            <div style="flex: 1; min-width: 300px; max-width: calc(33.333% - 20px);">
                                <div class="exam-logs-card card h-100">
                                    <div class="card-header bg-light">
                                        <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Exam Exit Logs</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($examExitLogs)): ?>
                                            <div class="exam-exit-logs">
                                                <div class="alert alert-warning mb-3">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <strong>Exam Mode:</strong> Student attempted to exit the exam <?= count($examExitLogs) ?> time(s).
                                                </div>
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Time</th>
                                                                <th>IP Address</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($examExitLogs as $index => $log): ?>
                                                                <tr>
                                                                    <td><?= $index + 1 ?></td>
                                                                    <td><?= date('M j, Y g:i:s a', strtotime($log['exit_time'])) ?></td>
                                                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-3">
                                                <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                                                <p>No exit attempts detected. Student completed the exam properly.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                
                        <?php if (count($answers) > 0): ?>
                            <div class="answers-list">
                                <h4 class="answers-section-title"><i class="fas fa-tasks me-2"></i>Answers</h4>
                
                                <?php foreach ($answers as $index => $answer): ?>
                                    <div class="answer-card card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Question <?= $index + 1 ?></h5>
                                            <?php if ($selectedAttempt['status'] === 'completed'): ?>
                                                <div class="answer-score">
                                                    <span class="score-badge
                                                        <?php if (($answer['score'] / $answer['max_points']) >= 0.9): ?>score-excellent
                                                        <?php elseif (($answer['score'] / $answer['max_points']) >= 0.7): ?>score-good
                                                        <?php elseif (($answer['score'] / $answer['max_points']) >= 0.5): ?>score-average
                                                        <?php else: ?>score-needs-improvement<?php endif; ?>">
                                                        <?= $answer['score'] ?> / <?= $answer['max_points'] ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                
                                        <div class="card-body">
                                            <div class="question-text card mb-4 w-100">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Question</h6>
                                                </div>
                                                <div class="card-body" style="font-size: 1.15rem; padding: 2rem; word-break: break-word; white-space: normal; overflow-wrap: break-word; text-align: left;">
                                                    <?= nl2br(htmlspecialchars($answer['question_text'])) ?>
                                                </div>
                                            </div>
                
                                            <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 20px; margin-bottom: 20px;">
                                                <!-- Student's Answer Card -->
                                                <div style="flex: 1; min-width: 300px; max-width: calc(33.333% - 20px);">
                                                    <div class="student-answer card h-100">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0"><i class="fas fa-pen me-2"></i>Student's Answer</h6>
                                                        </div>
                                                        <div class="card-body" style="padding: 2rem; font-size: 1.1rem; word-break: break-word; white-space: normal; overflow-wrap: break-word; text-align: left;">
                                                            <?= empty($answer['answer_text']) ? '<em>No answer provided</em>' : nl2br(htmlspecialchars($answer['answer_text'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Model Answer Card -->
                                                <div style="flex: 1; min-width: 300px; max-width: calc(33.333% - 20px);">
                                                    <div class="model-answer card h-100">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Model Answer</h6>
                                                        </div>
                                                        <div class="card-body" style="padding: 2rem; font-size: 1.1rem; word-break: break-word; white-space: normal; overflow-wrap: break-word; text-align: left;">
                                                            <?= nl2br(htmlspecialchars($answer['model_answer'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- AI Feedback Card -->
                                                <?php if ($selectedAttempt['status'] === 'completed' && !empty($answer['feedback'])): ?>
                                                <div style="flex: 1; min-width: 300px; max-width: calc(33.333% - 20px);">
                                                    <div class="feedback-card card h-100">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0"><i class="fas fa-robot me-2"></i>AI Feedback</h6>
                                                        </div>
                                                        <div class="card-body" style="padding: 2rem; font-size: 1.1rem; word-break: break-word; white-space: normal; overflow-wrap: break-word; text-align: left;">
                                                            <?= nl2br(htmlspecialchars($answer['feedback'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state card">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                                    <p class="lead">No answers have been submitted yet.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php elseif (count($attempts) > 0): ?>
            <div class="empty-state select-attempt mt-4 card">
                <div class="card-body text-center p-5">
                    <i class="fas fa-hand-pointer fa-3x mb-3 text-muted"></i>
                    <p class="lead">Select an attempt from the list above to view details.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>

.quiz-results {
    max-width: 2000px;
    margin: 0 auto;
    width: 98%;
}

.question-text {
    margin-bottom: 2rem !important;
}

.question-text .card-body {
    padding: 1.5rem;
    font-size: 1.1rem;
    word-break: break-word;
    white-space: normal;
    overflow-wrap: break-word;
    text-align: left;
}

.quiz-results-content {
    margin-top: 30px;
}

.attempt-details .card-body {
    padding: 1.5rem;
}

.attempt-info-card, .retake-settings-card, .exam-logs-card {
    height: 100%;
}

.attempt-info-card .card-body, 
.retake-settings-card .card-body, 
.exam-logs-card .card-body {
    padding: 1.5rem;
}

.exam-logs-card .table-responsive {
    max-height: 250px;
    overflow-y: auto;
}

.answer-comparison {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 3.5%;
    margin-bottom: 2.5rem;
    width: 100%;
    justify-content: center;
}

.answer-comparison .card {
    margin-bottom: 20px !important;
    flex: 0 0 30%;  
    width: 30%;
    min-width: 280px;
    max-width: 450px;
}

.answer-comparison .card-body {
    padding: 2rem;
    font-size: 1.1rem;
    min-height: 200px;
    max-height: none;
    overflow-y: visible;
}

.attempts-list .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,0.125);
}

.attempt-card {
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid #dee2e6;
    min-width: 280px;
}

.attempt-card .card-body {
    padding: 1.25rem;
}

.attempt-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.selected-card {
    border: 2px solid #4a89dc !important;
    box-shadow: 0 5px 15px rgba(74,137,220,0.2) !important;
}

.info-row {
    display: flex;
    margin-bottom: 10px;
    align-items: center;
}

.info-label {
    color: #6c757d;
    margin-right: 10px;
    font-weight: 500;
}

.info-value {
    font-weight: normal;
}

.score-pill {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 30px;
    font-weight: bold;
    background-color: #4a89dc;
    color: white;
}


.score-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.score-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: bold;
    color: white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.score-label {
    font-size: 16px;
    font-weight: 500;
    color: #6c757d;
}

.score-excellent {
    background: linear-gradient(135deg, #4caf50, #2e7d32);
}

.score-good {
    background: linear-gradient(135deg, #2196f3, #1565c0);
}

.score-average {
    background: linear-gradient(135deg, #ff9800, #e65100);
}

.score-needs-improvement {
    background: linear-gradient(135deg, #f44336, #b71c1c);
}


.answer-card {
    margin-bottom: 35px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    width: 100%;
}

.answer-card .card-body {
    padding: 2rem;
    font-size: 1.1rem;
    word-break: break-word;
    white-space: normal;
    overflow-wrap: break-word;
    text-align: left;
}

.answers-section-title {
    margin: 30px 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
    color: #495057;
}

.answer-score {
    display: flex;
    align-items: center;
}

.score-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    color: white;
}

.setting-item {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.setting-label {
    font-weight: 500;
    margin-bottom: 5px;
    color: #495057;
}

.setting-value {
    font-weight: normal;
}


.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 50px 20px;
    text-align: center;
    background-color: #f8f9fa;
    border-radius: 8px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
    color: #adb5bd;
}

.empty-state p {
    font-size: 18px;
    max-width: 400px;
    margin: 0 auto;
}


@media (max-width: 768px) {
    .score-circle {
        width: 80px;
        height: 80px;
        font-size: 22px;
    }
    
    .answer-comparison {
        flex-direction: column;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>