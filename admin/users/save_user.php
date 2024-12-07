<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $user_id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($service) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires']);
        exit();
    }

    // Vérifier si l'email est valide
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse email invalide']);
        exit();
    }

    $conn->beginTransaction();

    // Vérifier si le nom d'utilisateur existe déjà
    $check_query = "SELECT id FROM users WHERE username = :username AND id != :id";
    $stmt = $conn->prepare($check_query);
    $stmt->execute([
        ':username' => $username,
        ':id' => $user_id ?? 0
    ]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce nom d\'utilisateur existe déjà']);
        exit();
    }

    // Vérifier si l'email existe déjà
    $check_query = "SELECT id FROM users WHERE email = :email AND id != :id";
    $stmt = $conn->prepare($check_query);
    $stmt->execute([
        ':email' => $email,
        ':id' => $user_id ?? 0
    ]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cette adresse email existe déjà']);
        exit();
    }

    if ($user_id) {
        // Mise à jour
        $query = "UPDATE users SET 
                  username = :username, 
                  service = :service, 
                  role = :role 
                  WHERE id = :id";
        $params = [
            ':username' => $username,
            ':service' => $service,
            ':role' => $role,
            ':id' => $user_id
        ];
        $action = "Modification de l'utilisateur";
    } else {
        // Création
        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe est requis pour un nouvel utilisateur']);
            exit();
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, service, role, password) 
                  VALUES (:username, :service, :role, :password)";
        $params = [
            ':username' => $username,
            ':service' => $service,
            ':role' => $role,
            ':password' => $hashed_password
        ];
        $action = "Création d'un nouvel utilisateur";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], $action, "Utilisateur: $username");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $user_id ? 'Utilisateur modifié avec succès' : 'Utilisateur créé avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
} 