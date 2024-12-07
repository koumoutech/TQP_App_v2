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
$service = $_GET['service'] ?? '';
$role = $_GET['role'] ?? '';

// Construction de la requête
$query = "SELECT username, service, role, created_at FROM users WHERE 1=1";
$params = [];

if ($service) {
    $query .= " AND service = :service";
    $params[':service'] = $service;
}
if ($role) {
    $query .= " AND role = :role";
    $params[':role'] = $role;
}

$query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Générer le CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Nom d\'utilisateur', 'Service', 'Rôle', 'Date de création']);

foreach ($users as $user) {
    fputcsv($output, [
        $user['username'],
        $user['service'],
        $user['role'],
        date('d/m/Y H:i', strtotime($user['created_at']))
    ]);
} 