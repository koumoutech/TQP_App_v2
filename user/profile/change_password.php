<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$page_title = "Changer le mot de passe";
$base_url = "../..";

ob_start();
?>

<div class="content-wrapper">
    <div class="page-header">
        <h2>Changer le mot de passe</h2>
    </div>

    <div class="form-container">
        <form id="changePasswordForm" method="POST">
            <div class="form-group">
                <label for="currentPassword">
                    <i class="fas fa-lock"></i>
                    Mot de passe actuel
                </label>
                <input type="password" 
                       id="currentPassword" 
                       name="current_password" 
                       class="form-control" 
                       required>
            </div>

            <div class="form-group">
                <label for="newPassword">
                    <i class="fas fa-key"></i>
                    Nouveau mot de passe
                </label>
                <input type="password" 
                       id="newPassword" 
                       name="new_password" 
                       class="form-control" 
                       required>
                <div class="password-requirements">
                    <p>Le mot de passe doit contenir :</p>
                    <ul>
                        <li id="length">Au moins 8 caractères</li>
                        <li id="uppercase">Au moins une majuscule</li>
                        <li id="lowercase">Au moins une minuscule</li>
                        <li id="number">Au moins un chiffre</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">
                    <i class="fas fa-check-circle"></i>
                    Confirmer le nouveau mot de passe
                </label>
                <input type="password" 
                       id="confirmPassword" 
                       name="confirm_password" 
                       class="form-control" 
                       required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="../home.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

// JavaScript supplémentaire
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/change_password.js"></script>';

include '../../includes/layout.php';
?> 