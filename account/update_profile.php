<?php
session_start();
require_once '../config/database.php';
require_once '../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $email = trim($_POST['email'] ?? '');
    
    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit();
    }
    
    // Vérifier si l'email existe déjà pour un autre utilisateur
    $check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
    $stmt = $conn->prepare($check_query);
    $stmt->execute([
        ':email' => $email,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
        exit();
    }
    
    // Mettre à jour le profil
    $query = "UPDATE users SET email = :email WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':email' => $email,
        ':id' => $_SESSION['user_id']
    ]);
    
    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], 'Mise à jour du profil', 'Email mis à jour');
    
    echo json_encode([
        'success' => true,
        'message' => 'Profil mis à jour avec succès'
    ]);

} catch (PDOException $e) {
    error_log("Erreur mise à jour profil: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la mise à jour'
    ]);
} 