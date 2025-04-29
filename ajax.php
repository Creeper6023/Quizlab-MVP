<?php
ob_start();

require_once(__DIR__."/lib/ai/DeepSeek.php");
require_once(__DIR__."/lib/ai/ai_prompts.php");
require_once(__DIR__."/config.php");

function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";

    if ($data !== null) {
        $log_message .= ": " . print_r($data, true);
    }

    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

$prompt = "你是一個專業的中文老師";

if ((isset($_POST['action']) && $_POST['action'] === 'test_ai_grading') ||
    (isset($_POST['method']) && in_array($_POST['method'], ['send_message', 'similar_question', 'new_question', 'new_questions']))) {
    unset($_SESSION['ai']);
    if (!isset($_SESSION['ai'])) {
        try {
            $_SESSION['ai'] = create_ai();
            $_SESSION['ai']->new_chat($prompt);
            debug_log("AI session initialized successfully");
        } catch (Exception $e) {
            debug_log("Error initializing AI session", $e->getMessage());
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action'])) {
    $action = $_GET['action'];
    debug_log("Processing GET request with action", $action);

    if ($action === 'get_users') {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        $db = new Database();
        $users = $db->getAllUsers();

        header('Content-Type: application/json');
        echo json_encode($users);

        ob_end_flush();
        exit;
    }
}
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    debug_log("Processing POST request with action", $action);

    if ($action === 'share_quiz' && isLoggedIn() && (hasRole(ROLE_ADMIN) || hasRole(ROLE_TEACHER))) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        $db = new Database();
        $quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        $shared_with = isset($_POST['shared_with']) ? (int)$_POST['shared_with'] : 0;
        $can_edit = isset($_POST['can_edit']) ? (int)$_POST['can_edit'] : 0;
        $shared_by = $_SESSION['user_id'];

        debug_log("Processing share_quiz request", [
            'quiz_id' => $quiz_id, 
            'shared_with' => $shared_with,
            'can_edit' => $can_edit
        ]);

        if ($quiz_id <= 0 || $shared_with <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid quiz or user.']);
            ob_end_flush();
            exit;
        }

        $quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);
        if (!$quiz) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Quiz not found.']);
            ob_end_flush();
            exit;
        }

        $teacher = $db->single("SELECT * FROM users WHERE id = ? AND role = ?", [$shared_with, ROLE_TEACHER]);
        if (!$teacher) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Selected user is not a teacher.']);
            ob_end_flush();
            exit;
        }

        $existing = $db->single(
            "SELECT * FROM quiz_shares WHERE quiz_id = ? AND shared_by = ? AND shared_with = ?", 
            [$quiz_id, $shared_by, $shared_with]
        );

        if ($existing) {
            $db->query(
                "UPDATE quiz_shares SET can_edit = ?, shared_at = CURRENT_TIMESTAMP WHERE id = ?", 
                [$can_edit, $existing['id']]
            );
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Share updated successfully.']);
            ob_end_flush();
            exit;
        } else {
            $db->query(
                "INSERT INTO quiz_shares (quiz_id, shared_by, shared_with, can_edit) VALUES (?, ?, ?, ?)", 
                [$quiz_id, $shared_by, $shared_with, $can_edit]
            );
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Quiz shared successfully.']);
            ob_end_flush();
            exit;
        }
    }

    if ($action === 'remove_quiz_share' && isLoggedIn() && (hasRole(ROLE_ADMIN) || hasRole(ROLE_TEACHER))) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        $db = new Database();
        $share_id = isset($_POST['share_id']) ? (int)$_POST['share_id'] : 0;
        $user_id = $_SESSION['user_id'];

        debug_log("Processing remove_quiz_share request", ['share_id' => $share_id]);

        if ($share_id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid share ID.']);
            ob_end_flush();
            exit;
        }

        $share = $db->single("SELECT * FROM quiz_shares WHERE id = ? AND shared_by = ?", [$share_id, $user_id]);
        if (!$share) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Share not found or you do not have permission to remove it.']);
            ob_end_flush();
            exit;
        }

        $db->query("DELETE FROM quiz_shares WHERE id = ?", [$share_id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Share removed successfully.']);
        ob_end_flush();
        exit;
    }

    if ($action === 'allow_retake' && isLoggedIn() && (hasRole(ROLE_ADMIN) || hasRole(ROLE_TEACHER))) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        $db = new Database();
        $quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

        debug_log("Processing allow_retake request", [
            'quiz_id' => $quiz_id,
            'student_id' => $student_id
        ]);

        if ($quiz_id <= 0 || $student_id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid quiz or student.']);
            ob_end_flush();
            exit;
        }

        $quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);
        if (!$quiz) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Quiz not found.']);
            ob_end_flush();
            exit;
        }

        $student = $db->single("SELECT * FROM users WHERE id = ? AND role = ?", [$student_id, ROLE_STUDENT]);
        if (!$student) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student not found.']);
            ob_end_flush();
            exit;
        }

        $db->query(
            "INSERT INTO quiz_retakes (quiz_id, student_id, granted_by, granted_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)",
            [$quiz_id, $student_id, $_SESSION['user_id']]
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Retake permission granted.']);
        ob_end_flush();
        exit;
    }

    if ($action === 'toggle_retakes' && isLoggedIn() && (hasRole(ROLE_ADMIN) || hasRole(ROLE_TEACHER))) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        $db = new Database();
        $quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        $allow_redo = isset($_POST['allow_retakes']) ? (int)$_POST['allow_retakes'] : 0;

        debug_log("Processing toggle_retakes request", [
            'quiz_id' => $quiz_id,
            'allow_redo' => $allow_redo
        ]);

        if ($quiz_id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid quiz.']);
            ob_end_flush();
            exit;
        }

        $quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);
        if (!$quiz) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Quiz not found.']);
            ob_end_flush();
            exit;
        }

        $db->query(
            "UPDATE quizzes SET allow_redo = ? WHERE id = ?",
            [$allow_redo, $quiz_id]
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Quiz retake setting updated.']);
        ob_end_flush();
        exit;
    }

    if ($action === 'test_ai_grading') {
        debug_log("Test AI grading request received");

        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        $subject = $_POST['subject'] ?? 'Science';
        $question = $_POST['question'] ?? '';
        $modelAnswer = $_POST['model_answer'] ?? '';
        $studentAnswer = $_POST['student_answer'] ?? '';

        debug_log("Test AI grading parameters", [
            'subject' => $subject,
            'question' => $question,
            'modelAnswer' => $modelAnswer,
            'studentAnswer' => $studentAnswer
        ]);

        $response = array();

        $similarity = similar_text($modelAnswer, $studentAnswer, $percent);
        $score = round($percent);
        $feedback = "Based on text similarity, your answer is {$score}% similar to the model answer.";

        if ($score < 40) {
            $feedback .= " Your answer appears to be substantially different from what was expected.";
        } else if ($score < 70) {
            $feedback .= " Your answer contains some relevant information but may be missing key details.";
        } else {
            $feedback .= " Your answer contains most of the expected information.";
        }

        $response['simple_result'] = array(
            'score' => $score,
            'feedback' => $feedback
        );

        debug_log("Simple result computed", $response['simple_result']);

        if (AI_GRADING_ENABLED) {
            debug_log("AI grading is enabled, proceeding with DeepSeek API");
            try {
                set_error_handler(function($errno, $errstr) {
                    throw new ErrorException($errstr, $errno);
                });

                $content = check_answer($subject, $question, $modelAnswer, $studentAnswer);
                debug_log("Prompt for AI", $content);

                $aiResponse = $_SESSION['ai']->send_message($content);
                debug_log("AI response received", $aiResponse);


                $decoded = json_decode($aiResponse, true);
                if (!$decoded || !isset($decoded['content'])) {
                    throw new Exception("Invalid AI response format");
                }


                $jsonContent = preg_replace('/^```json\s*|\s*```$/m', '', $decoded['content']);


                $result = json_decode($jsonContent, true);
                if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON in AI response content");
                }

                $response['result'] = $result;

                restore_error_handler();
            } catch (Exception $e) {
                debug_log("Error with DeepSeek API", $e->getMessage());
                $response['error'] = "Error with AI grading: " . $e->getMessage();
            }
        } else {
            debug_log("AI grading is disabled");
            $response['result'] = null;
        }

        debug_log("Response prepared", $response);

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_start();

        header('Content-Type: application/json');
        echo json_encode($response);

        ob_end_flush();
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $method = htmlspecialchars($_POST['method'] ?? '');

    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_start();

    header('Content-Type: application/json');

    if ($method == "send_message") 
    {
        $subject = htmlspecialchars($_POST['subject']);
        $question = htmlspecialchars($_POST['question']);
        $model_answer = htmlspecialchars($_POST['model_answer']);
        $student_input = htmlspecialchars($_POST['student_input']);
        $content = check_answer($subject, $question, $model_answer, $student_input);

        debug_log("Processing send_message request", ['subject' => $subject]);
        $response = $_SESSION['ai']->send_message($content);
        debug_log("AI response received", $response);

        echo $response;
    } 
    else if ($method == "similar_question") 
    {
        $subject = htmlspecialchars($_POST['subject']);
        $question = htmlspecialchars($_POST['question']);
        $model_answer = htmlspecialchars($_POST['model_answer']);
        $student_input = htmlspecialchars($_POST['student_input']);
        $content = similar_question($subject, $question, $model_answer, $student_input);

        debug_log("Processing similar_question request");
        $response = $_SESSION['ai']->send_message($content);
        echo $response;
    } 
    else if ($method == "new_question") 
    {
        $subject = htmlspecialchars($_POST['subject']);
        $question = htmlspecialchars($_POST['question']);
        $model_answer = htmlspecialchars($_POST['model_answer']);
        $student_input = htmlspecialchars($_POST['student_input']);
        $content = new_question($subject, $question, $model_answer, $student_input);

        debug_log("Processing new_question request");
        $response = $_SESSION['ai']->send_message($content);
        echo $response;
    } 
    else if ($method == "new_questions")
    {
        $subject = htmlspecialchars($_POST['subject']);
        $keywords = htmlspecialchars($_POST['keywords']);
        $count = htmlspecialchars($_POST['count']);
        $content = new_questions($subject, $keywords, $count);

        debug_log("Processing new_questions request");
        $response = $_SESSION['ai']->send_message($content);
        echo $response;
    }
    else if ($method == "new_chat") 
    {
        debug_log("Processing new_chat request");
        $_SESSION['ai']->new_chat($prompt);
        echo "OK";
    } 
    else if (empty($method) && !isset($_POST['action'])) {
        echo "no method provided";
    }

    ob_end_flush();
}
?>