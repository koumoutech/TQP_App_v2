<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé'
    ]);
    exit();
}

try {
    $account_id = $_GET['id'] ?? null;
    if (!$account_id) {
        throw new Exception('ID du compte manquant');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer les détails du compte
    $query = "SELECT a.*, au.status as user_status, au.username
              FROM app_accounts a
              INNER JOIN account_users au ON a.id = au.account_id
              WHERE a.id = :id AND au.user_id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':id' => $account_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new Exception('Compte non trouvé');
    }

    echo json_encode([
        'success' => true,
        'account' => [
            'id' => $account['id'],
            'name' => htmlspecialchars($account['name']),
            'description' => htmlspecialchars($account['description']),
            'username' => htmlspecialchars($account['username']),
            'login_url' => htmlspecialchars($account['login_url']),
            'status' => $account['user_status']
        ]
    ]);

} catch (Exception $e) {
    error_log("Erreur get_account_details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 