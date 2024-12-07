<?php
session_start();
require_once '../config/database.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les statistiques globales
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_quizzes' => $conn->query("SELECT COUNT(*) FROM quizzes")->fetchColumn(),
    'total_products' => $conn->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_accounts' => $conn->query("SELECT COUNT(*) FROM app_accounts")->fetchColumn()
];

// Statistiques des quiz par service
$quiz_stats = $conn->query("
    SELECT 
        u.service,
        COUNT(qr.id) as attempts,
        ROUND(AVG(qr.score), 2) as avg_score,
        COUNT(DISTINCT qr.user_id) as unique_users
    FROM users u
    LEFT JOIN quiz_results qr ON u.id = qr.user_id
    WHERE u.role = 'user'
    GROUP BY u.service
    ORDER BY attempts DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Top 10 des utilisateurs par score aux quiz
$top_users = $conn->query("
    SELECT 
        u.username,
        u.service,
        COUNT(qr.id) as quiz_count,
        ROUND(AVG(qr.score), 2) as avg_score
    FROM users u
    INNER JOIN quiz_results qr ON u.id = qr.user_id
    GROUP BY u.id
    ORDER BY avg_score DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Tableau de bord";
$base_url = "..";

ob_start();
?>

<div class="content-wrapper">
    <div class="page-header">
        <h2>Tableau de bord administrateur</h2>
    </div>

    <!-- Statistiques globales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Utilisateurs</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $stats['total_quizzes']; ?></div>
                <div class="stat-label">Quiz</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                <div class="stat-label">Produits</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $stats['total_accounts']; ?></div>
                <div class="stat-label">Comptes</div>
            </div>
        </div>
    </div>

    <!-- Statistiques des quiz par service -->
    <div class="dashboard-section">
        <h3>Performance des quiz par service</h3>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Tentatives</th>
                        <th>Score moyen</th>
                        <th>Participants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quiz_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['service']); ?></td>
                        <td><?php echo $stat['attempts']; ?></td>
                        <td><?php echo $stat['avg_score']; ?>%</td>
                        <td><?php echo $stat['unique_users']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top 10 des utilisateurs -->
    <div class="dashboard-section">
        <h3>Top 10 des utilisateurs</h3>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Service</th>
                        <th>Quiz passés</th>
                        <th>Score moyen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['service']); ?></td>
                        <td><?php echo $user['quiz_count']; ?></td>
                        <td><?php echo $user['avg_score']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-icon.blue { background: rgba(0, 86, 210, 0.1); color: #0056D2; }
.stat-icon.yellow { background: rgba(255, 204, 48, 0.1); color: #FFCC30; }
.stat-icon.green { background: rgba(40, 199, 111, 0.1); color: #28c76f; }
.stat-icon.purple { background: rgba(116, 51, 255, 0.1); color: #7433ff; }

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #666;
    font-size: 0.875rem;
}

.dashboard-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dashboard-section h3 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #FFCC30;
}

.data-table {
    overflow-x: auto;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.data-table tr:hover {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?> 