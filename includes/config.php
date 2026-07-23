<?php
/**
 * Online Job Recruitment System - Configuration
 * Update database credentials to match your local XAMPP/WAMP setup.
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    die('Direct access not permitted.');
}

// Database configuration (XAMPP defaults; Docker overrides via environment)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'job_recruitment');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'JobConnect');
define('APP_TAGLINE', 'Online Job Recruitment System');
define('APP_URL', 'http://localhost/Online%20Job%20Recruitment');

// Paths (absolute filesystem)
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('RESUME_PATH', UPLOAD_PATH . '/resumes');
define('PHOTO_PATH', UPLOAD_PATH . '/photos');
define('LOGO_PATH', UPLOAD_PATH . '/logos');

// Upload limits (bytes)
define('MAX_RESUME_SIZE', 2 * 1024 * 1024);   // 2 MB
define('MAX_IMAGE_SIZE', 1 * 1024 * 1024);    // 1 MB

// Allowed MIME types
define('ALLOWED_RESUME_TYPES', ['application/pdf']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Session
define('SESSION_NAME', 'jobconnect_session');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
