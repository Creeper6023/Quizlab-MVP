<?php
require_once __DIR__ . '/../config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header("Location: ../auth/login.php");
    exit;
}

$db = new Database();
$settings = [];

// Convert settings from database to associative array for easier access
$dbSettings = $db->getAllSettings();
foreach ($dbSettings as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Handle API key test form submission
$api_test_message = '';
$api_test_status = '';

if (isset($_POST['test_api'])) {
    require_once LIB_PATH . '/ai/DeepSeek.php';

    try {
        $ai = create_ai();
        $result = $ai->new_chat("You are a helpful assistant for testing.");
        $response = $ai->send_message("This is a test message. Please respond with 'API test successful!' if you receive this.");

        $response_data = json_decode($response, true);
        if (isset($response_data['error'])) {
            $api_test_message = "API Test Failed: " . $response_data['error'];
            $api_test_status = 'error';
        } else if (isset($response_data['content'])) {
            $api_test_message = "API Test Successful! Response received: " . substr($response_data['content'], 0, 50) . "...";
            $api_test_status = 'success';
        } else {
            $api_test_message = "API Test Failed: Unexpected response format";
            $api_test_status = 'error';
        }
    } catch (Exception $e) {
        $api_test_message = "API Test Failed: " . $e->getMessage();
        $api_test_status = 'error';
    }
}

$pageTitle = "Admin Settings";
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-sm-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-cog me-2"></i><?php echo $pageTitle; ?>
                </h1>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-robot me-2"></i>AI Grading Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/update_settings_db.php" method="post" class="form">
                        <div class="mb-3">
                            <label for="deepseek_api_key" class="form-label">DeepSeek API Key:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input 
                                    type="text" 
                                    id="deepseek_api_key" 
                                    name="deepseek_api_key" 
                                    value="<?php echo isset($settings['deepseek_api_key']) ? $settings['deepseek_api_key'] : ''; ?>"
                                    placeholder="Enter your DeepSeek API key"
                                    class="form-control"
                                >
                            </div>
                            <div class="form-text">
                                Your DeepSeek API key is required for AI grading features. Keep this key secure.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="ai_grading_enabled" class="form-label">AI Grading:</label>
                            <select id="ai_grading_enabled" name="ai_grading_enabled" class="form-select">
                                <option value="1" <?php echo (isset($settings['ai_grading_enabled']) && $settings['ai_grading_enabled'] == '1') ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo (isset($settings['ai_grading_enabled']) && $settings['ai_grading_enabled'] == '0') ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                            <div class="form-text">
                                Enable or disable AI-based grading for quizzes.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save AI Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Test DeepSeek API Connection
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($api_test_status === 'success'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $api_test_message; ?>
                        </div>
                    <?php elseif ($api_test_status === 'error'): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $api_test_message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post">
                        <button type="submit" name="test_api" class="btn btn-info">
                            <i class="fas fa-plug me-2"></i>Test API Connection
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>Debug Tools - AI Grading Test
                    </h3>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/ajax.php" method="post" class="form" id="ai-grading-test-form">
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="test_subject" class="form-label">Subject:</label>
                                <input type="text" id="test_subject" name="subject" value="Science" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="test_question" class="form-label">Question:</label>
                                <textarea id="test_question" name="question" class="form-control" rows="2">Explain how photosynthesis works.</textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="test_model_answer" class="form-label">Model Answer:</label>
                                <textarea id="test_model_answer" name="model_answer" class="form-control" rows="3">Photosynthesis is the process by which plants convert light energy into chemical energy. Plants use carbon dioxide, water, and sunlight to produce glucose and oxygen.</textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="test_student_answer" class="form-label">Student Answer:</label>
                                <textarea id="test_student_answer" name="student_answer" class="form-control" rows="3">Photosynthesis is how plants make food using sunlight.</textarea>
                            </div>
                        </div>

                        <input type="hidden" name="action" value="test_ai_grading">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-vial me-2"></i>Test AI Grading
                        </button>
                    </form>

                    <div class="loading-overlay" id="loading-overlay" style="display: none !important;">
                        <div class="card p-4 shadow">
                            <div class="text-center mb-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <p class="mb-0 text-center">Testing AI grading, please wait...</p>
                        </div>
                    </div>

                    <div id="test-results" class="card mt-4" style="display: none;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Test Results</h5>
                        </div>
                        <div class="card-body">
                            <div class="simple-result-container mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Simple Grading</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Score:</strong> <span id="simple-score" class="badge bg-primary"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Feedback:</strong> <span id="simple-feedback"></span></p>
                                    </div>
                                </div>
                            </div>

                            <div class="ai-result-container">
                                <h6 class="border-bottom pb-2 mb-3">AI Grading</h6>
                                <div id="ai-result-content" class="bg-light p-3 rounded">
                                    <!-- This will be filled with AI results -->
                                </div>
                            </div>

                            <div class="error-container alert alert-danger mt-3" style="display: none;">
                                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Error</h6>
                                <p id="error-message"></p>
                                <div id="error-details" class="mt-3" style="display: none;">
                                    <h6>Error Details</h6>
                                    <pre id="error-json" class="bg-light p-3 rounded small" style="max-height: 200px; overflow-y: auto;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-palette me-2"></i>User Interface Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/update_settings_db.php" method="post" class="form">
                        <div class="mb-3">
                            <label for="quick_login_enabled" class="form-label">Quick Login Feature:</label>
                            <select id="quick_login_enabled" name="quick_login_enabled" class="form-select">
                                <option value="1" <?php echo (isset($settings['quick_login_enabled']) && $settings['quick_login_enabled'] == '1') ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo (isset($settings['quick_login_enabled']) && $settings['quick_login_enabled'] == '0') ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                            <div class="form-text">
                                When enabled, username options will be displayed on the login page to allow for faster login during testing and development.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save UI Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
            
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>System Logs
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-outline-primary" id="view-debug-log">
                            <i class="fas fa-bug me-2"></i>View Debug Log
                        </button>
                        <button class="btn btn-outline-info" id="view-ai-debug-log">
                            <i class="fas fa-robot me-2"></i>View AI Debug Log
                        </button>
                        <button class="btn btn-outline-danger" id="clear-logs">
                            <i class="fas fa-trash-alt me-2"></i>Clear Logs
                        </button>
                    </div>
                    
                    <div class="card bg-light" id="log-viewer" style="display: none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0" id="log-title">Log Viewer</h5>
                        </div>
                        <div class="card-body p-0">
                            <pre id="log-content" class="p-3 mb-0 bg-light text-dark" style="max-height: 400px; overflow-y: auto;">Select a log to view</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testForm = document.getElementById('ai-grading-test-form');
            const loadingOverlay = document.getElementById('loading-overlay');
            const testResults = document.getElementById('test-results');
            const simpleScore = document.getElementById('simple-score');
            const simpleFeedback = document.getElementById('simple-feedback');
            const aiResultContent = document.getElementById('ai-result-content');
            const errorContainer = document.querySelector('.error-container');
            const errorMessage = document.getElementById('error-message');
            const errorDetails = document.getElementById('error-details');
            const errorJson = document.getElementById('error-json');

            // Function to syntex highlight JSON
            function formatJsonString(jsonString) {
                if (!jsonString) return '';

                try {
                    // Parse the JSON
                    const json = JSON.parse(jsonString);

                    // Handle the AI response which contains JSON string in content field
                    if (json.content) {
                        // Remove the "json\n" prefix and parse the actual JSON content
                        const cleanedContent = json.content.replace(/^json\n/, '');
                        const aiResult = JSON.parse(cleanedContent);

                        // Now check if we have score and feedback
                        if (aiResult.score !== undefined && aiResult.feedback !== undefined) {
                            let html = '<div class="ai-result-summary">';
                            html += `<p><strong>Score:</strong> ${aiResult.score}</p>`;
                            html += `<p><strong>Feedback:</strong> ${aiResult.feedback}</p>`;

                            // Key points addressed
                            if (aiResult.key_points_addressed && aiResult.key_points_addressed.length > 0) {
                                html += '<h6>Key Points Addressed:</h6><ul>';
                                aiResult.key_points_addressed.forEach(point => {
                                    html += `<li>${point}</li>`;
                                });
                                html += '</ul>';
                            }

                            // Key points missed
                            if (aiResult.key_points_missed && aiResult.key_points_missed.length > 0) {
                                html += '<h6>Key Points Missed:</h6><ul>';
                                aiResult.key_points_missed.forEach(point => {
                                    html += `<li>${point}</li>`;
                                });
                                html += '</ul>';
                            }

                            // Improvement suggestions
                            if (aiResult.improvement_suggestions) {
                                html += `<h6>Improvement Suggestions:</h6><p>${aiResult.improvement_suggestions}</p>`;
                            }

                            html += '</div>';
                            return html;
                        }
                    }


                    // Otherwise format as pretty JSON
                    return syntaxHighlightJson(json);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    return jsonString;
                }
            }

            // Syntax highlighting for JSON
            function syntaxHighlightJson(json) {
                // Convert to string if it's an object
                if (typeof json !== 'string') {
                    json = JSON.stringify(json, null, 2);
                }

                // Apply syntax highlighting with regex
                return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                    let cls = 'json-number';
                    if (/^"/.test(match)) {
                        if (/:$/.test(match)) {
                            cls = 'json-key';
                        } else {
                            cls = 'json-string';
                        }
                    } else if (/true|false/.test(match)) {
                        cls = 'json-boolean';
                    } else if (/null/.test(match)) {
                        cls = 'json-null';
                    }
                    return '<span class="' + cls + '">' + match + '</span>';
                });
            }

            // Function to parse and display error details
            function displayErrorDetails(errorString) {
                try {
                    // Check if this is a JSON string that contains an error field
                    const errorJson = JSON.parse(errorString);

                    if (errorJson.error) {
                        // The error field might be a string or an object
                        let errorDetail = errorJson.error;

                        // If it's a string that looks like JSON, try to parse it
                        if (typeof errorDetail === 'string' && errorDetail.includes('{')) {
                            try {
                                // Extract JSON from the string
                                const jsonMatch = errorDetail.match(/\{.*\}/s);
                                if (jsonMatch) {
                                    const extractedJson = JSON.parse(jsonMatch[0]);
                                    return {
                                        message: `API Error: ${extractedJson.message || 'Unknown error'}`,
                                        detail: syntaxHighlightJson(extractedJson)
                                    };
                                }
                            } catch (e) {
                                // If extraction fails, just use the string
                                console.error('Error parsing nested JSON:', e);
                            }
                        }

                        // Handle case where error is already an object
                        if (typeof errorDetail === 'object') {
                            return {
                                message: `API Error: ${errorDetail.message || 'Unknown error'}`,
                                detail: syntaxHighlightJson(errorDetail)
                            };
                        }

                        // Default case where error is a simple string
                        return {
                            message: errorDetail,
                            detail: syntaxHighlightJson(errorJson)
                        };
                    }

                    // If no error field but still JSON, show the formatted JSON
                    return {
                        message: 'Error processing request',
                        detail: syntaxHighlightJson(errorJson)
                    };

                } catch (e) {
                    // Not valid JSON, just return the string
                    console.error('Error parsing error JSON:', e);
                    return {
                        message: errorString,
                        detail: errorString
                    };
                }
            }

            // View debug logs
            document.getElementById('view-debug-log').addEventListener('click', function(e) {
                e.preventDefault();
                fetch('<?= BASE_URL ?>/debug_log_viewer.php?log=debug')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('log-title').textContent = 'Debug Log';
                        document.getElementById('log-content').textContent = data;
                        document.getElementById('log-viewer').style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching debug log:', error);
                    });
            });

            document.getElementById('view-ai-debug-log').addEventListener('click', function(e) {
                e.preventDefault();
                fetch('<?= BASE_URL ?>/debug_log_viewer.php?log=ai_debug')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('log-title').textContent = 'AI Debug Log';
                        document.getElementById('log-content').textContent = data;
                        document.getElementById('log-viewer').style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching AI debug log:', error);
                    });
            });

            document.getElementById('clear-logs').addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to clear all log files?')) {
                    fetch('<?= BASE_URL ?>/debug_log_viewer.php?action=clear')
                        .then(response => response.text())
                        .then(data => {
                            alert('Logs cleared successfully');
                            document.getElementById('log-viewer').style.display = 'none';
                        })
                        .catch(error => {
                            console.error('Error clearing logs:', error);
                        });
                }
            });

            // Test AI grading form submission
            testForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Show loading overlay
                loadingOverlay.classList.add('active');
                loadingOverlay.style.removeProperty('display');
                testResults.style.display = 'none';

                // Collect form data
                const formData = new FormData(testForm);

                // Send AJAX request
                fetch('<?= BASE_URL ?>/ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Test AI grading response:', data);

                    // Hide loading overlay
                    loadingOverlay.classList.remove('active');

                    // Show test results
                    testResults.style.display = 'block';

                    // Display simple result
                    if (data.simple_result) {
                        simpleScore.textContent = data.simple_result.score;
                        simpleFeedback.textContent = data.simple_result.feedback;
                    }

                    // Reset error container
                    errorContainer.style.display = 'none';
                    errorDetails.style.display = 'none';

                    // Handle different response scenarios
                    if (data.error) {
                        // Show error message
                        errorContainer.style.display = 'block';
                        errorMessage.textContent = data.error;
                        document.querySelector('.ai-result-container').style.display = 'none';
                    } else if (data.result === null) {
                        // AI grading is disabled
                        errorContainer.style.display = 'block';
                        errorMessage.textContent = 'AI grading is disabled. Enable it in settings to see AI grading results.';
                        document.querySelector('.ai-result-container').style.display = 'none';
                    } else if (typeof data.result === 'string' && data.result.includes('error')) {
                        // API error from DeepSeek
                        const errorInfo = displayErrorDetails(data.result);

                        errorContainer.style.display = 'block';
                        errorMessage.textContent = errorInfo.message;
                        document.querySelector('.ai-result-container').style.display = 'none';

                        // Show detailed error
                        errorJson.innerHTML = errorInfo.detail;
                        errorDetails.style.display = 'block';
                    } else {
                        // Success - AI result available
                        errorContainer.style.display = 'none';
                        document.querySelector('.ai-result-container').style.display = 'block';

                        // Process AI result
                        if (data.result && data.result.content) {
                            aiResultContent.innerHTML = formatJsonString(data.result.content);
                        } else {
                            aiResultContent.innerHTML = '<p>No AI feedback available.</p>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error testing AI grading:', error);

                    // Hide loading overlay
                    loadingOverlay.classList.remove('active');

                    // Show error
                    testResults.style.display = 'block';
                    errorContainer.style.display = 'block';
                    errorMessage.textContent = 'An error occurred while testing AI grading: ' + error.message;
                    document.querySelector('.ai-result-container').style.display = 'none';
                });
            });
        });
    </script>
</body>
</html>