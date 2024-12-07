<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

$quiz_id = $_GET['id'] ?? null;

if (!$quiz_id) {
    echo json_encode(['success' => false, 'message' => 'ID quiz manquant']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT q.*, GROUP_CONCAT(qs.service_name) as services 
              FROM quizzes q 
              LEFT JOIN quiz_services qs ON q.id = qs.quiz_id 
              WHERE q.id = :id 
              GROUP BY q.id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $quiz_id]);
    
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($quiz) {
        $quiz['success'] = true;
        echo json_encode($quiz);
    } else {
        echo json_encode(['success' => false, 'message' => 'Quiz non trouvé']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 