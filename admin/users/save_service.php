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
    
    $name = strtoupper(trim($_POST['name'] ?? ''));
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Le nom du service est requis']);
        exit();
    }

    $conn->beginTransaction();

    // Vérifier si le service existe déjà
    $check_query = "SELECT id FROM services WHERE UPPER(name) = :name";
    $stmt = $conn->prepare($check_query);
    $stmt->execute([':name' => $name]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce service existe déjà']);
        exit();
    }

    // Ajouter le nouveau service
    $query = "INSERT INTO services (name) VALUES (:name)";
    $stmt = $conn->prepare($query);
    $stmt->execute([':name' => $name]);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Ajout d'un service", 
        "Nouveau service: $name");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Service ajouté avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Erreur dans save_service.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 