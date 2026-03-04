<?php
class Autoload {
    public static function register() {
        spl_autoload_register(function ($class) {
            // Define directories to check
            $directories = [
                APP_PATH . '/core/',
                APP_PATH . '/controllers/',
                APP_PATH . '/models/',
                APP_PATH . '/config/'
            ];
            
            // Check in defined directories
            foreach ($directories as $directory) {
                $file = $directory . $class . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
            
            // If not found, try with namespace
            $class = str_replace('\\', '/', $class);
            $file = APP_PATH . '/' . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            
            // Try in public directory for any helper classes
            $file = PUBLIC_PATH . '/' . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            
            return false;
        });
    }
}