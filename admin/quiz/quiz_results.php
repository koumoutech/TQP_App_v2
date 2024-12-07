<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Filtres
$service = isset($_GET['service']) ? $_GET['service'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Requête de base
$query = "SELECT qr.*, q.title as quiz_title, q.category, u.username, u.service,
          COUNT(qa.id) as total_answers,
          SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
          FROM quiz_results qr
          JOIN quizzes q ON qr.quiz_id = q.id
          JOIN users u ON qr.user_id = u.id
          LEFT JOIN quiz_answers qa ON qr.id = qa.result_id
          LEFT JOIN answers a ON qa.answer_id = a.id
          WHERE 1=1";

if ($service) {
    $query .= " AND u.service = :service";
}
if ($month) {
    $query .= " AND DATE_FORMAT(qr.completed_at, '%Y-%m') = :month";
}

$query .= " GROUP BY qr.id ORDER BY qr.completed_at DESC";

$stmt = $conn->prepare($query);
if ($service) {
    $stmt->bindValue(':service', $service);
}
if ($month) {
    $stmt->bindValue(':month', $month);
}
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des services pour le filtre
$services = $conn->query("SELECT DISTINCT service FROM users ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Administration des Résultats Quiz</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Résultats des Quiz</h2>
        
        <div class="filters">
            <form method="GET" action="">
                <select name="service">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $srv): ?>
                        <option value="<?php echo htmlspecialchars($srv); ?>"
                                <?php echo $service === $srv ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($srv); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>">
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </form>
        </div>

        <div class="export-actions">
            <a href="export_results.php?service=<?php echo urlencode($service); ?>&month=<?php echo urlencode($month); ?>" 
               class="btn btn-secondary">Exporter en Excel</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Service</th>
                    <th>Quiz</th>
                    <th>Catégorie</th>
                    <th>Score</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($result['completed_at'])); ?></td>
                    <td><?php echo htmlspecialchars($result['username']); ?></td>
                    <td><?php echo htmlspecialchars($result['service']); ?></td>
                    <td><?php echo htmlspecialchars($result['quiz_title']); ?></td>
                    <td><?php echo htmlspecialchars($result['category']); ?></td>
                    <td><?php echo number_format($result['score'], 2); ?>%</td>
                    <td>
                        <a href="view_result.php?id=<?php echo $result['id']; ?>" 
                           class="btn btn-info">Voir détails</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 