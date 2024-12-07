<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    header('Location: list_quiz.php');
    exit();
}

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

if (!$quiz) {
    header('Location: list_quiz.php');
    exit();
}

// Statistiques générales
$stats_query = "SELECT 
    COUNT(DISTINCT user_id) as total_participants,
    COALESCE(AVG(score), 0) as avg_score,
    COALESCE(MIN(score), 0) as min_score,
    COALESCE(MAX(score), 0) as max_score,
    COUNT(*) as total_attempts
    FROM quiz_results 
    WHERE quiz_id = :quiz_id";
$stmt = $conn->prepare($stats_query);
$stmt->execute([':quiz_id' => $quiz_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistiques par service
$service_stats_query = "SELECT 
    u.service,
    COUNT(DISTINCT qr.user_id) as participants,
    COALESCE(AVG(qr.score), 0) as avg_score
    FROM quiz_results qr
    JOIN users u ON qr.user_id = u.id
    WHERE qr.quiz_id = :quiz_id
    GROUP BY u.service";
$stmt = $conn->prepare($service_stats_query);
$stmt->execute([':quiz_id' => $quiz_id]);
$service_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Derniers résultats
$recent_results_query = "SELECT 
    qr.*, u.username, u.service
    FROM quiz_results qr
    JOIN users u ON qr.user_id = u.id
    WHERE qr.quiz_id = :quiz_id
    ORDER BY qr.completed_at DESC
    LIMIT 10";
$stmt = $conn->prepare($recent_results_query);
$stmt->execute([':quiz_id' => $quiz_id]);
$recent_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Statistiques du Quiz";
$base_url = "../..";

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="quiz-header">
        <div class="quiz-title">
            <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
            <div class="services-badges">
                <?php 
                $services = explode(',', $quiz['services'] ?? '');
                foreach ($services as $service): 
                    if (!empty($service)):
                ?>
                    <span class="badge badge-service">
                        <?php echo htmlspecialchars(trim($service)); ?>
                    </span>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <div class="quiz-actions">
            <button class="btn btn-secondary" id="backButton" type="button">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
            <button class="btn btn-primary" id="exportButton" type="button" data-quiz-id="<?php echo $quiz_id; ?>">
                <i class="fas fa-download"></i> Exporter
            </button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3>Participants</h3>
                <p class="stat-number"><?php echo $stats['total_participants']; ?></p>
                <p class="stat-label">Utilisateurs uniques</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-details">
                <h3>Score Moyen</h3>
                <p class="stat-number"><?php echo number_format($stats['avg_score'], 1); ?>%</p>
                <p class="stat-label">Sur tous les essais</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-details">
                <h3>Meilleur Score</h3>
                <p class="stat-number"><?php echo number_format($stats['max_score'], 1); ?>%</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-redo"></i>
            </div>
            <div class="stat-details">
                <h3>Tentatives</h3>
                <p class="stat-number"><?php echo $stats['total_attempts']; ?></p>
                <p class="stat-label">Total des essais</p>
            </div>
        </div>
    </div>

    <div class="stats-details">
        <div class="service-stats">
            <h3>Résultats par Service</h3>
            <div class="chart-container">
                <canvas id="serviceChart"></canvas>
            </div>
            <div id="serviceData" data-stats='<?php echo json_encode($service_stats); ?>' style="display: none;"></div>
            <div class="service-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Participants</th>
                            <th>Score Moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($service_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['service']); ?></td>
                            <td><?php echo $stat['participants']; ?></td>
                            <td><?php echo number_format($stat['avg_score'], 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="recent-results">
            <h3>Derniers Résultats</h3>
            <div class="results-list">
                <?php foreach ($recent_results as $result): ?>
                <div class="result-item">
                    <div class="result-user">
                        <i class="fas fa-user"></i>
                        <div>
                            <strong><?php echo htmlspecialchars($result['username']); ?></strong>
                            <span class="service-badge">
                                <?php echo htmlspecialchars($result['service']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="result-score">
                        <span class="score"><?php echo number_format($result['score'], 1); ?>%</span>
                        <span class="date">
                            <?php echo date('d/m/Y H:i', strtotime($result['completed_at'])); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS supplémentaire
$extra_css = '
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-color);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: var(--shadow);
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bg-dark);
    font-size: 1.5rem;
}

.stats-details {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

.service-stats, .recent-results {
    background: var(--bg-color);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--shadow);
}

.chart-container {
    margin: 1.5rem 0;
    height: 300px;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.result-user {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.result-score {
    text-align: right;
}

.score {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-color);
}

.date {
    display: block;
    font-size: 0.875rem;
    color: var(--text-light);
}

@media (max-width: 1024px) {
    .stats-details {
        grid-template-columns: 1fr;
    }
}
</style>';

// JavaScript pour les graphiques
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/quiz_stats.js"></script>';

// Inclure le layout
include '../../includes/layout.php';
?> 