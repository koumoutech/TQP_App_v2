<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $name = $_POST['name'];
    $id = $_POST['id'] ?? null;
    
    try {
        if ($id) {
            // Mise à jour
            $query = "UPDATE categories SET name = :name WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':name' => $name,
                ':id' => $id
            ]);
            $_SESSION['success'] = "Catégorie modifiée avec succès";
        } else {
            // Création
            $query = "INSERT INTO categories (name) VALUES (:name)";
            $stmt = $conn->prepare($query);
            $stmt->execute([':name' => $name]);
            $_SESSION['success'] = "Catégorie ajoutée avec succès";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'enregistrement: " . $e->getMessage();
    }
}

header('Location: list_categories.php');
exit(); 