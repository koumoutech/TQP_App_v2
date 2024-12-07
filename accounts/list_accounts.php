<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT * FROM accounts 
          WHERE name LIKE :search OR description LIKE :search
          LIMIT :offset, :limit";

$stmt = $conn->prepare($query);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestion des comptes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Liste des comptes applicatifs</h2>
        
        <div class="actions">
            <a href="add_account.php" class="btn btn-primary">Ajouter un compte</a>
            <a href="export_accounts.php" class="btn btn-secondary">Exporter en Excel</a>
        </div>

        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Rechercher un compte..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Nom du compte</th>
                    <th>Lien</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?php echo htmlspecialchars($account['name']); ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($account['link']); ?>" target="_blank">
                            <?php echo htmlspecialchars($account['link']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($account['description']); ?></td>
                    <td>
                        <a href="view_account.php?id=<?php echo $account['id']; ?>" 
                           class="btn btn-info">Détails</a>
                        <a href="edit_account.php?id=<?php echo $account['id']; ?>" 
                           class="btn btn-primary">Éditer</a>
                        <button onclick="deleteAccount(<?php echo $account['id']; ?>)" 
                                class="btn btn-danger">Supprimer</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function deleteAccount(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce compte ?')) {
            window.location.href = 'delete_account.php?id=' + id;
        }
    }
    </script>
</body>
</html> 