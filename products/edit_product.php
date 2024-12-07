<?php
require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $details = $_POST['details'];
    
    $query = "UPDATE products SET 
              name = :name,
              category_id = :category_id,
              description = :description,
              details = :details
              WHERE id = :id";
              
    $stmt = $conn->prepare($query);
    
    try {
        $stmt->execute([
            ':name' => $name,
            ':category_id' => $category_id,
            ':description' => $description,
            ':details' => $details,
            ':id' => $id
        ]);
        $_SESSION['success'] = "Produit modifié avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification: " . $e->getMessage();
    }
    
    header('Location: products.php');
    exit();
}
?> 