<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // Nécessite l'installation de PhpSpreadsheet via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$db = new Database();
$conn = $db->getConnection();

// Récupérer tous les comptes avec leurs statuts utilisateurs
$query = "SELECT a.name as account_name, a.link, a.description,
          u.username, u.service, ua.status
          FROM accounts a
          LEFT JOIN user_accounts ua ON a.id = ua.account_id
          LEFT JOIN users u ON ua.user_id = u.id
          ORDER BY a.name, u.service, u.username";
          
$accounts = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Créer un nouveau document Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// En-têtes
$sheet->setCellValue('A1', 'Compte');
$sheet->setCellValue('B1', 'Lien');
$sheet->setCellValue('C1', 'Description');
$sheet->setCellValue('D1', 'Utilisateur');
$sheet->setCellValue('E1', 'Service');
$sheet->setCellValue('F1', 'Statut');

// Style des en-têtes
$sheet->getStyle('A1:F1')->getFont()->setBold(true);
$sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setRGB('FFCC30');

// Données
$row = 2;
foreach ($accounts as $account) {
    $sheet->setCellValue('A'.$row, $account['account_name']);
    $sheet->setCellValue('B'.$row, $account['link']);
    $sheet->setCellValue('C'.$row, $account['description']);
    $sheet->setCellValue('D'.$row, $account['username']);
    $sheet->setCellValue('E'.$row, $account['service']);
    $sheet->setCellValue('F'.$row, $account['status']);
    $row++;
}

// Ajuster la largeur des colonnes
foreach(range('A','F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Créer le fichier Excel
$writer = new Xlsx($spreadsheet);

// En-têtes HTTP pour le téléchargement
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="comptes_tqp_'.date('Y-m-d').'.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?> 