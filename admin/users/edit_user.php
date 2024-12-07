<?php
session_start();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: list_users.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $service = $_POST['service'];
    $role = $_POST['role'];
    
    // Si un nouveau mot de passe est fourni
    $password_update = '';
    $params = [
        ':username' => $username,
        ':service' => $service,
        ':role' => $role,
        ':id' => $user_id
    ];
    
    if (!empty($_POST['password'])) {
        $password_update = ", password = :password";
        $params[':password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    
    $query = "UPDATE users SET 
              username = :username,
              service = :service,
              role = :role" . $password_update . "
              WHERE id = :id";
              
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $_SESSION['success'] = "Utilisateur modifié avec succès";
        header('Location: list_users.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification: " . $e->getMessage();
    }
}

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: list_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modifier un utilisateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Modifier l'utilisateur</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                <input type="password" name="password">
            </div>
            
            <div class="form-group">
                <label>Service</label>
                <input type="text" name="service" value="<?php echo htmlspecialchars($user['service']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Rôle</label>
                <select name="role" required>
                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                        Utilisateur
                    </option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                        Administrateur
                    </option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Modifier</button>
            <a href="list_users.php" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</body>
</html> 