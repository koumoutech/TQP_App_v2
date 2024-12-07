<?php
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validatePassword($password) {
    // Au moins 8 caractères, une majuscule, une minuscule et un chiffre
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

function logActivity($conn, $user_id, $action, $details = null) {
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address) 
              VALUES (:user_id, :action, :details, :ip_address)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':action' => $action,
        ':details' => $details,
        ':ip_address' => $_SERVER['REMOTE_ADDR']
    ]);
} 