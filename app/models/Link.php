<?php
class Link {
    private $db;
    private $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->table = Database::getInstance()->table('links');
    }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (title, url, description, image_path, category) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title'],
            $data['url'],
            $data['description'],
            $data['image_path'],
            $data['category']
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET title = ?, url = ?, description = ?, image_path = ?, category = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['url'],
            $data['description'],
            $data['image_path'],
            $data['category'],
            $id
        ]);
    }
    
    public function delete($id) {
        // Get image path before deleting
        $link = $this->getById($id);
        
        // Delete from database
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        // Delete image file if exists
        if ($result && $link && !empty($link['image_path'])) {
            $image_path = BASE_PATH . '/' . $link['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        return $result;
    }
    
    public function handleImageUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        
        // Validate file
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size must be less than 2MB.');
        }
        
        if (!in_array($file['type'], ALLOWED_TYPES)) {
            throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed.');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $target_path = UPLOAD_PATH . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return 'uploads/' . $filename;
        }
        
        throw new Exception('Failed to upload image.');
    }
}
?>