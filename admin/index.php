<?php
require_once __DIR__ . '/../config.php';

// Check if user is admin
if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();

// Get user statistics
$userStats = [
    'totalUsers' => $db->single("SELECT COUNT(*) as count FROM users", [])['count'],
    'totalTeachers' => $db->single("SELECT COUNT(*) as count FROM users WHERE role = ?", [ROLE_TEACHER])['count'],
    'totalStudents' => $db->single("SELECT COUNT(*) as count FROM users WHERE role = ?", [ROLE_STUDENT])['count']
];

// Get quiz statistics
$quizStats = [
    'totalQuizzes' => $db->single("SELECT COUNT(*) as count FROM quizzes", [])['count'],
    'totalPublishedQuizzes' => $db->single("SELECT COUNT(*) as count FROM quizzes WHERE is_published = 1", [])['count'],
    'totalClasses' => $db->single("SELECT COUNT(*) as count FROM classes", [])['count'],
    'totalAttempts' => $db->single("SELECT COUNT(*) as count FROM quiz_attempts", [])['count']
];

// Get recent users
$recentUsers = $db->resultSet("SELECT * FROM users ORDER BY created_at DESC LIMIT 5", []);

// Get recent quizzes
$recentQuizzes = $db->resultSet("
    SELECT q.*, u.username as creator_name 
    FROM quizzes q 
    JOIN users u ON q.created_by = u.id 
    ORDER BY q.created_at DESC LIMIT 5
", []);

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
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                        <h5>User Management</h5>
                                        <p class="text-muted">Add, edit, or remove users from the system</p>
                                        <a href="<?= BASE_URL ?>/admin/users/" class="btn btn-outline-primary">Manage Users</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chalkboard fa-3x text-success mb-3"></i>
                                        <h5>Class Management</h5>
                                        <p class="text-muted">Create and organize classes for teachers and students</p>
                                        <a href="<?= BASE_URL ?>/admin/classes/" class="btn btn-outline-success">Manage Classes</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-cog fa-3x text-secondary mb-3"></i>
                                        <h5>System Settings</h5>
                                        <p class="text-muted">Configure system settings and preferences</p>
                                        <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-outline-secondary">Manage Settings</a>
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
        <div class="recent-section">
            <h3>Recent Users</h3>
            <?php if (count($recentUsers) > 0): ?>
                <table class="data-table">
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
                                <td><?= $user['username'] ?></td>
                                <td><?= ucfirst($user['role']) ?></td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        </div>
        
        <div class="recent-section">
            <h3>Recent Quizzes</h3>
            <?php if (count($recentQuizzes) > 0): ?>
                <table class="data-table">
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
                                <td><?= $quiz['title'] ?></td>
                                <td><?= $quiz['creator_name'] ?></td>
                                <td><?= $quiz['is_published'] ? 'Published' : 'Draft' ?></td>
                                <td><?= date('M d, Y', strtotime($quiz['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No quizzes found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
