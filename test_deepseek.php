<?php
// Test script for DeepSeek integration
require_once(__DIR__."/config.php");
require_once(__DIR__."/lib/ai/DeepSeek.php");
require_once(__DIR__."/lib/ai/ai_prompts.php");

echo "========== DEEPSEEK API TEST ==========\n\n";

// 1. Check if API key is available
$db = new Database();
$api_key = $db->getSetting('deepseek_api_key');

echo "1. API Key Status:\n";
if (!empty($api_key)) {
    echo "API key is set in the database (masked: " . substr($api_key, 0, 4) . "..." . substr($api_key, -4) . ")\n";
} else {
    echo "Warning: No API key found in database\n";
    
    if (defined('DEEPSEEK_API_KEY') && !empty(DEEPSEEK_API_KEY)) {
        echo "API key found in config.php constant\n";
    } else {
        echo "Error: No API key found in config.php constant either\n";
        echo "Please set the API key in admin settings or in config.php\n";
        exit(1);
    }
}

echo "\n2. Testing DeepSeek class initialization:\n";
try {
    $ai = create_ai();
    echo "DeepSeek class initialized successfully\n";
} catch (Exception $e) {
    echo "Error initializing DeepSeek class: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3. Testing new chat creation:\n";
try {
    $result = $ai->new_chat("You are a helpful assistant for testing.");
    echo "New chat created successfully\n";
} catch (Exception $e) {
    echo "Error creating new chat: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n4. Testing API call with simple message:\n";
try {
    $response = $ai->send_message("Hello, this is a test message. Please respond with 'Test successful' if you receive this.");
    $response_data = json_decode($response, true);
    
    if (isset($response_data['error'])) {
        echo "API Error: " . $response_data['error'] . "\n";
    } else if (isset($response_data['content'])) {
        echo "API Response received:\n";
        echo substr($response_data['content'], 0, 100) . (strlen($response_data['content']) > 100 ? "..." : "") . "\n";
        echo "Test successful!\n";
    } else {
        echo "Unexpected response format: " . $response . "\n";
    }
} catch (Exception $e) {
    echo "Error sending message: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n5. Testing quiz grading prompt:\n";
$subject = "Science";
$question = "Explain how photosynthesis works.";
$model_answer = "Photosynthesis is the process by which plants convert light energy into chemical energy. Plants use carbon dioxide, water, and sunlight to produce glucose and oxygen.";
$student_answer = "Photosynthesis is how plants make food using sunlight.";

try {
    $content = check_answer($subject, $question, $model_answer, $student_answer);
    echo "Grading prompt generated successfully\n";
    
    $response = $ai->send_message($content);
    $response_data = json_decode($response, true);
    
    if (isset($response_data['error'])) {
        echo "API Error during grading: " . $response_data['error'] . "\n";
    } else if (isset($response_data['content'])) {
        echo "Grading response received:\n";
        echo substr($response_data['content'], 0, 200) . (strlen($response_data['content']) > 200 ? "..." : "") . "\n";
        
        // Try to parse the JSON from the content
        $grading_data = json_decode($response_data['content'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "\nParsed grading data:\n";
            echo "Score: " . ($grading_data['score'] ?? 'N/A') . "\n";
            echo "Feedback: " . substr($grading_data['feedback'] ?? 'N/A', 0, 100) . "...\n";
        } else {
            echo "\nWarning: Could not parse grading response as JSON\n";
        }
    } else {
        echo "Unexpected response format: " . $response . "\n";
    }
} catch (Exception $e) {
    echo "Error during grading test: " . $e->getMessage() . "\n";
}

echo "\n========== TEST COMPLETE ==========\n";
?>
