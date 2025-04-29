<?php
require_once __DIR__ . '/../../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$error = null;
$success = false;
$importResults = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != UPLOAD_ERR_OK) {
        $error = 'Please select a CSV file to upload';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $fileInfo = pathinfo($_FILES['csv_file']['name']);
        

        if ($fileInfo['extension'] !== 'csv') {
            $error = 'Only CSV files are allowed';
        } else {

            $handle = fopen($file, 'r');
            
            if ($handle === false) {
                $error = 'Failed to open file';
            } else {

                $header = fgetcsv($handle);
                

                if (count($header) < 3 || 
                    strtolower($header[0]) !== 'username' || 
                    strtolower($header[1]) !== 'password' || 
                    strtolower($header[2]) !== 'role') {
                    
                    $error = 'Invalid CSV format. The CSV file must have columns: username, password, role';
                    fclose($handle);
                } else {

                    $db->getConnection()->beginTransaction();
                    
                    try {
                        $row = 1; // Start at 1 for header row
                        $created = 0;
                        $skipped = 0;
                        $errors = 0;
                        

                        while (($data = fgetcsv($handle)) !== false) {
                            $row++;
                            

                            if (count($data) < 3 || empty($data[0]) || empty($data[1])) {
                                $importResults[] = [
                                    'row' => $row,
                                    'username' => isset($data[0]) ? $data[0] : '',
                                    'status' => 'skipped',
                                    'message' => 'Missing required fields'
                                ];
                                $skipped++;
                                continue;
                            }
                            
                            $username = trim($data[0]);
                            $password = trim($data[1]);
                            $role = strtolower(trim($data[2]));
                            

                            if (!in_array($role, ['admin', 'teacher', 'student'])) {
                                $importResults[] = [
                                    'row' => $row,
                                    'username' => $username,
                                    'status' => 'error',
                                    'message' => 'Invalid role. Must be admin, teacher, or student'
                                ];
                                $errors++;
                                continue;
                            }
                            

                            $existing = $db->single(
                                "SELECT id FROM users WHERE username = ?", 
                                [$username]
                            );
                            
                            if ($existing) {
                                $importResults[] = [
                                    'row' => $row,
                                    'username' => $username,
                                    'status' => 'skipped',
                                    'message' => 'Username already exists'
                                ];
                                $skipped++;
                                continue;
                            }
                            

                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            
                            $result = $db->query(
                                "INSERT INTO users (username, password, role) VALUES (?, ?, ?)",
                                [$username, $hashedPassword, $role]
                            );
                            
                            if ($result) {
                                $importResults[] = [
                                    'row' => $row,
                                    'username' => $username,
                                    'status' => 'success',
                                    'message' => 'User created successfully'
                                ];
                                $created++;
                            } else {
                                $importResults[] = [
                                    'row' => $row,
                                    'username' => $username,
                                    'status' => 'error',
                                    'message' => 'Failed to create user'
                                ];
                                $errors++;
                            }
                        }
                        

                        $db->getConnection()->commit();
                        
                        $success = true;
                        $_SESSION['success_message'] = "Import completed: $created users created, $skipped skipped, $errors errors";
                        
                    } catch (Exception $e) {

                        $db->getConnection()->rollBack();
                        $error = 'Error during import: ' . $e->getMessage();
                    }
                    
                    fclose($handle);
                }
            }
        }
    }
}


$pageTitle = "Import Users";
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-file-import text-primary me-2"></i><?php echo $pageTitle; ?></h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Users
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Import completed successfully.
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Upload CSV File</h5>
                </div>
                <div class="card-body">
                    <form action="import_users.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <div class="form-text text-muted">
                                The CSV file must have the following columns: username, password, role
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6>CSV Format Example:</h6>
                            <div class="bg-light p-3 rounded">
                                <code>username,password,role</code><br>
                                <code>john_doe,password123,student</code><br>
                                <code>jane_smith,secure456,teacher</code><br>
                                <code>admin_user,admin789,admin</code>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i> Upload and Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($importResults)): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Import Results</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Row</th>
                                        <th scope="col">Username</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($importResults as $result): ?>
                                        <tr class="<?php 
                                            echo $result['status'] === 'success' 
                                                ? 'table-success' 
                                                : ($result['status'] === 'error' ? 'table-danger' : 'table-warning'); 
                                        ?>">
                                            <td><?php echo $result['row']; ?></td>
                                            <td><?php echo htmlspecialchars($result['username']); ?></td>
                                            <td>
                                                <?php if ($result['status'] === 'success'): ?>
                                                    <span class="badge bg-success">Success</span>
                                                <?php elseif ($result['status'] === 'error'): ?>
                                                    <span class="badge bg-danger">Error</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Skipped</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($result['message']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>