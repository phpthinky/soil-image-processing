<?php
class User {
    private $db;
    private $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->table = Database::getInstance()->table('users');
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit();
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . 'admin/login');
        exit();
    }
    
    public function getProfile($user_id) {
        $stmt = $this->db->prepare("SELECT id, username, email, created_at FROM {$this->table} WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    public function updatePassword($user_id, $current_password, $new_password) {
        // Get current password
        $stmt = $this->db->prepare("SELECT password FROM {$this->table} WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            return 'Current password is incorrect.';
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            return true;
        }
        return 'Failed to update password.';
    }
    
    public function updateProfile($user_id, $email) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }
        
        // Check if email exists
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            return 'Email is already in use.';
        }
        
        // Update
        $stmt = $this->db->prepare("UPDATE {$this->table} SET email = ? WHERE id = ?");
        return $stmt->execute([$email, $user_id]);
    }
}
?>