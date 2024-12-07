<?php
session_start();
require_once '../../config/database.php';

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
    
    $query = "SELECT * FROM account_users 
              WHERE account_id = :account_id 
              ORDER BY employee_name";
    $stmt = $conn->prepare($query);
    $stmt->execute([':account_id' => $account_id]);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (PDOException $e) {
    error_log("Erreur get_account_users: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des utilisateurs'
    ]);
} 