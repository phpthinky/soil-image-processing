<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_USER', 'root');
define('DB_PASS', '');
define('TABLE_PREFIX', 'cv_');

// Site Configuration - FIX THIS!
define('SITE_NAME', 'Democodes Online');

// Detect protocol (http/https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Base URL - important for routing
define('SITE_URL', $protocol . '://' . $host);
define('BASE_URL', SITE_URL . '/');

// Paths - make sure these are correct
define('UPLOAD_PATH', dirname(dirname(__DIR__)) . '/uploads/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('IMAGES_URL', BASE_URL . 'images/');

// Upload Settings
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}


// Security Configuration
define('SECURITY_ENABLED', true); // Master switch for all security features

// Login Protection Settings
define('LOGIN_MAX_ATTEMPTS', 5);      // Max failed attempts before CAPTCHA/block
define('LOGIN_CAPTCHA_AFTER', 3);     // Show CAPTCHA after X failed attempts
define('LOGIN_BLOCK_AFTER', 5);       // Block after X failed attempts
define('LOGIN_BLOCK_DURATION', 900);  // Block duration in seconds (15 minutes)
define('LOGIN_DELAY_INCREMENT', 1);   // Delay increase per attempt in seconds

// reCAPTCHA Settings (set to false if not using)
define('RECAPTCHA_ENABLED', false);   // Master switch for reCAPTCHA
define('RECAPTCHA_SITE_KEY', '');     // Leave empty if not using
define('RECAPTCHA_SECRET_KEY', '');   // Leave empty if not using
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');
?>