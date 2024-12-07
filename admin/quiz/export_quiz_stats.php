<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$quiz_id = $_POST['quiz_id'] ?? null;
if (!$quiz_id) {
    header('Location: list_quiz.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Récupérer les informations du quiz
    $query = "SELECT q.*, GROUP_CONCAT(qs.service_name) as services
              FROM quizzes q 
              LEFT JOIN quiz_services qs ON q.id = qs.quiz_id
              WHERE q.id = :id
              GROUP BY q.id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les résultats détaillés
    $results_query = "SELECT 
        u.username,
        u.service,
        qr.score,
        qr.completed_at,
        qr.time_spent
        FROM quiz_results qr
        JOIN users u ON qr.user_id = u.id
        WHERE qr.quiz_id = :quiz_id
        ORDER BY qr.completed_at DESC";
    $stmt = $conn->prepare($results_query);
    $stmt->execute([':quiz_id' => $quiz_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nettoyer la sortie
    if (ob_get_length()) ob_clean();
    
    // Nom du fichier
    $filename = 'statistiques_quiz_' . preg_replace('/[^a-zA-Z0-9]/', '_', $quiz['title']) . '_' . date('Y-m-d') . '.csv';
    
    // Headers pour forcer le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Transfer-Encoding: binary');
    
    // Ouvrir le flux de sortie
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes du fichier
    fputcsv($output, ['Statistiques du Quiz: ' . $quiz['title']]);
    fputcsv($output, ['Services concernés: ' . $quiz['services']]);
    fputcsv($output, ['Date d\'export: ' . date('d/m/Y H:i')]);
    fputcsv($output, []); // Ligne vide
    
    // En-têtes des colonnes
    fputcsv($output, ['Utilisateur', 'Service', 'Score', 'Date de completion', 'Temps passé (minutes)']);
    
    // Données
    foreach ($results as $result) {
        fputcsv($output, [
            $result['username'],
            $result['service'],
            $result['score'] . '%',
            date('d/m/Y H:i', strtotime($result['completed_at'])),
            round($result['time_spent'] / 60, 1)
        ]);
    }
    
    fclose($output);
    exit();

} catch (Exception $e) {
    error_log("Erreur export quiz stats: " . $e->getMessage());
    header('Location: quiz_stats.php?id=' . $quiz_id . '&error=export');
    exit();
} 