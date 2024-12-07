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
$service = $_GET['service'] ?? '';
$role = $_GET['role'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construction de la requête
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($service) {
    $query .= " AND service = :service";
    $params[':service'] = $service;
}
if ($role) {
    $query .= " AND role = :role";
    $params[':role'] = $role;
}

// Compte total pour la pagination
$count_stmt = $conn->prepare(str_replace('*', 'COUNT(*)', $query));
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Requête finale avec pagination
$query .= " ORDER BY username ASC LIMIT :offset, :limit";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les services uniques pour le filtre
$services = $conn->query("SELECT DISTINCT service FROM users ORDER BY service")
                ->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Gestion des Utilisateurs";
$base_url = "../..";

// Actions dans l'en-tête
$header_actions = '
<div class="header-actions">
    <button class="btn btn-secondary" onclick="showServiceModal()">
        <i class="fas fa-building"></i> Gérer les services
    </button>
    <button class="btn btn-primary" onclick="showAddUserModal()">
        <i class="fas fa-user-plus"></i> Ajouter un utilisateur
    </button>
</div>';

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div class="form-group">
                <input type="text" name="search" placeholder="Rechercher un utilisateur..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            <div class="form-group">
                <select name="service" class="form-control">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $srv): ?>
                        <option value="<?php echo htmlspecialchars($srv); ?>"
                                <?php echo $service === $srv ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($srv); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <select name="role" class="form-control">
                    <option value="">Tous les rôles</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
    </div>

    <div class="users-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Service</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                    <td>
                        <span class="badge badge-service">
                            <?php echo htmlspecialchars($user['service']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                            <?php echo $user['role'] === 'admin' ? 'Admin' : 'Utilisateur'; ?>
                        </span>
                    </td>
                    <td class="actions">
                        <button class="btn-icon" onclick="editUser(<?php echo $user['id']; ?>)" 
                                data-tooltip="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon" 
                                onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                data-tooltip="Réinitialiser le mot de passe">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <button class="btn-icon text-danger" 
                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                data-tooltip="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&service=<?php echo urlencode($service); ?>&role=<?php echo urlencode($role); ?>" 
               class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

// CSS supplémentaire
$extra_css = '
<style>
.users-table {
    background: var(--bg-color);
    border-radius: 1rem;
    box-shadow: var(--shadow);
    margin-top: 2rem;
    overflow: hidden;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.table th {
    background: var(--bg-light);
    font-weight: 600;
    color: var(--text-color);
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
}

.badge-service {
    background-color: var(--primary-color);
    color: var(--bg-dark);
}

.badge-admin {
    background-color: #dc3545;
    color: white;
}

.badge-user {
    background-color: #28a745;
    color: white;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    padding: 0.5rem;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--text-light);
    border-radius: 0.375rem;
    transition: all 0.2s;
}

.btn-icon:hover {
    background-color: var(--bg-light);
    color: var(--primary-color);
}

.btn-icon.text-danger:hover {
    background-color: #dc354522;
    color: #dc3545;
}
</style>';

// Inclure la modale utilisateur
include 'modals/user_modal.php';

// JavaScript supplémentaire
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/services.js"></script>
<script src="js/users.js"></script>';

// Inclure le layout
include '../../includes/layout.php';

// À la fin du fichier, avant d'inclure le layout
include 'modals/service_modal.php';
?> 