<?php
class MessagesController {
    private $userModel;
    private $messageModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->messageModel = new Message();
        $this->userModel->requireLogin();
    }
    
    public function index() {
        $message = '';
        $error = '';
        
        // Handle actions
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            $id = $_GET['id'] ?? 0;
            
            if ($action === 'read' && $id) {
                $this->messageModel->markAsRead($id);
                $message = 'Message marked as read.';
            } elseif ($action === 'delete' && $id) {
                if ($this->messageModel->delete($id)) {
                    $message = 'Message deleted.';
                } else {
                    $error = 'Failed to delete message.';
                }
            }
        }
        
        // Get all messages
        $messages = $this->messageModel->getAll();
        $unreadCount = $this->messageModel->getUnreadCount();
        
        $data = [
            'pageTitle' => 'Messages',
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'message' => $message,
            'error' => $error
        ];
        
        $this->viewTemplate('admin/messages/index', $data); // CHANGED: view() to viewTemplate()
    }
    
    public function view($id = null) {
        // If no ID provided, redirect to messages list
        if (!$id) {
            header('Location: ' . BASE_URL . 'admin/messages');
            exit();
        }
        
        $message = $this->messageModel->getById($id);
        
        if (!$message) {
            // Message not found
            header('Location: ' . BASE_URL . 'admin/messages');
            exit();
        }
        
        // Mark as read when viewing
        $this->messageModel->markAsRead($id);
        
        $data = [
            'pageTitle' => 'View Message',
            'message' => $message
        ];
        
        $this->viewTemplate('admin/messages/view', $data);
    }
    
    // Renamed to avoid conflict with view() method
    protected function viewTemplate($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/admin/layout/header.php";
        require_once APP_PATH . "/views/{$view}.php";
        require_once APP_PATH . "/views/admin/layout/footer.php";
    }
}
?>