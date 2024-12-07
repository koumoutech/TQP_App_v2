<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer tous les résultats de l'utilisateur
$query = "SELECT qr.*, q.title as quiz_title, q.duration, q.total_questions
          FROM quiz_results qr
          INNER JOIN quizzes q ON qr.quiz_id = q.id
          WHERE qr.user_id = :user_id
          ORDER BY qr.completed_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Mes résultats";
$base_url = "../..";

ob_start();
?>

<div class="content-wrapper fade-in">
    <!-- En-tête avec statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo count($results); ?></div>
                <div class="stat-label">Quiz passés</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">
                    <?php 
                    $avg_score = array_reduce($results, function($carry, $item) {
                        return $carry + $item['score'];
                    }, 0) / (count($results) ?: 1);
                    echo number_format($avg_score, 1) . '%';
                    ?>
                </div>
                <div class="stat-label">Score moyen</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">
                    <?php 
                    $best_score = array_reduce($results, function($carry, $item) {
                        return max($carry, $item['score']);
                    }, 0);
                    echo number_format($best_score, 1) . '%';
                    ?>
                </div>
                <div class="stat-label">Meilleur score</div>
            </div>
        </div>
    </div>

    <!-- Liste des résultats -->
    <div class="card">
        <div class="card-header">
            <h3>Historique des quiz</h3>
        </div>
        <div class="results-list">
            <?php foreach ($results as $result): ?>
            <div class="result-item">
                <div class="result-info">
                    <h4><?php echo htmlspecialchars($result['quiz_title']); ?></h4>
                    <div class="result-meta">
                        <span>
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d/m/Y H:i', strtotime($result['completed_at'])); ?>
                        </span>
                        <span>
                            <i class="fas fa-clock"></i>
                            <?php echo $result['time_taken']; ?> minutes
                        </span>
                        <span>
                            <i class="fas fa-check-circle"></i>
                            <?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?> questions
                        </span>
                    </div>
                </div>
                <div class="result-score">
                    <div class="score-circle <?php echo $result['score'] >= 70 ? 'success' : ($result['score'] >= 50 ? 'warning' : 'danger'); ?>">
                        <?php echo number_format($result['score'], 1); ?>%
                    </div>
                    <a href="view_result.php?id=<?php echo $result['id']; ?>" class="btn btn-secondary btn-sm">
                        Détails
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.results-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.result-item:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow-hover);
}

.result-meta {
    display: flex;
    gap: 1.5rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
}

.result-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.result-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.score-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.score-circle.success {
    background: var(--stat-bg-green);
    color: var(--chart-green);
}

.score-circle.warning {
    background: var(--stat-bg-yellow);
    color: var(--chart-yellow);
}

.score-circle.danger {
    background: var(--stat-bg-red);
    color: var(--chart-red);
}

@media (max-width: 768px) {
    .result-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }

    .result-meta {
        flex-direction: column;
        gap: 0.5rem;
        align-items: center;
    }
}
</style>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?> 