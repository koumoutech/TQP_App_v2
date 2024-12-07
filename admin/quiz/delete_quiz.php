<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$quiz_id = $_GET['id'] ?? null;

if (!$quiz_id) {
    $_SESSION['error'] = "ID quiz manquant";
    header('Location: list_quiz.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Récupérer les informations du quiz avant suppression
    $query = "SELECT title FROM quizzes WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        $_SESSION['error'] = "Quiz non trouvé";
        header('Location: list_quiz.php');
        exit();
    }

    $conn->beginTransaction();
    
    // Supprimer les réponses
    $query = "DELETE a FROM answers a 
              INNER JOIN questions q ON a.question_id = q.id 
              WHERE q.quiz_id = :quiz_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':quiz_id' => $quiz_id]);
    
    // Supprimer les questions
    $query = "DELETE FROM questions WHERE quiz_id = :quiz_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':quiz_id' => $quiz_id]);
    
    // Supprimer les résultats
    $query = "DELETE FROM quiz_results WHERE quiz_id = :quiz_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':quiz_id' => $quiz_id]);
    
    // Supprimer le quiz
    $query = "DELETE FROM quizzes WHERE id = :quiz_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':quiz_id' => $quiz_id]);
    
    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Suppression du quiz", 
        "Quiz supprimé: " . $quiz['title']);
    
    $conn->commit();
    
    $_SESSION['success'] = "Quiz supprimé avec succès";
} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

header('Location: list_quiz.php');
exit(); 