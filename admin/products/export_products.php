<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les filtres
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Construction de la requête
$query = "SELECT p.name, c.name as category, p.description, p.details, 
          p.media_url, p.created_at 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($category) {
    $query .= " AND c.id = :category";
    $params[':category'] = $category;
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Générer le CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=products_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
// Ajouter le BOM pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['Nom', 'Catégorie', 'Description', 'Détails', 'URL Média', 'Date de création']);

foreach ($products as $product) {
    fputcsv($output, [
        $product['name'],
        $product['category'],
        $product['description'],
        $product['details'],
        $product['media_url'] ? $_SERVER['HTTP_HOST'] . '/' . $product['media_url'] : '',
        date('d/m/Y H:i', strtotime($product['created_at']))
    ]);
}

fclose($output); 