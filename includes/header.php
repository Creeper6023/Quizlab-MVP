<?php 
require_once __DIR__ . '/../config.php'; 
$role = $_SESSION['role'] ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/generated-icon.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/ui-components.css">
    <!-- Font Awesome for icons (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4 sleek-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>">
            <img src="<?= BASE_URL ?>/icon.png" alt="QuizLabs Logo" height="28" class="me-2">
            <span class="fw-bold">QuizLabs - 培正</span>
        </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" 
                    aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole(ROLE_ADMIN)): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cog me-1"></i>Admin
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/classes">
                                        <i class="fas fa-chalkboard me-2"></i>Manage Classes
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/settings.php">
                                        <i class="fas fa-sliders-h me-2"></i>Settings
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/users/">
                                        <i class="fas fa-users me-2"></i>User Management
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <?php if (hasRole(ROLE_TEACHER)): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="teacherDropdown" role="button" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-chalkboard-teacher me-1"></i>Teacher
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="teacherDropdown">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/teacher">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/teacher/classes">
                                        <i class="fas fa-chalkboard me-2"></i>My Classes
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/teacher/classes/create_class.php">
                                        <i class="fas fa-plus me-2"></i>Create New Class
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <?php if (hasRole(ROLE_STUDENT)): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-pencil-alt me-1"></i>Student
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="studentDropdown">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/student">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/student">
                                        <i class="fas fa-list me-2"></i>Available Quizzes
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/view_result.php">
                                        <i class="fas fa-chart-bar me-2"></i>My Results
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <?php if (isLoggedIn()): ?>
                    <div class="navbar-nav">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?= $_SESSION['username'] ?> 
                                <span class="badge bg-light text-primary ms-1"><?= ucfirst($_SESSION['role']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="navbar-nav">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <main class="container py-4 px-4">
