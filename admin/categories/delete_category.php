<?php
session_start();
require_once '../../config/database.php';

if (isset($_GET['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $id = $_GET['id'];
    
    // Vérifier si la catégorie n'a pas de produits
    $check_query = "SELECT COUNT(*) FROM products WHERE category_id = :id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([':id' => $id]);
    $product_count = $check_stmt->fetchColumn();
    
    if ($product_count > 0) {
        $_SESSION['error'] = "Impossible de supprimer une catégorie contenant des produits";
    } else {
        try {
            $delete_query = "DELETE FROM categories WHERE id = :id";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->execute([':id' => $id]);
            $_SESSION['success'] = "Catégorie supprimée avec succès";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
        }
    }
}

header('Location: list_categories.php');
exit(); 