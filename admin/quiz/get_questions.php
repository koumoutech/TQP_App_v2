<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    echo json_encode(['success' => false, 'message' => 'ID quiz manquant']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // D'abord, récupérer toutes les questions
    $query = "SELECT * FROM questions WHERE quiz_id = :quiz_id ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':quiz_id' => $quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensuite, pour chaque question, récupérer ses réponses
    $result = [];
    foreach ($questions as $question) {
        $answersQuery = "SELECT id, answer, is_correct 
                        FROM answers 
                        WHERE question_id = :question_id 
                        ORDER BY id";
        $stmt = $conn->prepare($answersQuery);
        $stmt->execute([':question_id' => $question['id']]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result[] = [
            'id' => $question['id'],
            'question' => $question['question'],
            'quiz_id' => $question['quiz_id'],
            'answers' => $answers
        ];
    }
    
    echo json_encode($result);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors du chargement des questions: ' . $e->getMessage()
    ]);
} 