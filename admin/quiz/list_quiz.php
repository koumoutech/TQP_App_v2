<?php
ob_start();
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Filtres
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construction de la requête
$query = "SELECT q.*, 
          GROUP_CONCAT(qs.service_name) as services,
          COUNT(DISTINCT qr.id) as total_attempts,
          COALESCE(AVG(qr.score), 0) as avg_score
          FROM quizzes q 
          LEFT JOIN quiz_services qs ON q.id = qs.quiz_id
          LEFT JOIN quiz_results qr ON q.id = qr.quiz_id
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND q.title LIKE :search";
    $params[':search'] = "%$search%";
}

$query .= " GROUP BY q.id";

// Compte total pour la pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM quizzes q WHERE 1=1" . 
    ($search ? " AND q.title LIKE :search" : ""));
$count_stmt->execute($params);
$total_quizzes = $count_stmt->fetchColumn();
$total_pages = ceil($total_quizzes / $limit);

// Requête finale avec pagination
$query .= " ORDER BY q.created_at DESC LIMIT :offset, :limit";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestion des Quiz";
$base_url = "../..";

// Actions dans l'en-tête
$header_actions = '
<div class="header-actions">
    <button class="btn btn-primary" onclick="showAddQuizModal()">
        <i class="fas fa-plus"></i> Créer un quiz
    </button>
    <button class="btn btn-secondary" onclick="exportQuizzes()">
        <i class="fas fa-download"></i> Exporter
    </button>
</div>';

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div class="form-group">
                <input type="text" name="search" placeholder="Rechercher un quiz..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
    </div>

    <div class="quiz-grid">
        <?php foreach ($quizzes as $quiz): ?>
        <div class="quiz-card">
            <div class="quiz-header">
                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
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
            
            <div class="quiz-stats">
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo $quiz['duration']; ?> min</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <span><?php echo $quiz['total_attempts']; ?> tentatives</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chart-line"></i>
                    <span><?php echo number_format($quiz['avg_score'], 1); ?>% moy.</span>
                </div>
            </div>
            
            <div class="quiz-actions">
                <button class="btn-icon" onclick="editQuiz(<?php echo $quiz['id']; ?>)"
                        data-tooltip="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon" onclick="manageQuestions(<?php echo $quiz['id']; ?>)"
                        data-tooltip="Gérer les questions">
                    <i class="fas fa-list-check"></i>
                </button>
                <button class="btn-icon" onclick="viewQuizStats(<?php echo $quiz['id']; ?>)"
                        data-tooltip="Voir les statistiques">
                    <i class="fas fa-chart-bar"></i>
                </button>
                <button class="btn-icon text-danger" onclick="deleteQuiz(<?php echo $quiz['id']; ?>)"
                        data-tooltip="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
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
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
               class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

// CSS supplémentaire pour les quiz
$extra_css = '
<style>
.quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.quiz-card {
    background: var(--bg-color);
    border-radius: 1rem;
    box-shadow: var(--shadow);
    padding: 1.5rem;
    transition: transform 0.2s;
}

.quiz-card:hover {
    transform: translateY(-2px);
}

.quiz-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.quiz-header h3 {
    font-size: 1.25rem;
    margin: 0;
    flex: 1;
    margin-right: 1rem;
}

.quiz-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--bg-light);
    border-radius: 0.5rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-light);
    text-align: center;
}

.stat-item i {
    font-size: 1.25rem;
    color: var(--primary-color);
}

.quiz-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.quiz-dates {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-light);
    font-size: 0.9rem;
}

.services-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.badge-service {
    background-color: var(--primary-color);
    color: var(--bg-dark);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
}
</style>';

// Inclure la modale de quiz
include 'modals/quiz_modal.php';

// JavaScript supplémentaire
$extra_js = '
<script src="js/quiz.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Inclure le layout
include '../../includes/layout.php';
?> 