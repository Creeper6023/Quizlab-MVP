<?php
require_once __DIR__ . '/../../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$error = null;
$success = false;
$user = null;


if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid user ID';
    header('Location: index.php');
    exit;
}

$userId = (int)$_GET['id'];


$user = $db->single(
    "SELECT id, username, role FROM users WHERE id = ?", 
    [$userId]
);

if (!$user) {
    $_SESSION['error_message'] = 'User not found';
    header('Location: index.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']); // Optional - only update if provided
    $role = trim($_POST['role']);
    

    if (empty($username) || empty($role)) {
        $error = 'Username and role are required';
    } else if (!in_array($role, array('admin', 'teacher', 'student'))) {
        $error = 'Invalid role selected';
    } else {

        $existing = $db->single(
            "SELECT id FROM users WHERE username = ? AND id != ?", 
            [$username, $userId]
        );
        
        if ($existing) {
            $error = 'Username already exists';
        } else {

            if (!empty($password)) {

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $result = $db->query(
                    "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?",
                    [$username, $hashedPassword, $role, $userId]
                );
            } else {

                $result = $db->query(
                    "UPDATE users SET username = ?, role = ? WHERE id = ?",
                    [$username, $role, $userId]
                );
            }
            
            if ($result) {
                $_SESSION['success_message'] = 'User updated successfully';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Failed to update user';
            }
        }
    }
}


$pageTitle = "Edit User";
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-user-edit text-primary me-2"></i><?php echo $pageTitle; ?></h1>
                <a href="index.php" class="btn btn-outline-secondary">
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
                    <h5 class="mb-0">Edit User: <?php echo htmlspecialchars($user['username']); ?></h5>
                </div>
                <div class="card-body">
                    <form action="edit_user.php?id=<?php echo $user['id']; ?>" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <small class="text-muted">(Leave blank to keep current password)</small>
                            </label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>
                                    Student
                                </option>
                                <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>
                                    Teacher
                                </option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                    Administrator
                                </option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>