<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Vérifier si l'utilisateur existe
    $query = "SELECT username FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }

    $conn->beginTransaction();

    // Générer un nouveau mot de passe aléatoire
    $new_password = generateRandomPassword();
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe
    $query = "UPDATE users SET password = :password WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':password' => $hashed_password,
        ':id' => $user_id
    ]);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Réinitialisation de mot de passe", 
        "Utilisateur: " . $user['username']);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Mot de passe réinitialisé avec succès. Nouveau mot de passe : $new_password"
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}

// Fonction pour générer un mot de passe aléatoire
function generateRandomPassword($length = 12) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '@#$%^&*';
    
    $password = '';
    
    // Assurer au moins un caractère de chaque type
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Compléter avec des caractères aléatoires
    $all = $uppercase . $lowercase . $numbers . $special;
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    // Mélanger le mot de passe
    return str_shuffle($password);
} 