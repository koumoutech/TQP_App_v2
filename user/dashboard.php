<?php
session_start();
require_once '../config/database.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les statistiques de l'utilisateur
$stats_query = "SELECT 
    COUNT(*) as total_quiz_taken,
    AVG(score) as average_score,
    MAX(score) as best_score
FROM quiz_results 
WHERE user_id = :user_id";
$stmt = $conn->prepare($stats_query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "Tableau de bord";
$base_url = "..";

ob_start();
?>

<div class="dashboard-container">
    <!-- En-tête avec statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">3</div>
                <div class="stat-label">Services actifs</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $stats['total_quiz_taken']; ?></div>
                <div class="stat-label">Quiz complétés</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo number_format($stats['best_score'] ?? 0, 1); ?>%</div>
                <div class="stat-label">Meilleur score</div>
            </div>
        </div>
    </div>

    <!-- Services actifs -->
    <div class="section">
        <div class="section-header">
            <h2>Mes services actifs</h2>
            <a href="products/list_products.php" class="btn btn-primary">
                Voir tous les services
            </a>
        </div>
        <div class="services-grid">
            <!-- Service 1 -->
            <div class="service-card">
                <img src="../assets/images/services/internet.jpg" alt="Internet">
                <div class="service-info">
                    <h3>Forfait Internet 4G</h3>
                    <p>Forfait internet 4G haute vitesse pour une connexion rapide</p>
                    <div class="service-status active">Actif</div>
                </div>
            </div>
            <!-- Service 2 -->
            <div class="service-card">
                <img src="../assets/images/services/calls.jpg" alt="Appels">
                <div class="service-info">
                    <h3>Pack Appels Illimités</h3>
                    <p>Pack d'appels illimités vers tous les réseaux</p>
                    <div class="service-status active">Actif</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz récents -->
    <div class="section">
        <div class="section-header">
            <h2>Quiz récents</h2>
            <a href="quiz/available_quiz.php" class="btn btn-secondary">
                Voir tous les quiz
            </a>
        </div>
        <div class="quiz-grid">
            <?php
            // Récupérer les derniers quiz
            $quiz_query = "SELECT qr.*, q.title, q.duration
                          FROM quiz_results qr
                          INNER JOIN quizzes q ON qr.quiz_id = q.id
                          WHERE qr.user_id = :user_id
                          ORDER BY qr.completed_at DESC
                          LIMIT 3";
            $stmt = $conn->prepare($quiz_query);
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $recent_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($recent_quizzes as $quiz):
            ?>
            <div class="quiz-card">
                <div class="quiz-header">
                    <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <div class="quiz-score <?php echo $quiz['score'] >= 70 ? 'success' : ($quiz['score'] >= 50 ? 'warning' : 'danger'); ?>">
                        <?php echo number_format($quiz['score'], 1); ?>%
                    </div>
                </div>
                <div class="quiz-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($quiz['completed_at'])); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo $quiz['duration']; ?> min</span>
                </div>
                <a href="quiz/view_result.php?id=<?php echo $quiz['id']; ?>" class="btn btn-link">
                    Voir les détails
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.section {
    margin-bottom: 3rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.services-grid,
.quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.service-card,
.quiz-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.service-card:hover,
.quiz-card:hover {
    transform: translateY(-5px);
}

.service-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}

.service-info,
.quiz-header {
    padding: 1.5rem;
}

.service-status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
}

.service-status.active {
    background: rgba(40, 199, 111, 0.1);
    color: #28c76f;
}

.quiz-meta {
    padding: 0 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    color: #666;
}

.quiz-score {
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
}

.quiz-score.success { background: rgba(40, 199, 111, 0.1); color: #28c76f; }
.quiz-score.warning { background: rgba(255, 159, 67, 0.1); color: #ff9f43; }
.quiz-score.danger { background: rgba(234, 84, 85, 0.1); color: #ea5455; }

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }

    .section-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?> 