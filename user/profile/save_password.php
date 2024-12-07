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
    $db = new Database();
    $conn = $db->getConnection();
    
    // Récupérer les données du formulaire
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Vérifier que tous les champs sont remplis
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        throw new Exception('Tous les champs sont requis');
    }
    
    // Vérifier que les mots de passe correspondent
    if ($new_password !== $confirm_password) {
        throw new Exception('Les mots de passe ne correspondent pas');
    }
    
    // Vérifier la complexité du mot de passe
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $new_password)) {
        throw new Exception('Le mot de passe ne respecte pas les critères de sécurité');
    }
    
    // Vérifier le mot de passe actuel
    $query = "SELECT password FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($current_password, $user['password'])) {
        throw new Exception('Le mot de passe actuel est incorrect');
    }
    
    // Mettre à jour le mot de passe
    $query = "UPDATE users 
              SET password = :password,
                  updated_at = NOW()
              WHERE id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':password' => password_hash($new_password, PASSWORD_DEFAULT),
        ':user_id' => $_SESSION['user_id']
    ]);
    
    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], 'Changement de mot de passe', 'Mot de passe modifié avec succès');
    
    echo json_encode([
        'success' => true,
        'message' => 'Votre mot de passe a été modifié avec succès'
    ]);

} catch (Exception $e) {
    error_log("Erreur change_password: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}