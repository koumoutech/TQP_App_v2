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
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['slides'])) {
        throw new Exception('Données manquantes');
    }

    $db = new Database();
    $conn = $db->getConnection();

    foreach ($data['slides'] as $slide) {
        $query = "UPDATE slideshow SET position = :position WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':position' => $slide['position'],
            ':id' => $slide['id']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ordre mis à jour'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 