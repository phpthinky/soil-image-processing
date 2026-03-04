<?php
class AdminController {
    private $userModel;
    private $linkModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->linkModel = new Link();
        $this->userModel->requireLogin();
    }
    public function index() {
        $this->dashboard(); // Redirect to dashboard
    }
    public function dashboard() {
        $message = '';
        $error = '';
        
        // Handle delete
        if (isset($_GET['delete'])) {
            if ($this->linkModel->delete($_GET['delete'])) {
                $message = 'Link deleted successfully.';
            } else {
                $error = 'Failed to delete link.';
            }
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $image_path = '';
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $image_path = $this->linkModel->handleImageUpload($_FILES['image']);
                }
                
                $data = [
                    'title' => trim($_POST['title']),
                    'url' => trim($_POST['url']),
                    'description' => trim($_POST['description']),
                    'image_path' => $image_path,
                    'category' => trim($_POST['category'] ?? 'project')
                ];
                
                // Validate
                if (empty($data['title']) || empty($data['url'])) {
                    $error = 'Title and URL are required.';
                } elseif (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                    $error = 'Please enter a valid URL.';
                } elseif ($this->linkModel->create($data)) {
                    $message = 'Link added successfully!';
                    $_POST = []; // Clear form
                } else {
                    $error = 'Failed to add link.';
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $data = [
            'pageTitle' => 'Admin Dashboard',
            'message' => $message,
            'error' => $error,
            'links' => $this->linkModel->getAll(),
            'post' => $_POST
        ];
        
        $this->view('admin/dashboard', $data);
    }
    
    protected function view($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/admin/layout/header.php";
        require_once APP_PATH . "/views/{$view}.php";
        require_once APP_PATH . "/views/admin/layout/footer.php";
    }
}
?>