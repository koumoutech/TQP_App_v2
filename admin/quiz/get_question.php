<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

$question_id = $_GET['id'] ?? null;

if (!$question_id) {
    echo json_encode(['success' => false, 'message' => 'ID question manquant']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT q.*, 
              GROUP_CONCAT(
                  JSON_OBJECT(
                      'id', a.id,
                      'answer', a.answer,
                      'is_correct', a.is_correct
                  )
              ) as answers
              FROM questions q
              LEFT JOIN answers a ON q.id = a.question_id
              WHERE q.id = :id
              GROUP BY q.id";
              
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $question_id]);
    
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($question) {
        $question['answers'] = array_map(function($answer) {
            return json_decode($answer, true);
        }, explode(',', $question['answers']));
        
        $question['success'] = true;
        echo json_encode($question);
    } else {
        echo json_encode(['success' => false, 'message' => 'Question non trouvée']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 