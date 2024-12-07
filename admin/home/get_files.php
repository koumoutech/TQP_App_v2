<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM files ORDER BY created_at DESC";
    $stmt = $conn->query($query);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($files);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 