<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $account_id = $data['account_id'] ?? null;
    
    if (!$account_id) {
        echo json_encode(['success' => false, 'message' => 'ID compte manquant']);
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    // Récupérer les infos du compte avant suppression
    $query = "SELECT name FROM app_accounts WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $account_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        echo json_encode(['success' => false, 'message' => 'Compte non trouvé']);
        exit();
    }

    // Supprimer les utilisateurs associés
    $query = "DELETE FROM account_users WHERE account_id = :account_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':account_id' => $account_id]);

    // Supprimer le compte
    $query = "DELETE FROM app_accounts WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $account_id]);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Suppression d'un compte", 
        "Compte: {$account['name']}");

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Compte supprimé avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Erreur delete_account: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la suppression'
    ]);
} 