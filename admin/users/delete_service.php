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
    
    // Récupérer les données JSON
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID du service manquant']);
        exit();
    }

    $conn->beginTransaction();

    // Vérifier si le service est utilisé
    $check_query = "SELECT COUNT(*) FROM users WHERE service = :name";
    $stmt = $conn->prepare($check_query);
    $stmt->execute([':name' => $name]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ce service ne peut pas être supprimé car il est utilisé par des utilisateurs'
        ]);
        exit();
    }

    // Supprimer le service
    $query = "DELETE FROM services WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Suppression d'un service", 
        "Service supprimé: $name");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Service supprimé avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 