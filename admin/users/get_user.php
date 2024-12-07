<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT id, username, email, service, role 
              FROM users 
              WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $user['success'] = true;
        echo json_encode($user);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Utilisateur non trouvé'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
    ]);
} 