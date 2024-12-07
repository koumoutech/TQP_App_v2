<?php
session_start();
require_once '../config/database.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les informations de l'utilisateur
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "Mon Compte";
$base_url = "..";

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="account-container">
        <div class="account-header">
            <h2>Mon Compte</h2>
            <div class="user-info">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-building"></i>
                    <span><?php echo htmlspecialchars($user['service']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
            </div>
        </div>

        <div class="account-sections">
            <!-- Section Profil -->
            <div class="account-section">
                <h3>Profil</h3>
                <form id="profileForm" class="account-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </form>
            </div>

            <!-- Section Mot de passe -->
            <div class="account-section">
                <h3>Changer le mot de passe</h3>
                <form id="passwordForm" class="account-form">
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-control" required>
                        <small class="form-text text-muted">
                            Minimum 8 caractères, incluant majuscules, minuscules et chiffres
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Changer le mot de passe
                    </button>
                </form>
            </div>

            <!-- Section Statistiques -->
            <div class="account-section">
                <h3>Mes Statistiques</h3>
                <div class="stats-grid">
                    <?php
                    // Statistiques des quiz
                    $stats_query = "SELECT 
                        COUNT(*) as total_quizzes,
                        AVG(score) as avg_score,
                        MAX(score) as best_score
                        FROM quiz_results 
                        WHERE user_id = :user_id";
                    $stmt = $conn->prepare($stats_query);
                    $stmt->execute([':user_id' => $_SESSION['user_id']]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-details">
                            <h4>Quiz complétés</h4>
                            <p class="stat-number"><?php echo $stats['total_quizzes']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-details">
                            <h4>Score moyen</h4>
                            <p class="stat-number">
                                <?php echo number_format($stats['avg_score'], 1); ?>%
                            </p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-details">
                            <h4>Meilleur score</h4>
                            <p class="stat-number">
                                <?php echo number_format($stats['best_score'], 1); ?>%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.account-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.account-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.user-info {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-light);
}

.account-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.account-section {
    background: var(--bg-color);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--shadow);
}

.account-section h3 {
    margin-bottom: 1.5rem;
    color: var(--text-color);
    font-size: 1.25rem;
}

.account-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: var(--bg-light);
    padding: 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-color);
    color: var(--bg-dark);
    border-radius: 50%;
    font-size: 1.25rem;
}

.stat-details h4 {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-light);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-color);
    margin: 0;
}
</style>

<?php
$content = ob_get_clean();

// JavaScript supplémentaire
$extra_js = '<script src="js/account.js"></script>';

// Inclure le layout
include '../includes/layout.php';
?> 