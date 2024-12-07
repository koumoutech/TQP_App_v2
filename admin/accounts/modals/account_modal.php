<?php ob_start(); ?>
<div id="accountModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="accountModalTitle">Nouveau Compte</h3>
            <button type="button" class="close-modal" data-action="close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="accountForm" class="modal-form">
            <input type="hidden" id="accountId" name="account_id">
            
            <div class="form-group">
                <label for="accountName">
                    <i class="fas fa-tag"></i>
                    Nom du compte
                </label>
                <input type="text" id="accountName" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="accountLink">
                    <i class="fas fa-link"></i>
                    Lien du compte
                </label>
                <input type="url" id="accountLink" name="link" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="accountDescription">
                    <i class="fas fa-info-circle"></i>
                    Description
                </label>
                <textarea id="accountDescription" name="description" class="form-control" 
                          rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="accountStatus">
                    <i class="fas fa-toggle-on"></i>
                    Statut
                </label>
                <select id="accountStatus" name="status" class="form-control" required>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-action="close">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div> 