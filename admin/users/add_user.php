<?php
require_once __DIR__ . '/../../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$error = null;
$success = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    

    if (empty($username) || empty($password) || empty($role)) {
        $error = 'All fields are required';
    } else if (!in_array($role, array('admin', 'teacher', 'student'))) {
        $error = 'Invalid role selected';
    } else {

        $existing = $db->single(
            "SELECT id FROM users WHERE username = ?", 
            [$username]
        );
        
        if ($existing) {
            $error = 'Username already exists';
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            

            $hashId = generateHashId();
            

            while ($db->resultSet("SELECT id FROM users WHERE hash_id = ?", [$hashId])) {
                $hashId = generateHashId();
            }
            

            $result = $db->query(
                "INSERT INTO users (username, password, role, hash_id) VALUES (?, ?, ?, ?)",
                [$username, $hashedPassword, $role, $hashId]
            );
            
            if ($result) {
                $_SESSION['success_message'] = 'User created successfully';
                header('Location: ' . BASE_URL . '/admin/users/index.php');
                exit;
            } else {
                $error = 'Failed to create user';
            }
        }
    }
}


$pageTitle = "Add User";
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-user-plus text-primary me-2"></i><?php echo $pageTitle; ?></h1>
                <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Users
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Create New User</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/users/add_user.php" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled <?php echo !isset($_POST['role']) ? 'selected' : ''; ?>>
                                    Select a role
                                </option>
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>
                                    Student
                                </option>
                                <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>
                                    Teacher
                                </option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>
                                    Administrator
                                </option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>