<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $account_id = $_POST['account_id'] ?? null;
    $employee_name = trim($_POST['employee_name'] ?? '');
    $status = $_POST['status'] ?? 'no_account';
    
    if (!$account_id || empty($employee_name)) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit();
    }

    $conn->beginTransaction();

    // Vérifier si l'employé existe déjà pour ce compte
    $check_query = "SELECT id FROM account_users 
                   WHERE account_id = :account_id AND employee_name = :employee_name";
    $stmt = $conn->prepare($check_query);
    $stmt->execute([
        ':account_id' => $account_id,
        ':employee_name' => $employee_name
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet employé existe déjà pour ce compte']);
        exit();
    }

    // Ajouter l'employé
    $query = "INSERT INTO account_users (account_id, employee_name, status) 
              VALUES (:account_id, :employee_name, :status)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':account_id' => $account_id,
        ':employee_name' => $employee_name,
        ':status' => $status
    ]);

    // Logger l'activité
    logActivity($conn, $_SESSION['user_id'], "Ajout d'un employé", 
        "Compte ID: $account_id, Employé: $employee_name");

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Employé ajouté avec succès'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Erreur save_account_user: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'ajout'
    ]);
} 