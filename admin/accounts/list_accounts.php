<?php
// Démarrer le buffer de sortie au tout début
ob_start();

session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

// Générer le nonce au début du fichier
$nonce = base64_encode(random_bytes(16));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
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
$base_url = "../..";

// Contenu principal
ob_start();
?>

<style>
.content-wrapper {
    padding: 2rem;
    max-width: 1600px;
    margin: 0 auto;
}

.content-header {
    background: var(--bg-color);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.content-header:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.3s ease, height 0.3s ease;
}

.btn:active::after {
    width: 200px;
    height: 200px;
}

.search-section {
    margin-bottom: 2rem;
}

.search-box {
    background: var(--bg-color);
    border-radius: 2rem;
    padding: 0.5rem 1.5rem;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    max-width: 500px;
    margin: 0 auto;
    transition: all 0.3s ease;
}

.search-box:focus-within {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.search-box input {
    border: none;
    background: none;
    padding: 0.75rem;
    width: 100%;
    outline: none;
    color: var(--text-color);
}

.search-box i {
    color: var(--text-light);
}

.accounts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
    animation: fadeIn 0.5s ease;
}

.account-card {
    background: var(--bg-color);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.account-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.account-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.account-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--primary-color);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.status-badge.active {
    background: rgba(0, 200, 81, 0.1);
    color: #00c851;
}

.status-badge.inactive {
    background: rgba(255, 82, 82, 0.1);
    color: #ff5252;
}

.account-link {
    margin: 1rem 0;
    padding: 0.75rem;
    background: var(--bg-light);
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.account-link:hover {
    background: var(--bg-lighter);
}

.account-link a {
    color: var(--primary-color);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.users-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin: 1.5rem 0;
}

.stat-item {
    background: var(--bg-light);
    padding: 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.stat-item:hover {
    background: var(--bg-lighter);
    transform: translateY(-2px);
}

.account-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.btn-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    background: var(--bg-light);
    border: none;
    cursor: pointer;
    color: var(--text-color);
}

.btn-icon:hover {
    transform: translateY(-2px);
    background: var(--bg-lighter);
}

.btn-icon.text-danger:hover {
    color: #ff5252;
    background: rgba(255, 82, 82, 0.1);
}

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

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem;
    }
    
    .accounts-grid {
        grid-template-columns: 1fr;
    }
    
    .users-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <h2>Gestion des Comptes Applicatifs</h2>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showAddAccountModal()">
                <i class="fas fa-plus"></i> Nouveau Compte
            </button>
            <button class="btn btn-secondary" onclick="exportAccounts(); return false;">
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
include '../../includes/layout.php';
?> 