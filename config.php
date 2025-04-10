<?php
// Define root path and critical directories
define('ROOT_PATH', __DIR__);
define('LIB_PATH', ROOT_PATH . '/lib');
define('CONFIG_PATH', LIB_PATH . '/config');
define('DATABASE_PATH', LIB_PATH . '/database');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('STUDENT_PATH', ROOT_PATH . '/student');
define('TEACHER_PATH', ROOT_PATH . '/teacher');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Main configuration file - includes the core configuration
require_once LIB_PATH . '/config/config.php';
