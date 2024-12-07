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

// Récupérer les comptes de l'utilisateur
$query = "SELECT a.*, au.status as user_status, au.last_login
          FROM app_accounts a
          INNER JOIN account_users au ON a.id = au.account_id
          WHERE au.user_id = :user_id
          ORDER BY a.name";
$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Mes Comptes";
$base_url = "../..";

ob_start();
?>

<div class="content-wrapper">
    <!-- En-tête -->
    <div class="page-header">
        <h2>Mes Comptes</h2>
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchAccount" placeholder="Rechercher un compte..." class="form-control">
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo count($accounts); ?></div>
                <div class="stat-label">Total des comptes</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">
                    <?php 
                    echo count(array_filter($accounts, function($account) {
                        return $account['user_status'] === 'active';
                    }));
                    ?>
                </div>
                <div class="stat-label">Comptes actifs</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">
                    <?php 
                    echo count(array_filter($accounts, function($account) {
                        return $account['user_status'] === 'pending';
                    }));
                    ?>
                </div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
    </div>

    <!-- Liste des comptes -->
    <div class="accounts-grid">
        <?php if (empty($accounts)): ?>
        <div class="empty-state">
            <i class="fas fa-id-card"></i>
            <h3>Aucun compte</h3>
            <p>Vous n'avez pas encore de compte associé.</p>
        </div>
        <?php else: ?>
            <?php foreach ($accounts as $account): ?>
            <div class="account-card">
                <div class="account-header">
                    <div class="account-status <?php echo $account['user_status']; ?>">
                        <?php 
                        echo match($account['user_status']) {
                            'active' => 'Actif',
                            'blocked' => 'Bloqué',
                            'pending' => 'En attente',
                            default => 'Inconnu'
                        };
                        ?>
                    </div>
                    <h3><?php echo htmlspecialchars($account['name']); ?></h3>
                </div>

                <div class="account-body">
                    <p><?php echo htmlspecialchars($account['description']); ?></p>
                    
                    <div class="account-meta">
                        <?php if ($account['last_login']): ?>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Dernière connexion: <?php echo date('d/m/Y H:i', strtotime($account['last_login'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="account-actions">
                        <?php if ($account['user_status'] === 'active'): ?>
                        <a href="<?php echo htmlspecialchars($account['login_url']); ?>" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i>
                            Accéder
                        </a>
                        <?php endif; ?>
                        
                        <button class="btn btn-secondary" 
                                onclick="showAccountDetails(<?php echo $account['id']; ?>)">
                            <i class="fas fa-info-circle"></i>
                            Détails
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal des détails -->
<div class="modal" id="accountModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"></h3>
            <button class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script>
document.getElementById('searchAccount').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.account-card').forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('p').textContent.toLowerCase();
        
        if (name.includes(searchTerm) || description.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

function showAccountDetails(accountId) {
    fetch(`get_account_details.php?id=${accountId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = data.account.name;
                document.getElementById('modalBody').innerHTML = `
                    <div class="account-details">
                        <div class="details-section">
                            <h4>Informations</h4>
                            <p>${data.account.description}</p>
                        </div>
                        <div class="details-section">
                            <h4>Accès</h4>
                            <div class="access-info">
                                <p><strong>Identifiant:</strong> ${data.account.username}</p>
                                <p><strong>URL:</strong> <a href="${data.account.login_url}" target="_blank">${data.account.login_url}</a></p>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('accountModal').style.display = 'flex';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
}

function closeModal() {
    document.getElementById('accountModal').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?> 