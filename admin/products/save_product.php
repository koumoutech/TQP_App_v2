<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $product_id = $_POST['product_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $details = trim($_POST['details'] ?? '');
    
    // Validation
    if (empty($name) || empty($category_id) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires']);
        exit();
    }

    // Vérifier si la catégorie existe
    $cat_check = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    $cat_check->execute([$category_id]);
    if (!$cat_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Catégorie invalide']);
        exit();
    }

    // Gestion du média
    $media_url = null;
    $media_type = null;
    
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['media'];
        $allowed_types = ['image/jpeg', 'image/png', 'video/mp4'];
        
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Format de fichier non supporté']);
            exit();
        }
        
        $max_size = 50 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux (maximum 50MB)']);
            exit();
        }
        
        $upload_dir = '../../uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = uniqid() . '_' . basename($file['name']);
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $media_url = 'uploads/products/' . $filename;
            $media_type = strpos($file['type'], 'image/') === 0 ? 'image' : 'video';
        }
    }

    $conn->beginTransaction();

    if ($product_id) {
        // Mise à jour
        if ($media_url) {
            // Supprimer l'ancien média si un nouveau est uploadé
            $old_media_query = "SELECT media_url FROM products WHERE id = :id";
            $stmt = $conn->prepare($old_media_query);
            $stmt->execute([':id' => $product_id]);
            $old_media = $stmt->fetchColumn();
            
            if ($old_media && file_exists('../../' . $old_media)) {
                unlink('../../' . $old_media);
            }
            
            $query = "UPDATE products SET name = :name, category_id = :category_id, 
                      description = :description, details = :details, 
                      media_url = :media_url, media_type = :media_type 
                      WHERE id = :id";
            $params = [
                ':name' => $name,
                ':category_id' => $category_id,
                ':description' => $description,
                ':details' => $details,
                ':media_url' => $media_url,
                ':media_type' => $media_type,
                ':id' => $product_id
            ];
        } else {
            $query = "UPDATE products SET name = :name, category_id = :category_id, 
                      description = :description, details = :details 
                      WHERE id = :id";
            $params = [
                ':name' => $name,
                ':category_id' => $category_id,
                ':description' => $description,
                ':details' => $details,
                ':id' => $product_id
            ];
        }
        $action = "Modification du produit";
    } else {
        // Création
        $query = "INSERT INTO products (name, category_id, description, details, media_url, media_type) 
                  VALUES (:name, :category_id, :description, :details, :media_url, :media_type)";
        $params = [
            ':name' => $name,
            ':category_id' => $category_id,
            ':description' => $description,
            ':details' => $details,
            ':media_url' => $media_url,
            ':media_type' => $media_type
        ];
        $action = "Création d'un nouveau produit";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], $action, "Produit: $name");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $product_id ? 'Produit modifié avec succès' : 'Produit créé avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 