<?php
require_once __DIR__ . '/../../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$admin_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


$quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);
if (!$quiz) {
    redirect(BASE_URL . '/admin/quizzes');
}


$users = $db->resultSet("
    SELECT id, username, name, role 
    FROM users 
    WHERE (role = ? OR role = ?) AND id != ? 
    ORDER BY role DESC, name
", [ROLE_TEACHER, ROLE_ADMIN, $admin_id]);


$currentShares = $db->resultSet("
    SELECT s.*, u.username, u.name, u.role
    FROM quiz_shares s
    JOIN users u ON s.shared_with_id = u.id
    WHERE s.quiz_id = ? AND s.shared_by_id = ?
    ORDER BY u.role DESC, u.name
", [$quiz_id, $admin_id]);


$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['share_quiz'])) {
        $shared_with_id = (int)($_POST['shared_with_id'] ?? 0);
        $permission_level = $_POST['permission_level'] ?? 'view';
        

        if (!in_array($permission_level, ['view', 'edit', 'full'])) {
            $permission_level = 'view';
        }
        

        $user = $db->single("SELECT id FROM users WHERE id = ? AND (role = ? OR role = ?)", 
                             [$shared_with_id, ROLE_TEACHER, ROLE_ADMIN]);
        
        if (!$user) {
            $formError = 'Invalid user selected.';
        } else {

            $existingShare = $db->single("
                SELECT id FROM quiz_shares 
                WHERE quiz_id = ? AND shared_by_id = ? AND shared_with_id = ?
            ", [$quiz_id, $admin_id, $shared_with_id]);
            
            if ($existingShare) {

                $db->query("
                    UPDATE quiz_shares SET permission_level = ?, shared_at = CURRENT_TIMESTAMP
                    WHERE quiz_id = ? AND shared_by_id = ? AND shared_with_id = ?
                ", [$permission_level, $quiz_id, $admin_id, $shared_with_id]);
                
                $formSuccess = 'Share permissions updated successfully!';
            } else {

                $db->query("
                    INSERT INTO quiz_shares (quiz_id, shared_by_id, shared_with_id, permission_level)
                    VALUES (?, ?, ?, ?)
                ", [$quiz_id, $admin_id, $shared_with_id, $permission_level]);
                
                $formSuccess = 'Quiz shared successfully!';
            }
            

            $currentShares = $db->resultSet("
                SELECT s.*, u.username, u.name, u.role
                FROM quiz_shares s
                JOIN users u ON s.shared_with_id = u.id
                WHERE s.quiz_id = ? AND s.shared_by_id = ?
                ORDER BY u.role DESC, u.name
            ", [$quiz_id, $admin_id]);
        }
    } elseif (isset($_POST['remove_share'])) {
        $share_id = (int)($_POST['share_id'] ?? 0);
        

        $share = $db->single("
            SELECT id FROM quiz_shares 
            WHERE id = ? AND quiz_id = ? AND shared_by_id = ?
        ", [$share_id, $quiz_id, $admin_id]);
        
        if ($share) {
            $db->query("DELETE FROM quiz_shares WHERE id = ?", [$share_id]);
            
            $formSuccess = 'Share removed successfully!';
            

            $currentShares = $db->resultSet("
                SELECT s.*, u.username, u.name, u.role
                FROM quiz_shares s
                JOIN users u ON s.shared_with_id = u.id
                WHERE s.quiz_id = ? AND s.shared_by_id = ?
                ORDER BY u.role DESC, u.name
            ", [$quiz_id, $admin_id]);
        } else {
            $formError = 'Invalid share selected.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Share Quiz: <?= htmlspecialchars($quiz['title']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/quizzes" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
        </a>
    </div>
    
    <?php if (!empty($formError)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $formError ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($formSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $formSuccess ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i>Share with Others</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="shared_with_id" class="form-label">Share with:</label>
                            <select class="form-select" id="shared_with_id" name="shared_with_id" required>
                                <option value="">Select a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['name'] ?: $user['username']) ?> 
                                        (<?= ucfirst($user['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Permission Level:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="permission_level" id="permission_view" value="view" checked>
                                <label class="form-check-label" for="permission_view">
                                    <i class="fas fa-eye text-info me-1"></i> View Only
                                    <small class="d-block text-muted">Can view the quiz and results</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="permission_level" id="permission_edit" value="edit">
                                <label class="form-check-label" for="permission_edit">
                                    <i class="fas fa-edit text-primary me-1"></i> Edit
                                    <small class="d-block text-muted">Can view and edit the quiz</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="permission_level" id="permission_full" value="full">
                                <label class="form-check-label" for="permission_full">
                                    <i class="fas fa-crown text-warning me-1"></i> Full Access
                                    <small class="d-block text-muted">Can view, edit, and manage the quiz</small>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="share_quiz" class="btn btn-primary">
                            <i class="fas fa-share-alt me-1"></i> Share Quiz
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Currently Shared With</h5>
                </div>
                <div class="card-body">
                    <?php if (count($currentShares) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Permission</th>
                                        <th>Shared On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentShares as $share): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-<?= $share['role'] === ROLE_ADMIN ? 'danger' : 'primary' ?> bg-opacity-10 p-2 rounded-circle me-2">
                                                        <i class="fas fa-user text-<?= $share['role'] === ROLE_ADMIN ? 'danger' : 'primary' ?>"></i>
                                                    </div>
                                                    <div>
                                                        <?= htmlspecialchars($share['name'] ?: $share['username']) ?>
                                                        <small class="d-block text-muted"><?= ucfirst($share['role']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($share['permission_level'] === 'view'): ?>
                                                    <span class="badge bg-info"><i class="fas fa-eye me-1"></i> View</span>
                                                <?php elseif ($share['permission_level'] === 'edit'): ?>
                                                    <span class="badge bg-primary"><i class="fas fa-edit me-1"></i> Edit</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i> Full</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('M j, Y', strtotime($share['shared_at'])) ?></small>
                                            </td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this share?');">
                                                    <input type="hidden" name="share_id" value="<?= $share['id'] ?>">
                                                    <button type="submit" name="remove_share" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                            <p class="lead">This quiz is not shared with anyone yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>