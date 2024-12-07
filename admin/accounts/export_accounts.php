<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Nettoyer tout output précédent
    ob_clean();
    
    // Récupérer les statistiques globales
    $stats_query = "SELECT 
        COUNT(*) as total_accounts,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_accounts,
        COUNT(DISTINCT au.employee_name) as total_employees
        FROM app_accounts a
        LEFT JOIN account_users au ON a.id = au.account_id";
    $stmt = $conn->prepare($stats_query);
    $stmt->execute();
    $global_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer tous les comptes avec leurs utilisateurs
    $query = "SELECT a.*, 
              COUNT(DISTINCT au.id) as total_users,
              GROUP_CONCAT(
                  CONCAT(au.employee_name, ' (', 
                  CASE 
                      WHEN au.status = 'active' THEN 'Actif'
                      WHEN au.status = 'blocked' THEN 'Bloqué'
                      ELSE 'Sans compte'
                  END, 
                  ')' SEPARATOR '\n'
              ) as employees
              FROM app_accounts a
              LEFT JOIN account_users au ON a.id = au.account_id
              GROUP BY a.id
              ORDER BY a.name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Headers pour Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="comptes_' . date('Y-m-d') . '.xls"');
    header('Expires: 0');
    header('Cache-Control: no-cache');
    header('Pragma: no-cache');
    
    // Ajouter BOM pour UTF-8
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);

    // Créer le contenu HTML pour Excel
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!--[if gte mso 9]>
        <xml>
            <x:ExcelWorkbook>
                <x:ExcelWorksheets>
                    <x:ExcelWorksheet>
                        <x:Name>Comptes</x:Name>
                        <x:WorksheetOptions>
                            <x:DisplayGridlines/>
                        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
    </head>
    <body>';

    // ... [Reste du code HTML] ...

    echo '</body></html>';
    exit();

} catch (PDOException $e) {
    error_log("Erreur export_accounts: " . $e->getMessage());
    header('Location: list_accounts.php?error=export');
    exit();
} 