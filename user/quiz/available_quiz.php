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

// Récupérer les quiz disponibles pour le service de l'utilisateur
$query = "SELECT q.*, 
          COUNT(DISTINCT qr.id) as attempts,
          MAX(qr.score) as best_score
          FROM quizzes q 
          INNER JOIN quiz_services qs ON q.id = qs.quiz_id 
          LEFT JOIN quiz_results qr ON q.id = qr.quiz_id AND qr.user_id = :user_id
          WHERE qs.service_name = :service 
          AND q.status = 'active'
          AND (q.start_date IS NULL OR q.start_date <= NOW())
          AND (q.end_date IS NULL OR q.end_date >= NOW())
          GROUP BY q.id
          ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':service' => $_SESSION['service']
]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Quiz disponibles";
$base_url = "../..";

ob_start();
?>

<div class="quiz-container">
    <!-- En-tête avec filtres -->
    <div class="page-header">
        <div class="header-content">
            <h2>Quiz disponibles</h2>
            <p>Testez vos connaissances avec nos quiz interactifs</p>
        </div>
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchQuiz" placeholder="Rechercher un quiz..." class="form-control">
            </div>
        </div>
    </div>

    <!-- Grille des quiz -->
    <div class="quiz-grid">
        <?php foreach ($quizzes as $quiz): ?>
        <div class="quiz-card">
            <div class="quiz-header">
                <div class="quiz-status">
                    <?php if ($quiz['attempts'] > 0): ?>
                    <div class="status-badge <?php echo $quiz['best_score'] >= 70 ? 'success' : 'warning'; ?>">
                        Meilleur score: <?php echo number_format($quiz['best_score'], 1); ?>%
                    </div>
                    <?php else: ?>
                    <div class="status-badge new">Nouveau</div>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                <p><?php echo htmlspecialchars($quiz['description']); ?></p>
            </div>

            <div class="quiz-info">
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo $quiz['duration']; ?> minutes</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-question-circle"></i>
                    <span><?php echo $quiz['total_questions']; ?> questions</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-trophy"></i>
                    <span><?php echo $quiz['attempts']; ?> tentative(s)</span>
                </div>
            </div>

            <?php if ($quiz['start_date'] || $quiz['end_date']): ?>
            <div class="quiz-dates">
                <?php if ($quiz['start_date']): ?>
                <div class="date-item">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Début: <?php echo date('d/m/Y H:i', strtotime($quiz['start_date'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($quiz['end_date']): ?>
                <div class="date-item">
                    <i class="fas fa-calendar-minus"></i>
                    <span>Fin: <?php echo date('d/m/Y H:i', strtotime($quiz['end_date'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

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
        <?php endforeach; ?>
    </div>
</div>

<style>
.quiz-container {
    padding: 2rem;
}

.page-header {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.header-content p {
    color: #666;
    margin-top: 0.5rem;
}

.quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.quiz-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.quiz-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.quiz-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
}

.quiz-status {
    margin-bottom: 1rem;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.success { background: rgba(40, 199, 111, 0.1); color: #28c76f; }
.status-badge.warning { background: rgba(255, 159, 67, 0.1); color: #ff9f43; }
.status-badge.new { background: rgba(0, 86, 210, 0.1); color: #0056D2; }

.quiz-info {
    padding: 1.5rem;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    background: #f8f9fa;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.quiz-dates {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.quiz-actions {
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .quiz-container {
        padding: 1rem;
    }

    .quiz-grid {
        grid-template-columns: 1fr;
    }

    .quiz-info {
        grid-template-columns: 1fr;
    }

    .quiz-actions {
        flex-direction: column;
    }

    .quiz-actions .btn {
        width: 100%;
    }
}
</style>

<script>
document.getElementById('searchQuiz').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.quiz-card').forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('p').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?> 