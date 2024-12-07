<?php
session_start();
require_once '../config/database.php';
require_once '../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $account_id = $_POST['account_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name) || empty($link)) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
        exit();
    }

    // Vérifier si le nom existe déjà
    $check_query = "SELECT id FROM app_accounts WHERE name = :name AND id != :id";
    $stmt = $conn->prepare($check_query);
    $stmt->execute([
        ':name' => $name,
        ':id' => $account_id ?? 0
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Un compte avec ce nom existe déjà']);
        exit();
    }

    $conn->beginTransaction();

    if ($account_id) {
        // Mise à jour
        $query = "UPDATE app_accounts 
                  SET name = :name, link = :link, description = :description, status = :status 
                  WHERE id = :id";
        $params = [
            ':name' => $name,
            ':link' => $link,
            ':description' => $description,
            ':status' => $status,
            ':id' => $account_id
        ];
        $action = "Modification du compte";
        $message = "Compte modifié avec succès";
    } else {
        // Création
        $query = "INSERT INTO app_accounts (name, link, description, status) 
                  VALUES (:name, :link, :description, :status)";
        $params = [
            ':name' => $name,
            ':link' => $link,
            ':description' => $description,
            ':status' => $status
        ];
        $action = "Création d'un compte";
        $message = "Compte créé avec succès";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], $action, "Compte: $name");

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Erreur save_account: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'enregistrement'
    ]);
} 