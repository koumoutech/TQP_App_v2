<?php
session_start();
require_once '../config/database.php';

$account_id = $_GET['id'] ?? null;
if (!$account_id) {
    header('Location: list_accounts.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les informations du compte
$account_query = "SELECT * FROM accounts WHERE id = :id";
$account_stmt = $conn->prepare($account_query);
$account_stmt->execute([':id' => $account_id]);
$account = $account_stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    header('Location: list_accounts.php');
    exit();
}

// Récupérer les statuts des utilisateurs pour ce compte
$users_query = "SELECT u.username, u.service, ua.status 
                FROM users u 
                LEFT JOIN user_accounts ua ON u.id = ua.user_id 
                WHERE ua.account_id = :account_id
                ORDER BY u.service, u.username";
$users_stmt = $conn->prepare($users_query);
$users_stmt->execute([':account_id' => $account_id]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Détails du compte: <?php echo htmlspecialchars($account['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Détails du compte: <?php echo htmlspecialchars($account['name']); ?></h2>
        
        <div class="account-details">
            <p><strong>Lien:</strong> 
                <a href="<?php echo htmlspecialchars($account['link']); ?>" target="_blank">
                    <?php echo htmlspecialchars($account['link']); ?>
                </a>
            </p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($account['description']); ?></p>
        </div>

        <h3>Statuts des utilisateurs</h3>
        <div class="actions">
            <a href="export_account_users.php?id=<?php echo $account_id; ?>" 
               class="btn btn-secondary">Exporter en Excel</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Service</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['service']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($user['status']); ?>">
                            <?php echo htmlspecialchars($user['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button onclick="updateStatus(<?php echo $account_id; ?>, '<?php echo $user['username']; ?>')" 
                                class="btn btn-primary">Modifier statut</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function updateStatus(accountId, username) {
        const newStatus = prompt('Nouveau statut (actif, bloqué, pas de compte):', '');
        if (newStatus && ['actif', 'bloqué', 'pas de compte'].includes(newStatus.toLowerCase())) {
            window.location.href = `update_status.php?account_id=${accountId}&username=${username}&status=${newStatus}`;
        } else if (newStatus) {
            alert('Statut invalide. Les valeurs possibles sont: actif, bloqué, pas de compte');
        }
    }
    </script>
</body>
</html> 