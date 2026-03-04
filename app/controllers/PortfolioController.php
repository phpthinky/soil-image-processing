<?php
class PortfolioController {
    private $linkModel;
    
    public function __construct() {
        $this->linkModel = new Link();
    }
    
    public function index() {
        $data = [
            'pageTitle' => 'Home',
            'links' => $this->linkModel->getAll()
        ];
        
        $this->view('home/index', $data);
    }
    
    protected function view($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/{$view}.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>