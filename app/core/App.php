<?php
class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    
    public function __construct() {
        $url = $this->parseUrl();
        $this->dispatch($url);
    }
    
    private function parseUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }
        return [];
    }
    
    private function dispatch($url) {
        // Skip routing for asset files (images, css, js)
        if ($this->isAssetRequest($url)) {
            return;
        }
        
        // Define all routes
        $routes = [
            ''                 => ['HomeController', 'index'],
            'about'            => ['AboutController', 'index'],
            'contact'          => ['ContactController', 'index'],
            'portfolio'        => ['PortfolioController', 'index'],
            
            // Auth routes
            'login'            => ['AuthController', 'login'],
            'logout'           => ['AuthController', 'logout'],
            'admin/login'      => ['AuthController', 'login'],
            'admin/logout'     => ['AuthController', 'logout'],
            
            // Admin routes
            'admin'            => ['AdminController', 'dashboard'],
            'admin/dashboard'  => ['AdminController', 'dashboard'],
            'admin/profile'    => ['ProfileController', 'index'],
            'admin/messages'   => ['MessagesController', 'index'],
            
            // Message viewing route pattern
            'admin/messages/view' => ['MessagesController', 'view']
        ];
        
        $urlPath = implode('/', $url);
        
        // Check exact routes first
        if (isset($routes[$urlPath])) {
            list($this->controller, $this->method) = $routes[$urlPath];
        }
        // Handle message view with ID
        else if (isset($url[0]) && $url[0] == 'admin' && 
                 isset($url[1]) && $url[1] == 'messages' && 
                 isset($url[2]) && $url[2] == 'view') {
            
            $this->controller = 'MessagesController';
            $this->method = 'view';
            
            // Get the message ID if provided
            if (isset($url[3])) {
                $this->params = [$url[3]];
                unset($url[3]);
            }
            
            unset($url[0], $url[1], $url[2]);
        }
        // Handle other dynamic routes
        else {
            $this->handleDynamicRoute($url);
        }
        
        // Execute the controller
        $this->executeController();
    }
    
    private function isAssetRequest($url) {
        if (empty($url)) return false;
        
        $assetExtensions = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'css', 'js', 'webp', 'ttf', 'woff', 'woff2', 'eot'];
        
        $lastSegment = end($url);
        $extension = pathinfo($lastSegment, PATHINFO_EXTENSION);
        
        return in_array(strtolower($extension), $assetExtensions);
    }
    
    private function handleDynamicRoute(&$url) {
        if (empty($url[0])) return;
        
        $controllerName = ucfirst($url[0]) . 'Controller';
        
        if (file_exists(APP_PATH . '/controllers/' . $controllerName . '.php')) {
            $this->controller = $controllerName;
            
            if (!empty($url[1])) {
                // Load controller to check methods
                require_once APP_PATH . '/controllers/' . $this->controller . '.php';
                
                if (method_exists($this->controller, $url[1])) {
                    $this->method = $url[1];
                    unset($url[0], $url[1]);
                } else {
                    $this->method = 'index';
                    unset($url[0]);
                }
            } else {
                unset($url[0]);
            }
            
            $this->params = array_values($url);
        }
    }
    
    private function executeController() {
        require_once APP_PATH . '/controllers/' . $this->controller . '.php';
        $controllerInstance = new $this->controller;
        call_user_func_array([$controllerInstance, $this->method], $this->params);
    }
}