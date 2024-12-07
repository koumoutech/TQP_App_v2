<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Récupérer tous les comptes avec leurs utilisateurs
    $query = "SELECT a.*, 
              COUNT(DISTINCT au.id) as total_users,
              GROUP_CONCAT(CONCAT(au.employee_name, ' (', au.status, ')') SEPARATOR '\n') as employees
              FROM app_accounts a
              LEFT JOIN account_users au ON a.id = au.account_id
              GROUP BY a.id
              ORDER BY a.name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nettoyer la sortie
    if (ob_get_length()) ob_clean();
    
    // Headers pour le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=comptes_' . date('Y-m-d') . '.csv');
    
    // Créer le fichier CSV
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    // En-têtes des colonnes
    fputcsv($output, [
        'Nom du compte',
        'Lien',
        'Description',
        'Statut',
        'Nombre d\'utilisateurs',
        'Liste des employés'
    ]);

    // Données
    foreach ($accounts as $account) {
        fputcsv($output, [
            $account['name'],
            $account['link'],
            $account['description'],
            $account['status'],
            $account['total_users'],
            $account['employees']
        ]);
    }

    fclose($output);
    exit();

} catch (PDOException $e) {
    error_log("Erreur export_accounts: " . $e->getMessage());
    header('Location: list_accounts.php?error=export');
    exit();
} 