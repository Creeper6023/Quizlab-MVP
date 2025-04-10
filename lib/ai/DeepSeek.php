<?php
require_once __DIR__ . '/../../config.php';

// Debug function for logging
function ai_debug_log($message, $data = null) {
    $log_file = __DIR__ . '/../../ai_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_message .= ": " . json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $log_message .= ": " . $data;
        }
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

class DeepSeek {
    private string $api_key;
    private string $model;
    private string $history_path;
    private array $messages;
    // Declare properties to avoid dynamic property deprecation warnings
    private $curl_options = [];
    private $data = [];
    
    public function __construct($api_key_override = null, $model = 'deepseek-chat') {
        ai_debug_log("Initializing DeepSeek class");
        
        // Get API key from database settings if available
        $db = new Database();
        $db_api_key = $db->getSetting('deepseek_api_key');
        
        // Priority: 1. Override key 2. Database key 3. Config constant
        if (!empty($api_key_override)) {
            $this->api_key = $api_key_override;
            ai_debug_log("Using override API key");
        } else if (!empty($db_api_key)) {
            $this->api_key = $db_api_key;
            ai_debug_log("Using database API key");
        } else if (defined('DEEPSEEK_API_KEY')) {
            $this->api_key = DEEPSEEK_API_KEY;
            ai_debug_log("Using constant API key from config.php");
        } else {
            $error = "DeepSeek API key not found in database or constants";
            ai_debug_log($error);
            throw new Exception($error);
        }
        
        if (empty($this->api_key)) {
            $error = "DeepSeek API key is empty";
            ai_debug_log($error);
            throw new Exception($error);
        }
        
        // Validate API key format (basic check)
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $this->api_key)) {
            $error = "DeepSeek API key format appears invalid";
            ai_debug_log($error);
            throw new Exception($error);
        }
        
        $this->model = $model;
        $this->history_path = sys_get_temp_dir() . '/history.json';
        $this->messages = array();
        
        ai_debug_log("DeepSeek class initialized successfully", ['model' => $this->model]);
    }
    
    public function new_chat($system_prompt = "You are a helpful AI assistant.") {
        ai_debug_log("Starting new chat with system prompt", $system_prompt);
        $this->messages = array();
        $this->messages[] = array('role' => 'system', 'content' => $system_prompt);
        return true;
    }
    
    public function send_message($message) {
        ai_debug_log("Sending message to DeepSeek API", $message);
        
        // Check if API key is available
        if (empty($this->api_key)) {
            $error = "API key is not set";
            ai_debug_log($error);
            return json_encode(['error' => $error]);
        }
        
        // Add user message to history
        $this->messages[] = array('role' => 'user', 'content' => $message);
        
        // Prepare the API request
        $data = array(
            'model' => 'deepseek-chat',
            'messages' => $this->messages,
            'max_tokens' => 1024,
            'temperature' => 0.2,
            'stream' => false
        );
        
        ai_debug_log("API Request data", $data);
        
        $json_data = json_encode($data);
        
        // Initialize cURL session
        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
            'Content-Length: ' . strlen($json_data)
        ));
        
        // Execute cURL request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        // Log response details
        ai_debug_log("API Response HTTP Code", $http_code);
        if (!empty($curl_error)) {
            ai_debug_log("cURL Error", $curl_error);
        }
        
        // Close cURL session
        curl_close($ch);
        
        // Check for cURL errors
        if ($response === false) {
            $error = "cURL error: " . $curl_error;
            ai_debug_log($error);
            return json_encode(['error' => $error]);
        }
        
        // Process API response
        $result = json_decode($response, true);
        ai_debug_log("API Response", $result);
        
        // Check for API errors
        if (isset($result['error'])) {
            $error = "API Error: " . json_encode($result['error']);
            ai_debug_log($error);
            return json_encode(['error' => $error]);
        }
        
        // Check for unexpected response format
        if (!isset($result['choices'][0]['message']['content'])) {
            $error = "Unexpected API response format: " . $response;
            ai_debug_log($error);
            return json_encode(['error' => $error]);
        }
        
        // Extract the assistant's response
        $assistant_message = $result['choices'][0]['message']['content'];
        
        // Add assistant response to history
        $this->messages[] = array('role' => 'assistant', 'content' => $assistant_message);
        
        ai_debug_log("Successful response received", $assistant_message);
        return json_encode(['content' => $assistant_message]);
    }
}

function create_ai() {
    ai_debug_log("Creating AI instance");
    try {
        return new DeepSeek();
    } catch (Exception $e) {
        ai_debug_log("Error creating AI instance", $e->getMessage());
        throw $e;
    }
}
?>
