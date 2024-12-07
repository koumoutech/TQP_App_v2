<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

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
    
    // Récupérer les informations de la question avant suppression
    $query = "SELECT q.question, qz.title as quiz_title 
              FROM questions q
              JOIN quizzes qz ON q.quiz_id = qz.id 
              WHERE q.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'Question non trouvée']);
        exit();
    }

    $conn->beginTransaction();
    
    // Supprimer les réponses
    $query = "DELETE FROM answers WHERE question_id = :question_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':question_id' => $question_id]);
    
    // Supprimer la question
    $query = "DELETE FROM questions WHERE id = :question_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':question_id' => $question_id]);
    
    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Suppression d'une question", 
        "Question supprimée du quiz: " . $question['quiz_title']);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Question supprimée avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 