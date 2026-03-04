<?php
class ContactController {
    private $userModel;
    private $messageModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->messageModel = new Message();
    }
    
    public function index() {
        $message = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Validate reCAPTCHA if enabled
    if (RECAPTCHA_ENABLED && !empty(RECAPTCHA_SITE_KEY)) {
        if (empty($recaptcha_response)) {
            $error = 'Please complete the security verification.';
        } elseif (!Security::verifyRecaptcha($recaptcha_response)) {
            $error = 'Security verification failed. Please try again.';
        }
    }
    
            
            if (empty($name) || empty($email) || empty($message_text)) {
                $error = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Save message to database
                $data = [
                    'name' => $name,
                    'email' => $email,
                    'message' => $message_text,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ];
                
                if ($this->messageModel->create($data)) {
                    $message = 'Thank you for your message! I will get back to you soon.';
                    
                    // Optional: Send email notification
                    $this->sendEmailNotification($name, $email, $message_text);
                    
                    // Clear form
                    $_POST = [];
                } else {
                    $error = 'Sorry, there was an error sending your message. Please try again.';
                }
            }
        }
        
        // Get admin email for contact info
        $admin_email = 'letswrite14@gmail.com';
        if (isset($_SESSION['user_id'])) {
            $profile = $this->userModel->getProfile($_SESSION['user_id']);
            if (!empty($profile['email'])) {
                $admin_email = $profile['email'];
            }
        }
        
        $data = [
            'pageTitle' => 'Contact',
            'message' => $message,
            'error' => $error,
            'admin_email' => $admin_email,
            'post' => $_POST
        ];
        
        $this->view('contact/index', $data);
    }
    
    private function sendEmailNotification($name, $email, $message_text) {
        // Get admin email from profile
        $admin_email = 'letswrite14@gmail.com';
        if (isset($_SESSION['user_id'])) {
            $profile = $this->userModel->getProfile($_SESSION['user_id']);
            if (!empty($profile['email'])) {
                $admin_email = $profile['email'];
            }
        }
        
        // Email headers
        $headers = "From: " . SITE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Email subject
        $subject = "New Contact Message from " . SITE_NAME;
        
        // Email body
        $body = "<h2>New Contact Form Submission</h2>";
        $body .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
        $body .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        $body .= "<p><strong>Message:</strong></p>";
        $body .= "<p>" . nl2br(htmlspecialchars($message_text)) . "</p>";
        $body .= "<hr>";
        $body .= "<p><small>IP Address: " . $_SERVER['REMOTE_ADDR'] . "</small></p>";
        $body .= "<p><small>Time: " . date('Y-m-d H:i:s') . "</small></p>";
        
        // Send email (comment out if you don't want emails)
        // mail($admin_email, $subject, $body, $headers);
    }
    
    protected function view($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/{$view}.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>