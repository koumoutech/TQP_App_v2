<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    $_SESSION['error'] = "ID utilisateur manquant";
    header('Location: list_users.php');
    exit();
}

// Empêcher la suppression de son propre compte
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte";
    header('Location: list_users.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Récupérer les informations de l'utilisateur avant suppression
    $query = "SELECT username, role FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "Utilisateur non trouvé";
        header('Location: list_users.php');
        exit();
    }

    // Empêcher la suppression d'un admin par un autre admin
    if ($user['role'] === 'admin' && $_SESSION['role'] !== 'superadmin') {
        $_SESSION['error'] = "Vous n'avez pas les droits pour supprimer un administrateur";
        header('Location: list_users.php');
        exit();
    }

    $conn->beginTransaction();
    
    // Supprimer les résultats des quiz de l'utilisateur
    $query = "DELETE FROM quiz_results WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    // Supprimer les vues de produits de l'utilisateur
    $query = "DELETE FROM product_views WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    // Supprimer les activités de l'utilisateur
    $query = "DELETE FROM activity_logs WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    // Supprimer l'utilisateur
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    
    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Suppression d'utilisateur", 
        "Utilisateur supprimé: " . $user['username']);
    
    $conn->commit();
    
    $_SESSION['success'] = "Utilisateur supprimé avec succès";

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

header('Location: list_users.php');
exit(); 