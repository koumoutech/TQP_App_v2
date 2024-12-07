<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vérifier le mot de passe actuel
    $query = "SELECT password FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Le mot de passe actuel est incorrect";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = :password WHERE id = :id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([
                ':password' => $hashed_password,
                ':id' => $_SESSION['user_id']
            ]);
            $_SESSION['success'] = "Mot de passe modifié avec succès";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la modification: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Changer le mot de passe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Changer le mot de passe</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Mot de passe actuel</label>
                <input type="password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
        </form>
    </div>
</body>
</html> 