<?php
session_start();
require_once '../config/database.php';

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier la connexion à la base de données
        if (!$conn) {
            throw new Exception("Erreur de connexion à la base de données");
        }

        // Rechercher l'utilisateur
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Débogage
        echo "<pre>";
        echo "Tentative de connexion:\n";
        echo "Username: " . $username . "\n";
        echo "Password: " . $password . "\n";
        
        if ($user) {
            echo "Utilisateur trouvé:\n";
            print_r($user);
            
            $verify = password_verify($password, $user['password']);
            echo "Résultat password_verify(): " . ($verify ? "true" : "false") . "\n";
        } else {
            echo "Aucun utilisateur trouvé avec ce nom d'utilisateur\n";
        }
        echo "</pre>";

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['service'] = $user['service'];
            
            header('Location: ' . ($user['role'] === 'admin' ? '../admin/dashboard.php' : '../products/user_products.php'));
            exit();
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect";
        }
    } catch (Exception $e) {
        $error = "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connexion - TQP App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="logo-container">
                <img src="../assets/images/logo.png" alt="TQP App Logo" class="login-logo">
            </div>
            <h2>Bienvenue sur TQP App</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Nom d'utilisateur
                    </label>
                    <input type="text" 
                           id="username"
                           name="username" 
                           class="form-control" 
                           required 
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Mot de passe
                    </label>
                    <div class="password-input">
                        <input type="password" 
                               id="password"
                               name="password" 
                               class="form-control" 
                               required
                               autocomplete="current-password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
        </div>
    </div>

    <script>
    document.querySelector('.toggle-password').addEventListener('click', function() {
        const password = document.querySelector('#password');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    </script>
</body>
</html> 