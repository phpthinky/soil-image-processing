<?php
class Message {
    private $db;
    private $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->table = Database::getInstance()->table('messages');
    }
    
    public function getAll($limit = 100, $order = 'DESC') {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY created_at $order LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getUnreadCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE is_read = 0");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (name, email, message, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['message'],
            $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
            $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    
    public function markAsRead($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function getRecent($limit = 5) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>