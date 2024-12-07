<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$result_id = $_GET['id'] ?? null;
if (!$result_id) {
    header('Location: available_quiz.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les détails du résultat
$query = "SELECT qr.*, q.title, q.description, q.duration, q.total_questions
          FROM quiz_results qr
          INNER JOIN quizzes q ON qr.quiz_id = q.id
          WHERE qr.id = :id AND qr.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute([
    ':id' => $result_id,
    ':user_id' => $_SESSION['user_id']
]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header('Location: available_quiz.php');
    exit();
}

// Récupérer les réponses détaillées
$query = "SELECT 
            qq.question_text,
            qq.points,
            qa.answer_text,
            qa.is_correct,
            qua.answer_id as user_answer_id,
            (SELECT answer_text FROM quiz_answers WHERE id = qua.answer_id) as user_answer_text,
            (SELECT answer_text FROM quiz_answers WHERE question_id = qq.id AND is_correct = 1) as correct_answer_text
          FROM quiz_questions qq
          INNER JOIN quiz_user_answers qua ON qq.id = qua.question_id
          INNER JOIN quiz_answers qa ON qua.answer_id = qa.id
          WHERE qua.result_id = :result_id
          ORDER BY qq.id";
$stmt = $conn->prepare($query);
$stmt->execute([':result_id' => $result_id]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Résultat du quiz";
$base_url = "../..";

ob_start();
?>

<div class="result-container">
    <!-- En-tête avec score -->
    <div class="result-header">
        <div class="result-info">
            <h2><?php echo htmlspecialchars($result['title']); ?></h2>
            <div class="result-meta">
                <span><i class="fas fa-clock"></i> <?php echo $result['time_taken']; ?> minutes</span>
                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($result['completed_at'])); ?></span>
            </div>
        </div>
        <div class="score-circle <?php echo $result['score'] >= 70 ? 'success' : ($result['score'] >= 50 ? 'warning' : 'danger'); ?>">
            <div class="score-value"><?php echo number_format($result['score'], 1); ?>%</div>
            <div class="score-label">Score final</div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?></div>
                <div class="stat-label">Réponses correctes</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $result['time_taken']; ?> min</div>
                <div class="stat-label">Temps utilisé</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $result['earned_points']; ?>/<?php echo $result['total_points']; ?></div>
                <div class="stat-label">Points obtenus</div>
            </div>
        </div>
    </div>

    <!-- Détail des réponses -->
    <div class="answers-section">
        <h3>Détail des réponses</h3>
        <?php foreach ($answers as $index => $answer): ?>
        <div class="answer-card <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
            <div class="answer-header">
                <span class="question-number">Question <?php echo $index + 1; ?></span>
                <span class="question-points"><?php echo $answer['points']; ?> points</span>
            </div>
            <div class="question-text">
                <?php echo htmlspecialchars($answer['question_text']); ?>
            </div>
            <div class="answers-detail">
                <div class="user-answer">
                    <i class="fas <?php echo $answer['is_correct'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    <span>Votre réponse :</span>
                    <?php echo htmlspecialchars($answer['user_answer_text']); ?>
                </div>
                <?php if (!$answer['is_correct']): ?>
                <div class="correct-answer">
                    <i class="fas fa-check"></i>
                    <span>Bonne réponse :</span>
                    <?php echo htmlspecialchars($answer['correct_answer_text']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div class="result-actions">
        <a href="available_quiz.php" class="btn btn-primary">
            <i class="fas fa-list"></i> Retour aux quiz
        </a>
        <a href="take_quiz.php?id=<?php echo $result['quiz_id']; ?>" class="btn btn-secondary">
            <i class="fas fa-redo"></i> Retenter le quiz
        </a>
    </div>
</div>

<style>
.result-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

/* En-tête avec score */
.result-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.result-info h2 {
    margin-bottom: 0.75rem;
    font-size: 1.5rem;
}

.result-meta {
    display: flex;
    gap: 1.5rem;
    color: #666;
    font-size: 0.9rem;
}

.score-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
}

.score-circle.success { background: var(--chart-green); }
.score-circle.warning { background: var(--chart-yellow); }
.score-circle.danger { background: var(--chart-red); }

.score-value {
    font-size: 2rem;
    font-weight: 700;
}

.score-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

/* Statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-icon.blue { background: var(--stat-bg-blue); color: var(--chart-blue); }
.stat-icon.yellow { background: var(--stat-bg-yellow); color: var(--chart-yellow); }
.stat-icon.green { background: var(--stat-bg-green); color: var(--chart-green); }

/* Section des réponses */
.answers-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.answers-section h3 {
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
}

.answer-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid transparent;
}

.answer-card.correct { border-left-color: var(--chart-green); }
.answer-card.incorrect { border-left-color: var(--chart-red); }

.answer-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    color: #666;
    font-size: 0.9rem;
}

.question-text {
    font-weight: 500;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.answers-detail {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.user-answer,
.correct-answer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
}

.user-answer i,
.correct-answer i {
    font-size: 1.1rem;
}

.user-answer span,
.correct-answer span {
    font-weight: 500;
    margin-right: 0.5rem;
}

.correct .user-answer i { color: var(--chart-green); }
.incorrect .user-answer i { color: var(--chart-red); }
.correct-answer i { color: var(--chart-green); }

/* Actions */
.result-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.result-actions .btn {
    min-width: 200px;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .result-container {
        padding: 1rem;
    }

    .result-header {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }

    .result-meta {
        justify-content: center;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .answers-section {
        padding: 1.5rem;
    }

    .user-answer,
    .correct-answer {
        flex-direction: column;
        text-align: center;
    }

    .result-actions {
        flex-direction: column;
    }

    .result-actions .btn {
        width: 100%;
    }
}
</style>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?> 