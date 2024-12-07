<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    header('Location: available_quiz.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les informations du quiz
$query = "SELECT * FROM quizzes WHERE id = :id AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute([':id' => $quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: available_quiz.php');
    exit();
}

// Récupérer les questions du quiz
$query = "SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id ORDER BY RAND()";
$stmt = $conn->prepare($query);
$stmt->execute([':quiz_id' => $quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pour chaque question, récupérer ses réponses
foreach ($questions as &$question) {
    $query = "SELECT * FROM quiz_answers WHERE question_id = :question_id ORDER BY RAND()";
    $stmt = $conn->prepare($query);
    $stmt->execute([':question_id' => $question['id']]);
    $question['answers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = $quiz['title'];
$base_url = "../..";

ob_start();
?>

<div class="quiz-container">
    <!-- En-tête du quiz -->
    <div class="quiz-header">
        <div class="quiz-info">
            <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
            <div class="quiz-meta">
                <span><i class="fas fa-clock"></i> <?php echo $quiz['duration']; ?> minutes</span>
                <span><i class="fas fa-question-circle"></i> <?php echo count($questions); ?> questions</span>
            </div>
        </div>
        <div class="quiz-timer" id="quizTimer" data-duration="<?php echo $quiz['duration'] * 60; ?>">
            <i class="fas fa-hourglass-half"></i>
            <span>--:--</span>
        </div>
    </div>

    <!-- Formulaire du quiz -->
    <form id="quizForm" data-quiz-id="<?php echo $quiz_id; ?>">
        <!-- Progression -->
        <div class="quiz-progress">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <div class="progress-text">
                Question <span id="currentQuestionNum">1</span> sur <?php echo count($questions); ?>
            </div>
        </div>

        <!-- Questions -->
        <div class="questions-container">
            <?php foreach ($questions as $index => $question): ?>
            <div class="question-card" data-question="<?php echo $index + 1; ?>" style="display: none;">
                <div class="question-content">
                    <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                    <?php if ($question['image_url']): ?>
                    <div class="question-image">
                        <img src="<?php echo htmlspecialchars($question['image_url']); ?>" alt="Question image">
                    </div>
                    <?php endif; ?>

                    <div class="answers-list">
                        <?php foreach ($question['answers'] as $answer): ?>
                        <label class="answer-option">
                            <input type="radio" 
                                   name="question_<?php echo $question['id']; ?>" 
                                   value="<?php echo $answer['id']; ?>" 
                                   required>
                            <span class="answer-text">
                                <?php echo htmlspecialchars($answer['answer_text']); ?>
                            </span>
                            <span class="answer-check"></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigation -->
        <div class="quiz-navigation">
            <button type="button" class="btn btn-secondary" id="prevQuestion" disabled>
                <i class="fas fa-arrow-left"></i> Précédent
            </button>
            <div class="question-dots">
                <?php for ($i = 0; $i < count($questions); $i++): ?>
                <span class="dot" data-question="<?php echo $i + 1; ?>"></span>
                <?php endfor; ?>
            </div>
            <button type="button" class="btn btn-primary" id="nextQuestion">
                Suivant <i class="fas fa-arrow-right"></i>
            </button>
        </div>

        <!-- Bouton de soumission -->
        <div class="quiz-submit" style="display: none;">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-check-circle"></i> Terminer le quiz
            </button>
        </div>
    </form>
</div>

<style>
.quiz-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* En-tête du quiz */
.quiz-header {
    background: var(--sidebar-bg);
    padding: 2rem;
    color: var(--sidebar-text);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.quiz-info h2 {
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

.quiz-meta {
    display: flex;
    gap: 1.5rem;
    font-size: 0.9rem;
}

.quiz-timer {
    background: rgba(0, 0, 0, 0.1);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Barre de progression */
.quiz-progress {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #eee;
}

.progress-bar {
    height: 6px;
    background: #eee;
    border-radius: 3px;
    margin-bottom: 0.75rem;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: var(--sidebar-bg);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.9rem;
    color: #666;
    text-align: center;
}

/* Questions */
.questions-container {
    padding: 2rem;
}

.question-content h3 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.question-image {
    margin: 1.5rem 0;
    text-align: center;
}

.question-image img {
    max-width: 100%;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Réponses */
.answers-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.answer-option {
    position: relative;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid #eee;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: block;
}

.answer-option:hover {
    border-color: var(--sidebar-bg);
    background: rgba(255, 204, 48, 0.05);
}

.answer-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.answer-text {
    display: block;
    font-size: 1rem;
    line-height: 1.5;
}

.answer-check {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.answer-option input[type="radio"]:checked + .answer-text + .answer-check {
    border-color: var(--sidebar-bg);
    background: var(--sidebar-bg);
}

.answer-option input[type="radio"]:checked + .answer-text + .answer-check::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
}

/* Navigation */
.quiz-navigation {
    padding: 1.5rem 2rem;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.question-dots {
    display: flex;
    gap: 0.5rem;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #eee;
    cursor: pointer;
    transition: all 0.3s ease;
}

.dot.active {
    background: var(--sidebar-bg);
    transform: scale(1.2);
}

.dot.answered {
    background: var(--accent-color);
}

/* Bouton de soumission */
.quiz-submit {
    padding: 2rem;
    text-align: center;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.question-card.active {
    display: block !important;
    animation: fadeIn 0.3s ease forwards;
}

/* Responsive */
@media (max-width: 768px) {
    .quiz-container {
        margin: 0;
        border-radius: 0;
        min-height: 100vh;
    }

    .quiz-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .quiz-meta {
        justify-content: center;
    }

    .questions-container {
        padding: 1.5rem;
    }

    .quiz-navigation {
        flex-direction: column;
        gap: 1rem;
    }

    .question-dots {
        order: -1;
    }

    .btn {
        width: 100%;
    }
}
</style>

<?php
$content = ob_get_clean();

// JavaScript supplémentaire
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/take_quiz.js"></script>';

include '../../includes/layout.php';
?> 