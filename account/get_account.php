<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$account_id = $_GET['id'] ?? null;
if (!$account_id) {
    echo json_encode(['success' => false, 'message' => 'ID compte manquant']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM app_accounts WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $account_id]);
    
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($account) {
        $account['success'] = true;
        echo json_encode($account);
    } else {
        echo json_encode(['success' => false, 'message' => 'Compte non trouvé']);
    }

} catch (PDOException $e) {
    error_log("Erreur get_account: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du compte']);
} 