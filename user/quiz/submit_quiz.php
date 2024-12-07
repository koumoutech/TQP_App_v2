<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Récupérer l'ID du quiz
    $quiz_id = $_POST['quiz_id'] ?? null;
    if (!$quiz_id) {
        throw new Exception('ID du quiz manquant');
    }

    // Vérifier que le quiz existe et est actif
    $query = "SELECT * FROM quizzes WHERE id = :id AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        throw new Exception('Quiz non trouvé ou inactif');
    }

    // Commencer une transaction
    $conn->beginTransaction();

    // Créer l'enregistrement du résultat
    $query = "INSERT INTO quiz_results (user_id, quiz_id, time_taken, completed_at) 
              VALUES (:user_id, :quiz_id, :time_taken, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':quiz_id' => $quiz_id,
        ':time_taken' => $_POST['time_taken'] ?? 0
    ]);
    $result_id = $conn->lastInsertId();

    // Traiter chaque réponse
    $total_points = 0;
    $earned_points = 0;
    $correct_answers = 0;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'question_') === 0) {
            $question_id = substr($key, 9);
            
            // Récupérer les informations de la question
            $query = "SELECT points FROM quiz_questions WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id' => $question_id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si la réponse est correcte
            $query = "SELECT is_correct FROM quiz_answers WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id' => $value]);
            $answer = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_points += $question['points'];
            if ($answer['is_correct']) {
                $earned_points += $question['points'];
                $correct_answers++;
            }

            // Enregistrer la réponse
            $query = "INSERT INTO quiz_user_answers (result_id, question_id, answer_id) 
                      VALUES (:result_id, :question_id, :answer_id)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':result_id' => $result_id,
                ':question_id' => $question_id,
                ':answer_id' => $value
            ]);
        }
    }

    // Calculer le score final
    $score = ($earned_points / $total_points) * 100;

    // Mettre à jour le résultat avec le score
    $query = "UPDATE quiz_results 
              SET score = :score, 
                  total_points = :total_points,
                  earned_points = :earned_points,
                  correct_answers = :correct_answers
              WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':score' => $score,
        ':total_points' => $total_points,
        ':earned_points' => $earned_points,
        ':correct_answers' => $correct_answers,
        ':id' => $result_id
    ]);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], 'Quiz terminé', 
        "Quiz: {$quiz['title']} - Score: " . number_format($score, 1) . "%");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Quiz terminé avec succès !',
        'result_id' => $result_id,
        'score' => number_format($score, 1)
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Erreur submit_quiz: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 