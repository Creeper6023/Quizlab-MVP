<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$admin_id = $_SESSION['user_id'];

$userStats = [
    'totalUsers' => $db->single("SELECT COUNT(*) as count FROM users", [])['count'],
    'totalTeachers' => $db->single("SELECT COUNT(*) as count FROM users WHERE role = ?", [ROLE_TEACHER])['count'],
    'totalStudents' => $db->single("SELECT COUNT(*) as count FROM users WHERE role = ?", [ROLE_STUDENT])['count']
];

$quizStats = [
    'totalQuizzes' => $db->single("SELECT COUNT(*) as count FROM quizzes", [])['count'],
    'totalPublishedQuizzes' => $db->single("SELECT COUNT(*) as count FROM quizzes WHERE is_published = 1", [])['count'],
    'totalClasses' => $db->single("SELECT COUNT(*) as count FROM classes", [])['count'],
    'totalAttempts' => $db->single("SELECT COUNT(*) as count FROM quiz_attempts", [])['count']
];

$recentUsers = $db->resultSet("SELECT * FROM users ORDER BY created_at DESC LIMIT 5", []);

$recentQuizzes = $db->resultSet("
    SELECT q.*, u.username as creator_name, 0 as is_shared 
    FROM quizzes q 
    JOIN users u ON q.created_by = u.id 
    ORDER BY q.created_at DESC LIMIT 5
", []);

$sharedQuizzes = $db->resultSet("
    SELECT q.*, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as completed_count,
           u.username as owner_name,
           s.permission_level,
           1 as is_shared
    FROM quiz_shares s
    JOIN quizzes q ON s.quiz_id = q.id
    JOIN users u ON q.created_by = u.id
    WHERE s.shared_with_id = ?
    ORDER BY s.shared_at DESC
", [$admin_id]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <div class="dashboard-actions">
            <a href="<?= BASE_URL ?>/admin/classes/" class="btn btn-primary me-2">
                <i class="fas fa-chalkboard"></i> Manage Classes
            </a>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>
    
    <div class="stats-container">
        <div class="stats-card">
            <h3>User Statistics</h3>
            <ul>
                <li>Total Users: <?= $userStats['totalUsers'] ?></li>
                <li>Teachers: <?= $userStats['totalTeachers'] ?></li>
                <li>Students: <?= $userStats['totalStudents'] ?></li>
            </ul>
        </div>
        
        <div class="stats-card">
            <h3>Quiz Statistics</h3>
            <ul>
                <li>Total Quizzes: <?= $quizStats['totalQuizzes'] ?></li>
                <li>Published Quizzes: <?= $quizStats['totalPublishedQuizzes'] ?></li>
                <li>Classes: <?= $quizStats['totalClasses'] ?></li>
                <li>Quiz Attempts: <?= $quizStats['totalAttempts'] ?></li>
            </ul>
        </div>
    </div>
    
    <div id="admin-panel" class="admin-app">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0"><i class="fas fa-tasks me-2"></i>Administrative Tools</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6 col-lg-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 p-3 rounded-2 me-3" style="border-radius: 8px !important;">
                                                <i class="fas fa-users fa-2x text-primary"></i>
                                            </div>
                                            <h5 class="card-title mb-0">Users</h5>
                                        </div>
                                        <p class="text-muted flex-grow-1">Manage system users, roles and permissions</p>
                                        <a href="<?= BASE_URL ?>/admin/users/" class="btn btn-primary w-100">
                                            <i class="fas fa-arrow-right me-2"></i>Manage Users
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 p-3 rounded-2 me-3" style="border-radius: 8px !important;">
                                                <i class="fas fa-chalkboard fa-2x text-primary"></i>
                                            </div>
                                            <h5 class="card-title mb-0">Classes</h5>
                                        </div>
                                        <p class="text-muted flex-grow-1">Organize and manage classroom structures</p>
                                        <a href="<?= BASE_URL ?>/admin/classes/" class="btn btn-primary w-100">
                                            <i class="fas fa-arrow-right me-2"></i>Manage Classes
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 p-3 rounded-2 me-3" style="border-radius: 8px !important;">
                                                <i class="fas fa-question fa-2x text-primary"></i>
                                            </div>
                                            <h5 class="card-title mb-0">Quizzes</h5>
                                        </div>
                                        <p class="text-muted flex-grow-1">Create and manage assessment content</p>
                                        <a href="<?= BASE_URL ?>/admin/quizzes/" class="btn btn-primary w-100">
                                            <i class="fas fa-arrow-right me-2"></i>Manage Quizzes
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 p-3 rounded-2 me-3" style="border-radius: 8px !important;">
                                                <i class="fas fa-cog fa-2x text-primary"></i>
                                            </div>
                                            <h5 class="card-title mb-0">Settings</h5>
                                        </div>
                                        <p class="text-muted flex-grow-1">Configure system preferences and options</p>
                                        <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-primary w-100">
                                            <i class="fas fa-arrow-right me-2"></i>Manage Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="recent-data">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary"><i class="fas fa-users me-2"></i>Recent Users</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentUsers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td><span class="badge bg-<?= $user['role'] === ROLE_ADMIN ? 'danger' : ($user['role'] === ROLE_TEACHER ? 'primary' : 'success') ?>"><?= ucfirst($user['role']) ?></span></td>
                                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="<?= BASE_URL ?>/admin/users/" class="btn btn-sm btn-outline-primary">View All Users</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="lead">No users found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary"><i class="fas fa-question-circle me-2"></i>Recent Quizzes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentQuizzes) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentQuizzes as $quiz): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($quiz['title']) ?></td>
                                                <td><?= htmlspecialchars($quiz['creator_name']) ?></td>
                                                <td>
                                                    <?php if ($quiz['is_published']): ?>
                                                        <span class="badge bg-success">Published</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Draft</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($quiz['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="<?= BASE_URL ?>/admin/quizzes/" class="btn btn-sm btn-outline-primary">View All Quizzes</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                <p class="lead">No quizzes found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (count($sharedQuizzes) > 0): ?>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary"><i class="fas fa-share-alt me-2"></i>Shared With You</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($sharedQuizzes as $quiz): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm border-primary border-opacity-25">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <?php if ($quiz['is_published']): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Draft</span>
                                            <?php endif; ?>
                                            <span class="badge bg-info">
                                                <?= ucfirst($quiz['permission_level']) ?> Access
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                                    <i class="fas fa-user text-primary"></i>
                                                </div>
                                                <small class="text-muted">Shared by: <?= htmlspecialchars($quiz['owner_name']) ?></small>
                                            </div>
                                            
                                            <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                            <?php if (!empty($quiz['description'])): ?>
                                                <p class="card-text mb-3">
                                                    <?= nl2br(htmlspecialchars(substr($quiz['description'], 0, 100))) ?>
                                                    <?= strlen($quiz['description']) > 100 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="row text-center mb-3">
                                                <div class="col-4">
                                                    <div class="border rounded py-2">
                                                        <div class="h4 mb-0"><?= $quiz['question_count'] ?></div>
                                                        <div class="small text-muted">Questions</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded py-2">
                                                        <div class="h4 mb-0"><?= $quiz['attempt_count'] ?></div>
                                                        <div class="small text-muted">Attempts</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded py-2">
                                                        <div class="h4 mb-0"><?= $quiz['completed_count'] ?></div>
                                                        <div class="small text-muted">Completed</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>/admin/quizzes/view_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                
                                                <?php if (in_array($quiz['permission_level'], ['edit', 'full'])): ?>
                                                    <a href="<?= BASE_URL ?>/admin/quizzes/edit_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
