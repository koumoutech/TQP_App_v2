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

// Récupérer les comptes avec leurs statistiques
$query = "SELECT a.*, 
          COUNT(DISTINCT au.id) as total_users,
          SUM(CASE WHEN au.status = 'active' THEN 1 ELSE 0 END) as active_users,
          SUM(CASE WHEN au.status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,
          SUM(CASE WHEN au.status = 'no_account' THEN 1 ELSE 0 END) as no_account_users
          FROM app_accounts a
          LEFT JOIN account_users au ON a.id = au.account_id
          GROUP BY a.id
          ORDER BY a.name";
$stmt = $conn->prepare($query);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestion des Comptes";
$base_url = "..";

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="content-header">
        <h2>Gestion des Comptes Applicatifs</h2>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showAddAccountModal()">
                <i class="fas fa-plus"></i> Nouveau Compte
            </button>
            <button class="btn btn-secondary" onclick="exportAccounts()">
                <i class="fas fa-download"></i> Exporter
            </button>
        </div>
    </div>

    <div class="search-section">
        <div class="search-box">
            <input type="text" id="searchAccount" placeholder="Rechercher un compte..." 
                   class="form-control">
            <i class="fas fa-search"></i>
        </div>
    </div>

    <div class="accounts-grid">
        <?php foreach ($accounts as $account): ?>
        <div class="account-card">
            <div class="account-header">
                <h3><?php echo htmlspecialchars($account['name']); ?></h3>
                <span class="status-badge <?php echo $account['status']; ?>">
                    <?php echo $account['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                </span>
            </div>
            
            <div class="account-body">
                <div class="account-link">
                    <i class="fas fa-link"></i>
                    <a href="<?php echo htmlspecialchars($account['link']); ?>" target="_blank">
                        <?php echo htmlspecialchars($account['link']); ?>
                    </a>
                </div>
                
                <p class="account-description">
                    <?php echo htmlspecialchars($account['description']); ?>
                </p>
                
                <div class="users-stats">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $account['total_users']; ?> utilisateurs</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-user-check text-success"></i>
                        <span><?php echo $account['active_users']; ?> actifs</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-user-lock text-danger"></i>
                        <span><?php echo $account['blocked_users']; ?> bloqués</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-user-slash text-muted"></i>
                        <span><?php echo $account['no_account_users']; ?> sans compte</span>
                    </div>
                </div>
            </div>
            
            <div class="account-actions">
                <button class="btn-icon" onclick="showAccountUsers(<?php echo $account['id']; ?>)" 
                        data-tooltip="Gérer les utilisateurs">
                    <i class="fas fa-users"></i>
                </button>
                <button class="btn-icon" onclick="editAccount(<?php echo $account['id']; ?>)" 
                        data-tooltip="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon text-danger" onclick="deleteAccount(<?php echo $account['id']; ?>)" 
                        data-tooltip="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.search-section {
    margin-bottom: 2rem;
    padding: 0 1rem;
}

.search-box {
    position: relative;
    max-width: 500px;
    margin: 0 auto;
}

.search-box i {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
}

.accounts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}

.account-card {
    background: var(--bg-color);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--shadow);
}

.account-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.account-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
}

.status-badge.active {
    background-color: rgba(0, 166, 81, 0.1);
    color: var(--success-color);
}

.status-badge.inactive {
    background-color: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
}

.account-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.account-link a {
    color: var(--primary-color);
    text-decoration: none;
}

.account-description {
    color: var(--text-light);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.users-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-light);
    font-size: 0.875rem;
}

.account-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

@media (max-width: 768px) {
    .accounts-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();

// Inclure les modales
include 'modals/account_modal.php';
include 'modals/users_modal.php';

// JavaScript supplémentaire
$extra_js = '
<script src="js/accounts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Inclure le layout
include '../includes/layout.php';
?> 