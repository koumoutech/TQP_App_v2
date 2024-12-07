<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les comptes de l'utilisateur
$query = "SELECT a.*, ua.status
          FROM accounts a
          LEFT JOIN user_accounts ua ON a.id = ua.account_id AND ua.user_id = :user_id
          ORDER BY a.name";
$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['account_id'], $_POST['status'])) {
    $update_query = "INSERT INTO user_accounts (user_id, account_id, status) 
                     VALUES (:user_id, :account_id, :status)
                     ON DUPLICATE KEY UPDATE status = :status";
    $update_stmt = $conn->prepare($update_query);
    try {
        $update_stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':account_id' => $_POST['account_id'],
            ':status' => $_POST['status']
        ]);
        $_SESSION['success'] = "Statut mis à jour avec succès";
        header('Location: my_accounts.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mes Comptes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/user_menu.php'; ?>
    
    <div class="content">
        <h2>Mes Comptes Applicatifs</h2>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="accounts-grid">
            <?php foreach ($accounts as $account): ?>
            <div class="account-card">
                <h3><?php echo htmlspecialchars($account['name']); ?></h3>
                
                <?php if ($account['link']): ?>
                    <p><a href="<?php echo htmlspecialchars($account['link']); ?>" 
                          target="_blank" class="account-link">
                        Accéder au compte
                    </a></p>
                <?php endif; ?>
                
                <p class="description"><?php echo htmlspecialchars($account['description']); ?></p>
                
                <form method="POST" action="" class="status-form">
                    <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                    <div class="form-group">
                        <label>Statut actuel :</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="actif" <?php echo $account['status'] === 'actif' ? 'selected' : ''; ?>>
                                Actif
                            </option>
                            <option value="bloqué" <?php echo $account['status'] === 'bloqué' ? 'selected' : ''; ?>>
                                Bloqué
                            </option>
                            <option value="pas de compte" <?php echo $account['status'] === 'pas de compte' ? 'selected' : ''; ?>>
                                Pas de compte
                            </option>
                        </select>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 