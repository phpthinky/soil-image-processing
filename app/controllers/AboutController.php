<?php
class AboutController {
    public function index() {
        // Calculate years of experience
        $startYear = 2013;
        $currentYear = date('Y');
        $yearsOfExperience = $currentYear - $startYear;
        
        $data = [
            'pageTitle' => 'About',
            'yearsOfExperience' => $yearsOfExperience,
            'startYear' => $startYear
        ];
        
        $this->view('about/index', $data);
    }
    
    protected function view($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/{$view}.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>