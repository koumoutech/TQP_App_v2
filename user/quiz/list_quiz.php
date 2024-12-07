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

// Récupérer les quiz disponibles pour l'utilisateur
$query = "SELECT q.*, 
          COUNT(DISTINCT qr.id) as attempts,
          MAX(qr.score) as best_score
          FROM quizzes q 
          INNER JOIN quiz_services qs ON q.id = qs.quiz_id 
          LEFT JOIN quiz_results qr ON q.id = qr.quiz_id AND qr.user_id = :user_id
          WHERE qs.service_name = :service 
          AND q.status = 'active'
          GROUP BY q.id
          ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':service' => $_SESSION['service'] ?? ''
]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Quiz disponibles";
$base_url = "../..";

ob_start();
?>

<div class="content-wrapper">
    <!-- En-tête avec statistiques -->
    <div class="page-header">
        <h2>Quiz disponibles</h2>
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchQuiz" placeholder="Rechercher un quiz..." class="form-control">
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo count($quizzes); ?></div>
                <div class="stat-label">Quiz disponibles</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">
                    <?php 
                    echo count(array_filter($quizzes, function($quiz) {
                        return $quiz['attempts'] > 0;
                    }));
                    ?>
                </div>
                <div class="stat-label">Quiz complétés</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">
                    <?php 
                    $bestScore = max(array_column($quizzes, 'best_score')) ?? 0;
                    echo number_format($bestScore, 1) . '%';
                    ?>
                </div>
                <div class="stat-label">Meilleur score</div>
            </div>
        </div>
    </div>

    <!-- Liste des quiz -->
    <div class="quiz-grid">
        <?php if (empty($quizzes)): ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>Aucun quiz disponible</h3>
            <p>Il n'y a pas de quiz disponible pour le moment.</p>
        </div>
        <?php else: ?>
            <?php foreach ($quizzes as $quiz): ?>
            <div class="quiz-card">
                <div class="quiz-status">
                    <?php if ($quiz['attempts'] > 0): ?>
                    <div class="status-badge <?php echo $quiz['best_score'] >= 70 ? 'success' : 'warning'; ?>">
                        Meilleur score: <?php echo number_format($quiz['best_score'], 1); ?>%
                    </div>
                    <?php else: ?>
                    <div class="status-badge new">Nouveau</div>
                    <?php endif; ?>
                </div>

                <div class="quiz-content">
                    <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <p><?php echo htmlspecialchars($quiz['description']); ?></p>

                    <div class="quiz-meta">
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $quiz['duration']; ?> minutes</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-question-circle"></i>
                            <span><?php echo $quiz['total_questions']; ?> questions</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-redo"></i>
                            <span><?php echo $quiz['attempts']; ?> tentative(s)</span>
                        </div>
                    </div>

                    <div class="quiz-actions">
                        <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary">
                            <?php echo $quiz['attempts'] > 0 ? 'Retenter' : 'Commencer'; ?>
                        </a>
                        <?php if ($quiz['attempts'] > 0): ?>
                        <a href="view_result.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-secondary">
                            Voir mes résultats
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?> 