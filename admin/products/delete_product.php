<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    $_SESSION['error'] = "ID produit manquant";
    header('Location: list_products.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Récupérer les informations du produit avant suppression
    $query = "SELECT name, media_url FROM products WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error'] = "Produit non trouvé";
        header('Location: list_products.php');
        exit();
    }

    $conn->beginTransaction();
    
    // Supprimer d'abord les vues du produit
    $query = "DELETE FROM product_views WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':product_id' => $product_id]);
    
    // Supprimer le produit
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $product_id]);
    
    // Supprimer le fichier média associé
    if ($product['media_url'] && file_exists('../../' . $product['media_url'])) {
        unlink('../../' . $product['media_url']);
    }
    
    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Suppression du produit", 
        "Produit supprimé: " . $product['name']);
    
    $conn->commit();
    
    $_SESSION['success'] = "Produit supprimé avec succès";
} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

header('Location: list_products.php');
exit(); 