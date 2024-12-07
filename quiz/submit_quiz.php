<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user_quiz.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$quiz_id = $_POST['quiz_id'] ?? null;
$answers = $_POST['answer'] ?? [];

if (!$quiz_id || empty($answers)) {
    $_SESSION['error'] = "Données de quiz invalides";
    header('Location: user_quiz.php');
    exit();
}

try {
    $conn->beginTransaction();
    
    // Calculer le score
    $total_questions = count($answers);
    $correct_answers = 0;
    
    foreach ($answers as $question_id => $answer_id) {
        $check_query = "SELECT is_correct FROM answers WHERE id = :answer_id AND question_id = :question_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([
            ':answer_id' => $answer_id,
            ':question_id' => $question_id
        ]);
        
        if ($check_stmt->fetchColumn()) {
            $correct_answers++;
        }
    }
    
    $score = ($correct_answers / $total_questions) * 100;
    
    // Enregistrer le résultat
    $result_query = "INSERT INTO quiz_results (user_id, quiz_id, score, completed_at) 
                     VALUES (:user_id, :quiz_id, :score, NOW())";
    $result_stmt = $conn->prepare($result_query);
    $result_stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':quiz_id' => $quiz_id,
        ':score' => $score
    ]);
    
    // Enregistrer les réponses détaillées
    $answers_query = "INSERT INTO quiz_answers (result_id, question_id, answer_id) 
                     VALUES (:result_id, :question_id, :answer_id)";
    $answers_stmt = $conn->prepare($answers_query);
    
    $result_id = $conn->lastInsertId();
    foreach ($answers as $question_id => $answer_id) {
        $answers_stmt->execute([
            ':result_id' => $result_id,
            ':question_id' => $question_id,
            ':answer_id' => $answer_id
        ]);
    }
    
    $conn->commit();
    
    // Rediriger vers la page des résultats
    header('Location: quiz_result.php?id=' . $result_id);
    exit();
    
} catch(PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Erreur lors de l'enregistrement du quiz: " . $e->getMessage();
    header('Location: user_quiz.php');
    exit();
} 