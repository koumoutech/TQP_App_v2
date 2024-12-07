<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $service = $_POST['service'];
    $role = $_POST['role'];
    
    $query = "INSERT INTO users (username, password, service, role) 
              VALUES (:username, :password, :service, :role)";
              
    $stmt = $conn->prepare($query);
    
    try {
        $stmt->execute([
            ':username' => $username,
            ':password' => $password,
            ':service' => $service,
            ':role' => $role
        ]);
        $_SESSION['success'] = "Utilisateur ajouté avec succès";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout: " . $e->getMessage();
    }
    
    header('Location: list_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un utilisateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Ajouter un utilisateur</h2>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Service</label>
                <input type="text" name="service" required>
            </div>
            
            <div class="form-group">
                <label>Rôle</label>
                <select name="role" required>
                    <option value="user">Utilisateur</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
</body>
</html> 