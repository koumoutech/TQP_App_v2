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
    $title = trim($_POST['title']);
    $services = $_POST['services'] ?? [];
    $duration = (int)$_POST['duration'];
    
    // Validation
    if (empty($title) || empty($services) || $duration < 1) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs correctement']);
        exit();
    }

    $conn->beginTransaction();

    if ($quiz_id) {
        // Mise à jour
        $query = "UPDATE quizzes SET title = :title, duration = :duration WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':title' => $title,
            ':duration' => $duration,
            ':id' => $quiz_id
        ]);

        // Supprimer les anciens services
        $delete_services = "DELETE FROM quiz_services WHERE quiz_id = :quiz_id";
        $stmt = $conn->prepare($delete_services);
        $stmt->execute([':quiz_id' => $quiz_id]);
    } else {
        // Création
        $query = "INSERT INTO quizzes (title, duration) VALUES (:title, :duration)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':title' => $title,
            ':duration' => $duration
        ]);
        $quiz_id = $conn->lastInsertId();
    }

    // Ajouter les nouveaux services
    $insert_service = "INSERT INTO quiz_services (quiz_id, service_name) VALUES (:quiz_id, :service)";
    $stmt = $conn->prepare($insert_service);
    foreach ($services as $service) {
        $stmt->execute([
            ':quiz_id' => $quiz_id,
            ':service' => $service
        ]);
    }

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], 
        $quiz_id ? "Modification du quiz" : "Création d'un quiz", 
        "Quiz: $title");

    $conn->commit();

    // Améliorer les messages
    $message = $quiz_id ? 'Quiz modifié avec succès' : 'Quiz créé avec succès';
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 