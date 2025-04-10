<?php
require_once(__DIR__."/lib/ai/DeepSeek.php");
require_once(__DIR__."/lib/ai/ai_prompts.php");
require_once(__DIR__."/config.php");

// Debug function for logging
function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $log_message .= ": " . print_r($data, true);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Session is already started in config.php

$prompt = "你是一個專業的中文老師";

// Initialize AI session for actions that need it
if ((isset($_POST['action']) && $_POST['action'] === 'test_ai_grading') ||
    (isset($_POST['method']) && in_array($_POST['method'], ['send_message', 'similar_question', 'new_question', 'new_questions']))) {
    // Reset AI session for new requests
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

// Handle GET requests for user data
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action'])) {
    $action = $_GET['action'];
    debug_log("Processing GET request with action", $action);
    
    if ($action === 'get_users') {
        // This section handles the get_users action
        $db = new Database();
        $users = $db->getAllUsers();
        header('Content-Type: application/json');
        echo json_encode($users);
        exit;
    }
}

// Check if action parameter exists (for admin testing)
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    debug_log("Processing POST request with action", $action);
    
    // Handle test_ai_grading action
    if ($action === 'test_ai_grading') {
        debug_log("Test AI grading request received");
        $subject = htmlspecialchars($_POST['subject'] ?? 'Science');
        $question = htmlspecialchars($_POST['question'] ?? '');
        $modelAnswer = htmlspecialchars($_POST['model_answer'] ?? '');
        $studentAnswer = htmlspecialchars($_POST['student_answer'] ?? '');
        
        debug_log("Test AI grading parameters", [
            'subject' => $subject,
            'question' => $question,
            'modelAnswer' => $modelAnswer,
            'studentAnswer' => $studentAnswer
        ]);
        
        $response = array();
        
        // Always provide simple grading result
        $similarity = similar_text($modelAnswer, $studentAnswer, $percent);
        $score = round($percent);
        $feedback = "Based on text similarity, your answer is ${score}% similar to the model answer.";
        
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
        
        // If AI grading is enabled, add that result
        if (AI_GRADING_ENABLED) {
            debug_log("AI grading is enabled, proceeding with DeepSeek API");
            try {
                // Set error handling to throw exceptions
                set_error_handler(function($errno, $errstr) {
                    throw new ErrorException($errstr, $errno);
                });
                
                $content = check_answer($subject, $question, $modelAnswer, $studentAnswer);
                debug_log("Prompt for AI", $content);
                
                $aiResponse = $_SESSION['ai']->send_message($content);
                debug_log("AI response received", $aiResponse);
                
                // Validate JSON response
                if (json_decode($aiResponse) === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON response received from AI");
                }
                
                $response['result'] = $aiResponse;
                
                // Restore error handler
                restore_error_handler();
            } catch (Exception $e) {
                debug_log("Error with DeepSeek API", $e->getMessage());
                $response['error'] = "Error with AI grading: " . $e->getMessage();
            }
        } else {
            debug_log("AI grading is disabled");
            $response['result'] = null; // Indicate AI grading is disabled
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        debug_log("Response sent", $response);
        exit;
    }
}

// Handle original methods for POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $method = htmlspecialchars($_POST['method'] ?? '');
    if ($method == "send_message") 
    {
        $subject = htmlspecialchars($_POST['subject']);
        $question = htmlspecialchars($_POST['question']);
        $model_answer = htmlspecialchars($_POST['model_answer']);
        $student_input = htmlspecialchars($_POST['student_input']);
        $content = check_answer($subject, $question, $model_answer, $student_input);
        echo $_SESSION['ai']->send_message($content);
    } 
    else if ($method == "similar_question") 
    {
        $subject = htmlspecialchars($_POST['subject']);
        $question = htmlspecialchars($_POST['question']);
        $model_answer = htmlspecialchars($_POST['model_answer']);
        $student_input = htmlspecialchars($_POST['student_input']);
        $content = similar_question($subject, $question, $model_answer, $student_input);
        echo $_SESSION['ai']->send_message($content);
    } 
    else if ($method == "new_question") 
    {
        $subject = htmlspecialchars($_POST['subject']);
        $question = htmlspecialchars($_POST['question']);
        $model_answer = htmlspecialchars($_POST['model_answer']);
        $student_input = htmlspecialchars($_POST['student_input']);
        $content = new_question($subject, $question, $model_answer, $student_input);
        echo $_SESSION['ai']->send_message($content);
    } 
    else if ($method == "new_questions")
    {
        $subject = htmlspecialchars($_POST['subject']);
        $keywords = htmlspecialchars($_POST['keywords']);
        $count = htmlspecialchars($_POST['count']);
        $content = new_questions($subject, $keywords, $count);
        // echo $content;
        echo $_SESSION['ai']->send_message($content);
    }
    else if ($method == "new_chat") 
    {
        $_SESSION['ai']->new_chat($prompt);
        echo "OK";
    } 
    else if (empty($method) && !isset($_POST['action'])) {
        echo "no method provided";
    }
}
?>
