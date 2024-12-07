<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $id = $_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $_SESSION['success'] = "Produit supprimé avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

header('Location: list_products.php');
exit();
?> 