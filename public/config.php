<?php
// KDesk Helpdesk System - Configuration File

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kdesk_helpdesk');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'KDesk');
define('APP_URL', 'http://localhost');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Pagination
define('ITEMS_PER_PAGE', 20);

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
