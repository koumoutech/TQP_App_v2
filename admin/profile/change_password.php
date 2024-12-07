<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$page_title = "Changer le mot de passe";
$base_url = "../..";

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="password-form-container">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-key"></i> Changer votre mot de passe</h3>
            </div>
            <div class="card-body">
                <form id="changePasswordForm" method="POST" action="save_password.php">
                    <div class="form-group">
                        <label for="currentPassword">
                            <i class="fas fa-lock"></i>
                            Mot de passe actuel
                        </label>
                        <input type="password" id="currentPassword" name="current_password" 
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="newPassword">
                            <i class="fas fa-key"></i>
                            Nouveau mot de passe
                        </label>
                        <input type="password" id="newPassword" name="new_password" 
                               class="form-control" required>
                        <small class="form-text text-muted">
                            Le mot de passe doit contenir au moins 8 caractères, incluant majuscules, 
                            minuscules et chiffres
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">
                            <i class="fas fa-check-circle"></i>
                            Confirmer le nouveau mot de passe
                        </label>
                        <input type="password" id="confirmPassword" name="confirm_password" 
                               class="form-control" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.password-form-container {
    max-width: 600px;
    margin: 2rem auto;
}

.card {
    background: var(--bg-color);
    border-radius: 1rem;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.card-header {
    background: var(--bg-light);
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.card-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-color);
}

.card-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-actions {
    margin-top: 2rem;
    display: flex;
    justify-content: flex-end;
}
</style>

<?php
$content = ob_get_clean();

// JavaScript supplémentaire
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/change_password.js"></script>';

// Inclure le layout
include '../../includes/layout.php';
?> 