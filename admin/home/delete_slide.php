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
    $slide_id = $_GET['id'] ?? null;
    if (!$slide_id) {
        throw new Exception('ID manquant');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer l'image pour la supprimer
    $query = "SELECT image_url FROM slideshow WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $slide_id]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($slide) {
        // Supprimer le fichier image
        $imagePath = '../../' . ltrim($slide['image_url'], '/');
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Supprimer la slide
    $query = "DELETE FROM slideshow WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $slide_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Slide supprimée'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 