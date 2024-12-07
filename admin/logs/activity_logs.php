<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$logger = new Logger($conn);

// Filtres
$user = isset($_GET['user']) ? $_GET['user'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Construction de la requête
$query = "SELECT l.*, u.username 
          FROM activity_logs l
          JOIN users u ON l.user_id = u.id
          WHERE 1=1";

$params = [];

if ($user) {
    $query .= " AND u.username LIKE :username";
    $params[':username'] = "%$user%";
}
if ($action) {
    $query .= " AND l.action = :action";
    $params[':action'] = $action;
}
if ($date) {
    $query .= " AND DATE(l.created_at) = :date";
    $params[':date'] = $date;
}

$query .= " ORDER BY l.created_at DESC LIMIT :offset, :limit";
$params[':offset'] = $offset;
$params[':limit'] = $limit;

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des actions pour le filtre
$actions = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")
                ->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Journaux d'activité</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Journaux d'activité</h2>
        
        <div class="filters">
            <form method="GET" action="">
                <input type="text" name="user" placeholder="Utilisateur..." 
                       value="<?php echo htmlspecialchars($user); ?>">
                
                <select name="action">
                    <option value="">Toutes les actions</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?php echo htmlspecialchars($act); ?>"
                                <?php echo $action === $act ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($act); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </form>
        </div>

        <div class="export-actions">
            <a href="export_logs.php?<?php echo http_build_query($_GET); ?>" 
               class="btn btn-secondary">Exporter en Excel</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Date/Heure</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Détails</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                   class="btn btn-secondary">Précédent</a>
            <?php endif; ?>
            
            <?php if (count($logs) === $limit): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                   class="btn btn-secondary">Suivant</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 