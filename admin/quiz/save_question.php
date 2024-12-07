<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $quiz_id = $_POST['quiz_id'] ?? null;
    $question_text = trim($_POST['question']);
    $answers = $_POST['answers'] ?? [];
    $correct_answer = (int)$_POST['correct_answer'];
    
    // Validation
    if (empty($quiz_id) || empty($question_text) || empty($answers)) {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit();
    }

    if (!isset($answers[$correct_answer])) {
        echo json_encode(['success' => false, 'message' => 'Réponse correcte invalide']);
        exit();
    }

    $conn->beginTransaction();

    // Insérer la question
    $query = "INSERT INTO questions (quiz_id, question) VALUES (:quiz_id, :question)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':quiz_id' => $quiz_id,
        ':question' => $question_text
    ]);
    
    $question_id = $conn->lastInsertId();

    // Insérer les réponses
    $answer_query = "INSERT INTO answers (question_id, answer, is_correct) 
                    VALUES (:question_id, :answer, :is_correct)";
    $answer_stmt = $conn->prepare($answer_query);

    foreach ($answers as $index => $answer) {
        $answer_stmt->execute([
            ':question_id' => $question_id,
            ':answer' => trim($answer),
            ':is_correct' => $index === $correct_answer
        ]);
    }

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Ajout d'une question", 
        "Question ajoutée au quiz ID: $quiz_id");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Question ajoutée avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 