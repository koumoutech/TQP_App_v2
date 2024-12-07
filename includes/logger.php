<?php
class Logger {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function log($user_id, $action, $details = null) {
        $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
                  VALUES (:user_id, :action, :details, :ip_address, NOW())";
                  
        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute([
                ':user_id' => $user_id,
                ':action' => $action,
                ':details' => $details,
                ':ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            return true;
        } catch(PDOException $e) {
            error_log("Erreur de journalisation: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentLogs($limit = 100) {
        $query = "SELECT l.*, u.username 
                  FROM activity_logs l
                  JOIN users u ON l.user_id = u.id
                  ORDER BY l.created_at DESC 
                  LIMIT :limit";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserLogs($user_id, $limit = 50) {
        $query = "SELECT * FROM activity_logs 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 