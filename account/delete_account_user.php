<?php
session_start();
require_once '../config/database.php';
require_once '../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
        exit();
    }

    $conn->beginTransaction();

    // Récupérer les infos de l'utilisateur avant suppression pour le log
    $query = "SELECT employee_name, account_id FROM account_users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }

    // Supprimer l'utilisateur
    $query = "DELETE FROM account_users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Suppression d'un employé", 
        "Compte ID: {$user['account_id']}, Employé: {$user['employee_name']}");

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Employé supprimé avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Erreur delete_account_user: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la suppression'
    ]);
} 