<?php
class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function login() {
        // Check if login is blocked
        if (SECURITY_ENABLED && Security::isLoginBlocked()) {
            $this->render('admin/login_blocked', ['pageTitle' => 'Login Blocked']);
            return;
        }
        
        // Redirect if already logged in
        if ($this->userModel->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'admin/dashboard');
            exit();
        }
        
        $error = '';
        $attempts = SECURITY_ENABLED ? Security::getLoginAttempts() : 0;
        $show_captcha = Security::shouldShowCaptcha();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
            
            // Validate CAPTCHA if required
            if ($show_captcha) {
                if (empty($recaptcha_response)) {
                    $error = 'Please complete the security verification.';
                }
            }
            
            if (empty($error)) {
                if ($this->userModel->login($username, $password)) {
                    // Reset on success
                    if (SECURITY_ENABLED) {
                        Security::resetOnSuccess();
                    }
                    
                    header('Location: ' . BASE_URL . 'admin/dashboard');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                    
                    // Increment attempts
                    if (SECURITY_ENABLED) {
                        Security::incrementLoginAttempts();
                        $attempts = Security::getLoginAttempts();
                        $show_captcha = Security::shouldShowCaptcha();
                    }
                }
            }
        }
        
        $data = [
            'pageTitle' => 'Login',
            'error' => $error,
            'attempts' => $attempts,
            'show_captcha' => $show_captcha
        ];
        
        $this->render('admin/login', $data);
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . 'admin/login');
        exit();
    }
    
    protected function render($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/{$view}.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>