<?php
require_once "../config.php";
if (isset($_SESSION['user']) && isset($_SESSION['user']['role'])) {
    $role = $_SESSION['user']['role'];
    
    if ($role === ROLE_ADMIN) {
        header("Location: ../admin/");
        exit;
    } else if ($role === ROLE_TEACHER) {
        header("Location: ../teacher/");
        exit;
    } else if ($role === ROLE_STUDENT) {
        header("Location: ../student/");
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            $db = new Database();
            $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Login attempt: Username: $username, Password: *****, User found: " . ($user ? 'Yes' : 'No'));
            
            if ($user) {
                error_log("Password verification: " . ($password == 'admin123' ? 'Password matches admin123' : 'Password does not match admin123'));
                error_log("Stored password hash: " . substr($user['password'], 0, 20) . "...");
                error_log("Password verify result: " . (password_verify($password, $user['password']) ? 'True' : 'False'));
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ];
                    
                    error_log("User {$user['username']} logged in successfully with role {$user['role']}");
                    if ($user['role'] === ROLE_ADMIN) {
                        header("Location: ../admin/");
                        exit;
                    } else if ($user['role'] === ROLE_TEACHER) {
                        header("Location: ../teacher/");
                        exit;
                    } else if ($user['role'] === ROLE_STUDENT) {
                        header("Location: ../student/");
                        exit;
                    }
                } else {
                    error_log("Failed login attempt for username: $username - Password verification failed");
                    $error = "Invalid username or password.";
                }
            } else {
                error_log("Failed login attempt for username: $username - User not found");
                $error = "Invalid username or password.";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred during login. Please try again.";
        }
    }
}



$pageTitle = "Login";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizLabs - <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .login-container {
            height: 100vh;
            display: flex;
            align-items: center;
            background: url('../public/images/background.png') no-repeat center center;
            background-size: cover;
            overflow: hidden;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        .login-sidebar {
            background-color: #4257b2;
            color: white;
            padding: 3rem 1.5rem;
            position: relative;
        }
        .login-sidebar::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuXzAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxMCIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PHBhdGggZD0iTSAwIDUgTCA1IDAgTCAxMCA1IEwgNSAxMCBaIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC4xKSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI3BhdHRlcm5fMCkiLz48L3N2Zz4=');
            opacity: 0.2;
            z-index: 0;
        }
        .login-sidebar-content {
            position: relative;
            z-index: 1;
        }
        .login-form {
            padding: 3rem 2rem;
        }
        .feature-list {
            padding-left: 1.5rem;
        }
        .feature-list li {
            margin-bottom: 1rem;
            position: relative;
        }
        .feature-list li::before {
            content: "✓";
            position: absolute;
            left: -1.5rem;
            color: #ffcd1f;
        }
        .quick-login-card {
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card login-card">
                        <div class="row g-0">
                            <div class="col-md-5 d-none d-md-block login-sidebar">
                                <div class="login-sidebar-content">
                                    <div class="mb-5">
                                        <h2 class="h3 mb-4">QuizLabs - 培正</h2>
                                    </div>
                                    
                                    <div>
                                        <h3 class="h5 mb-4">Advanced quiz management system</h3>
                                        <ul class="feature-list">
                                            <li>AI-powered quiz grading</li>
                                            <li>Student performance tracking</li>
                                            <li>Detailed analytics</li>
                                            <li>Class organization</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-7 login-form">
                                <div class="text-center d-md-none mb-4">
                                    <h2 class="h3">QuizLabs - 培正</h2>
                                </div>
                                
                                <h1 class="h3 mb-4"><?php echo $pageTitle; ?></h1>
                                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="login.php" method="post">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   placeholder="Enter your username" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Enter your password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login
                                        </button>
                                    </div>
                                </form>
                                
                                <?php if (defined('QUICK_LOGIN_ENABLED') && QUICK_LOGIN_ENABLED): ?>
                                    <div class="card quick-login-card mt-4">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-bolt text-warning me-2"></i>Quick Login
                                            </h5>
                                            <p class="card-text small text-muted mb-3">For development purposes only</p>
                                            
                                            <div class="d-flex gap-2 flex-wrap">
                                                <form action="login.php" method="post">
                                                    <input type="hidden" name="username" value="admin">
                                                    <input type="hidden" name="password" value="admin123">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-user-shield me-1"></i>Admin
                                                    </button>
                                                </form>
                                                
                                                <form action="login.php" method="post">
                                                    <input type="hidden" name="username" value="teacher">
                                                    <input type="hidden" name="password" value="teacher123">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-chalkboard-teacher me-1"></i>Teacher
                                                    </button>
                                                </form>
                                                
                                                <form action="login.php" method="post">
                                                    <input type="hidden" name="username" value="student">
                                                    <input type="hidden" name="password" value="student123">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-user-graduate me-1"></i>Student
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
