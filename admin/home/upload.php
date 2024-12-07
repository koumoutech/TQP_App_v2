<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

try {
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileType = $file['type'];
    
    // Vérifier le type de fichier
    $allowedTypes = ['application/pdf', 'application/vnd.ms-excel', 'image/jpeg', 'image/png', 'application/msword'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type');
    }
    
    // Créer le dossier d'upload s'il n'existe pas
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Générer un nom unique
    $newFileName = uniqid() . '_' . $fileName;
    $uploadPath = $uploadDir . $newFileName;
    
    // Déplacer le fichier
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        // Enregistrer dans la base de données
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "INSERT INTO files (name, path, size, type) VALUES (:name, :path, :size, :type)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':name' => $fileName,
            ':path' => $uploadPath,
            ':size' => $fileSize,
            ':type' => $fileType
        ]);
        
        echo json_encode(['success' => true, 'message' => 'File uploaded successfully']);
    } else {
        throw new Exception('Error moving uploaded file');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 