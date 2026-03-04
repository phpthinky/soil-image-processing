<?php
// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);


// Start session
session_start();

// Load configuration FIRST
require_once APP_PATH . '/config/config.php';

// Register autoloader
require_once APP_PATH . '/core/Autoload.php';
Autoload::register();

// Manually load Security class since it's in config folder
require_once APP_PATH . '/config/security.php';

// Check if it's an asset request (bypass routing)
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$asset_extensions = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'css', 'js', 'webp'];

// Extract file extension
$path_info = pathinfo($request_uri);
$extension = isset($path_info['extension']) ? strtolower($path_info['extension']) : '';

// If it's an asset request, serve it directly
if (in_array($extension, $asset_extensions)) {
    // Check if file exists in public directory
    $file_path = PUBLIC_PATH . $request_uri;
    
    if (file_exists($file_path) && is_file($file_path)) {
        // Set appropriate content type
        $content_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'webp' => 'image/webp'
        ];
        
        if (isset($content_types[$extension])) {
            header('Content-Type: ' . $content_types[$extension]);
        }
        
        readfile($file_path);
        exit;
    }
}

// Initialize the application (only for non-asset requests)
$app = new App();
?>