<?php ob_start(); ?>

<div id="userModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter un utilisateur</h3>
            <button type="button" class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="userForm" method="POST" action="save_user.php">
            <input type="hidden" name="user_id" id="userId">
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i>
                    Nom d'utilisateur
                </label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Email
                </label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="service">
                        <i class="fas fa-building"></i>
                        Service
                    </label>
                    <select id="service" name="service" class="form-control" required>
                        <option value="">Sélectionner un service</option>
                        <?php foreach ($services_list as $service): ?>
                            <option value="<?php echo htmlspecialchars($service['name']); ?>">
                                <?php echo htmlspecialchars($service['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="role">
                        <i class="fas fa-user-shield"></i>
                        Rôle
                    </label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user">Utilisateur</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
            </div>
            
            <div id="passwordFields">
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Mot de passe
                    </label>
                    <input type="password" id="password" name="password" class="form-control">
                    <small class="form-text text-muted" id="passwordHint">
                        Minimum 8 caractères, incluant majuscules, minuscules et chiffres
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirmer le mot de passe
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-row > .form-group {
    flex: 1;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style> 