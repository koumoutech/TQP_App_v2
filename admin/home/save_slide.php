<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $slide_id = $_POST['slide_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    if (empty($title) || empty($image_url)) {
        throw new Exception('Titre et image requis');
    }

    if ($slide_id) {
        // Mise à jour
        $query = "UPDATE slideshow 
                  SET title = :title,
                      description = :description,
                      image_url = :image_url
                  WHERE id = :id";
        $params = [
            ':title' => $title,
            ':description' => $description,
            ':image_url' => $image_url,
            ':id' => $slide_id
        ];
    } else {
        // Nouvelle slide
        $query = "INSERT INTO slideshow (title, description, image_url, position)
                  SELECT :title, :description, :image_url, COALESCE(MAX(position), 0) + 1
                  FROM slideshow";
        $params = [
            ':title' => $title,
            ':description' => $description,
            ':image_url' => $image_url
        ];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => $slide_id ? 'Slide mise à jour' : 'Slide ajoutée'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 