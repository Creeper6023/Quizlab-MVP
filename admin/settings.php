<?php
require_once __DIR__ . '/../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header("Location: ../auth/login.php");
    exit;
}

$db = new Database();
$settings = [];


$dbSettings = $db->getAllSettings();
foreach ($dbSettings as $setting) {
    $settings[$setting['key']] = $setting['value'];
}


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

                        <div id="aiSettingsSaveBar" class="settings-save-bar" style="display: none;">
                            <div class="save-message">
                                <i class="fas fa-exclamation-circle"></i> You have unsaved changes
                            </div>
                            <button type="submit" class="btn btn-green">
                                Save Changes
                            </button>
                        </div>
                    </form>
                    <style>
                        .settings-save-bar {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            background: #202225;
                            padding: 15px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            z-index: 1000;
                            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                        }
                        .save-message {
                            color: #fff;
                        }
                        .btn-green {
                            background: #3ba55c;
                            color: white;
                            border: none;
                        }
                        .btn-green:hover {
                            background: #2d7d46;
                            color: white;
                        }
                    </style>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const form = document.querySelector('form');
                            const saveBar = document.getElementById('aiSettingsSaveBar');
                            let initialState = new FormData(form);

                            function checkChanges() {
                                const currentState = new FormData(form);
                                let hasChanges = false;

                                for(let pair of currentState.entries()) {
                                    if(initialState.get(pair[0]) !== pair[1]) {
                                        hasChanges = true;
                                        break;
                                    }
                                }

                                saveBar.style.display = hasChanges ? 'flex' : 'none';
                            }

                            form.querySelectorAll('input, select').forEach(input => {
                                input.addEventListener('change', checkChanges);
                                input.addEventListener('input', checkChanges);
                            });
                        });
                    </script>
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
                                    <div class="mb-3">
                                        <h5>Score: <span id="ai-score" class="badge"></span></h5>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Feedback:</h6>
                                        <p id="ai-feedback" class="feedback-text"></p>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Key Points Addressed:</h6>
                                        <ul id="key-points-addressed" class="list-group"></ul>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Key Points Missed:</h6>
                                        <ul id="key-points-missed" class="list-group"></ul>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Suggestions for Improvement:</h6>
                                        <div id="improvement-suggestions" class="alert alert-info">
                                        </div>
                                    </div>
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


            function formatJsonString(jsonString) {
                if (!jsonString) return '';

                try {

                    const json = typeof jsonString === 'object' ? jsonString : JSON.parse(jsonString);
                    

                    if (json.score !== undefined && json.feedback !== undefined) {
                        const aiResult = json;
                        

                        const scoreElement = document.getElementById('ai-score');
                        scoreElement.textContent = aiResult.score + '%';
                        scoreElement.className = `badge bg-${aiResult.score >= 70 ? 'success' : aiResult.score >= 40 ? 'warning' : 'danger'}`;
                        

                        document.getElementById('ai-feedback').textContent = aiResult.feedback;
                        

                        const pointsAddressedList = document.getElementById('key-points-addressed');
                        pointsAddressedList.innerHTML = '';
                        if (Array.isArray(aiResult.key_points_addressed)) {
                            aiResult.key_points_addressed.forEach(point => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item list-group-item-success';
                                li.innerHTML = `<i class="fas fa-check me-2"></i>${point}`;
                                pointsAddressedList.appendChild(li);
                            });
                        }
                        

                        const pointsMissedList = document.getElementById('key-points-missed');
                        pointsMissedList.innerHTML = '';
                        if (Array.isArray(aiResult.key_points_missed)) {
                            aiResult.key_points_missed.forEach(point => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item list-group-item-danger';
                                li.innerHTML = `<i class="fas fa-times me-2"></i>${point}`;
                                pointsMissedList.appendChild(li);
                            });
                        }
                        

                        if (aiResult.improvement_suggestions) {
                            document.getElementById('improvement-suggestions').innerHTML = 
                                `<i class="fas fa-lightbulb me-2"></i>${aiResult.improvement_suggestions}`;
                        }
                        
                        return ''; // Return empty since we're updating DOM directly
                    }
                    

                    if (json.content) {
                        try {
                            const cleanedContent = json.content.replace(/^```json\n|\n```$/g, '');
                            const aiResult = JSON.parse(cleanedContent);
                            

                            const scoreElement = document.getElementById('ai-score');
                            scoreElement.textContent = aiResult.score + '%';
                            scoreElement.className = `badge bg-${aiResult.score >= 70 ? 'success' : aiResult.score >= 40 ? 'warning' : 'danger'}`;
                            

                            document.getElementById('ai-feedback').textContent = aiResult.feedback;
                            

                            const pointsAddressedList = document.getElementById('key-points-addressed');
                            pointsAddressedList.innerHTML = '';
                            aiResult.key_points_addressed.forEach(point => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item list-group-item-success';
                                li.innerHTML = `<i class="fas fa-check me-2"></i>${point}`;
                                pointsAddressedList.appendChild(li);
                            });
                            

                            const pointsMissedList = document.getElementById('key-points-missed');
                            pointsMissedList.innerHTML = '';
                            aiResult.key_points_missed.forEach(point => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item list-group-item-danger';
                                li.innerHTML = `<i class="fas fa-times me-2"></i>${point}`;
                                pointsMissedList.appendChild(li);
                            });
                            

                            document.getElementById('improvement-suggestions').innerHTML = 
                                `<i class="fas fa-lightbulb me-2"></i>${aiResult.improvement_suggestions}`;
                            
                            return ''; // Return empty since we're updating DOM directly
                        } catch (e) {
                            console.error('Error parsing AI result:', e);
                            return '<div class="alert alert-danger">Error parsing AI result</div>';
                        }
                    }



                    return syntaxHighlightJson(json);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    return jsonString;
                }
            }


            function syntaxHighlightJson(json) {

                if (typeof json !== 'string') {
                    json = JSON.stringify(json, null, 2);
                }


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


            function displayErrorDetails(errorString) {
                try {

                    const errorJson = JSON.parse(errorString);

                    if (errorJson.error) {

                        let errorDetail = errorJson.error;


                        if (typeof errorDetail === 'string' && errorDetail.includes('{')) {
                            try {

                                const jsonMatch = errorDetail.match(/\{.*\}/s);
                                if (jsonMatch) {
                                    const extractedJson = JSON.parse(jsonMatch[0]);
                                    return {
                                        message: `API Error: ${extractedJson.message || 'Unknown error'}`,
                                        detail: syntaxHighlightJson(extractedJson)
                                    };
                                }
                            } catch (e) {

                                console.error('Error parsing nested JSON:', e);
                            }
                        }


                        if (typeof errorDetail === 'object') {
                            return {
                                message: `API Error: ${errorDetail.message || 'Unknown error'}`,
                                detail: syntaxHighlightJson(errorDetail)
                            };
                        }


                        return {
                            message: errorDetail,
                            detail: syntaxHighlightJson(errorJson)
                        };
                    }


                    return {
                        message: 'Error processing request',
                        detail: syntaxHighlightJson(errorJson)
                    };

                } catch (e) {

                    console.error('Error parsing error JSON:', e);
                    return {
                        message: errorString,
                        detail: errorString
                    };
                }
            }


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


            testForm.addEventListener('submit', function(e) {
                e.preventDefault();


                loadingOverlay.classList.add('active');
                loadingOverlay.style.removeProperty('display');
                testResults.style.display = 'none';


                const formData = new FormData(testForm);


                fetch('<?= BASE_URL ?>/ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Test AI grading response:', data);


                    loadingOverlay.classList.remove('active');


                    testResults.style.display = 'block';


                    if (data.simple_result) {
                        simpleScore.textContent = data.simple_result.score;
                        simpleFeedback.textContent = data.simple_result.feedback;
                    }


                    errorContainer.style.display = 'none';
                    errorDetails.style.display = 'none';


                    if (data.error) {

                        errorContainer.style.display = 'block';
                        errorMessage.textContent = data.error;
                        document.querySelector('.ai-result-container').style.display = 'none';
                    } else if (data.result === null) {

                        errorContainer.style.display = 'block';
                        errorMessage.textContent = 'AI grading is disabled. Enable it in settings to see AI grading results.';
                        document.querySelector('.ai-result-container').style.display = 'none';
                    } else if (typeof data.result === 'string' && data.result.includes('error')) {

                        const errorInfo = displayErrorDetails(data.result);

                        errorContainer.style.display = 'block';
                        errorMessage.textContent = errorInfo.message;
                        document.querySelector('.ai-result-container').style.display = 'none';


                        errorJson.innerHTML = errorInfo.detail;
                        errorDetails.style.display = 'block';
                    } else {

                        errorContainer.style.display = 'none';
                        document.querySelector('.ai-result-container').style.display = 'block';


                        if (data.result) {
                            if (data.result.content) {

                                aiResultContent.innerHTML = formatJsonString(data.result.content);
                            } else if (typeof data.result === 'object' && data.result.score !== undefined) {

                                aiResultContent.innerHTML = formatJsonString(data.result);
                            } else {
                                aiResultContent.innerHTML = '<p>No AI feedback available.</p>';
                            }
                        } else {
                            aiResultContent.innerHTML = '<p>No AI feedback available.</p>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error testing AI grading:', error);


                    loadingOverlay.classList.remove('active');


                    testResults.style.display = 'block';
                    errorContainer.style.display = 'block';
                    errorMessage.textContent = 'An error occurred while testing AI grading: ' + error.message;
                    document.querySelector('.ai-result-container').style.display = 'none';
                });
            });

            function tryParseJSON(jsonString) {
                try {

                    let parsed = JSON.parse(jsonString);
                    return parsed;
                } catch (e) {

                    const matches = jsonString.match(/```json\s*({[\s\S]*?})\s*```/);
                    if (matches && matches[1]) {
                        try {
                            return JSON.parse(matches[1]);
                        } catch (e2) {
                            return null;
                        }
                    }
                    return null;
                }
            }

            function updateGradingCard(data) {
                const aiResultContent = document.getElementById('ai-result-content');
                const errorContainer = document.querySelector('.error-container');
                const resultContainer = document.querySelector('.ai-result-container');

                let parsedData = null;
                
                if (typeof data === 'string') {
                    parsedData = tryParseJSON(data);
                } else if (typeof data === 'object') {
                    parsedData = data;
                }

                if (!parsedData) {
                    errorContainer.style.display = 'block';
                    errorMessage.textContent = 'Failed to parse AI grading response';
                    resultContainer.style.display = 'none';
                    return;
                }


                document.getElementById('ai-score').textContent = parsedData.score + '%';
                document.getElementById('ai-score').className = 'badge ' + 
                    (parsedData.score >= 70 ? 'bg-success' : 
                     parsedData.score >= 40 ? 'bg-warning' : 'bg-danger');
                
                document.getElementById('ai-feedback').textContent = parsedData.feedback || 'No feedback available';
                
                const addressedList = document.getElementById('key-points-addressed');
                const missedList = document.getElementById('key-points-missed');
                
                addressedList.innerHTML = '';
                missedList.innerHTML = '';
                
                (parsedData.key_points_addressed || []).forEach(point => {
                    addressedList.innerHTML += `
                        <li class="list-group-item list-group-item-success">
                            <i class="fas fa-check me-2"></i>${point}
                        </li>`;
                });
                
                (parsedData.key_points_missed || []).forEach(point => {
                    missedList.innerHTML += `
                        <li class="list-group-item list-group-item-danger">
                            <i class="fas fa-times me-2"></i>${point}
                        </li>`;
                });
                
                document.getElementById('improvement-suggestions').textContent = 
                    parsedData.improvement_suggestions || 'No improvement suggestions available';
                
                errorContainer.style.display = 'none';
                resultContainer.style.display = 'block';
            }
        });
    </script>
</body>
</html>