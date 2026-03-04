<?php
class ProfileController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->userModel->requireLogin();
    }
    
    public function index() {
        $user_id = $_SESSION['user_id'];
        $success = '';
        $error = '';
        
        // Get user profile
        $user = $this->userModel->getProfile($user_id);
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update_profile') {
                $email = trim($_POST['email'] ?? '');
                if ($this->userModel->updateProfile($user_id, $email)) {
                    $success = 'Profile updated successfully!';
                    $user = $this->userModel->getProfile($user_id); // Refresh
                } else {
                    $error = 'Failed to update profile.';
                }
                
            } elseif ($action === 'change_password') {
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All password fields are required.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long.';
                } else {
                    $result = $this->userModel->updatePassword($user_id, $current_password, $new_password);
                    if ($result === true) {
                        $success = 'Password changed successfully!';
                        $_POST['current_password'] = $_POST['new_password'] = $_POST['confirm_password'] = '';
                    } else {
                        $error = $result;
                    }
                }
            }
        }
        
        $data = [
            'pageTitle' => 'Profile Settings',
            'user' => $user,
            'success' => $success,
            'error' => $error
        ];
        
        $this->view('admin/profile', $data);
    }
    
    protected function view($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/admin/layout/header.php";
        require_once APP_PATH . "/views/{$view}.php";
        require_once APP_PATH . "/views/admin/layout/footer.php";
    }
}
?>