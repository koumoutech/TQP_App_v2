<?php ob_start(); ?>
<div id="usersModal" class="modal" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="usersModalTitle">Utilisateurs du Compte</h3>
            <button type="button" class="close-modal" data-action="close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="users-container">
            <div class="users-list">
                <div class="users-header">
                    <h4>Liste des Employés</h4>
                    <div class="search-box">
                        <input type="text" id="searchUser" placeholder="Rechercher un employé..." 
                               class="form-control">
                    </div>
                </div>
                <div id="usersList" class="users-grid">
                    <!-- Les utilisateurs seront chargés dynamiquement ici -->
                </div>
            </div>
            
            <div class="users-form">
                <h4>Ajouter un Employé</h4>
                <form id="addUserForm" class="add-user-form">
                    <input type="hidden" id="modalAccountId" name="account_id">
                    <div class="form-group">
                        <label for="employeeName">Nom de l'employé</label>
                        <input type="text" id="employeeName" name="employee_name" 
                               class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="employeeStatus">Statut</label>
                        <select id="employeeStatus" name="status" class="form-control" required>
                            <option value="active">Actif</option>
                            <option value="blocked">Bloqué</option>
                            <option value="no_account">Pas de compte</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.users-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    padding: 1.5rem;
}

.users-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    max-height: 500px;
    overflow-y: auto;
}

.user-card {
    background: var(--bg-light);
    padding: 1rem;
    border-radius: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-info {
    flex: 1;
}

.user-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.user-status {
    font-size: 0.875rem;
}

.user-status.active {
    color: var(--success-color);
}

.user-status.blocked {
    color: var(--danger-color);
}

.user-status.no_account {
    color: var(--text-light);
}

@media (max-width: 768px) {
    .users-container {
        grid-template-columns: 1fr;
    }
}
</style> 