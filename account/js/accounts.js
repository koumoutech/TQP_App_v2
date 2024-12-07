document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour le formulaire de compte
    const accountForm = document.getElementById('accountForm');
    if (accountForm) {
        accountForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('save_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#FFCC00'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: error.message || 'Une erreur est survenue',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
            });
        });
    }

    // Gestionnaires pour les boutons de fermeture des modales
    document.querySelectorAll('[data-action="close"]').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                if (modal.querySelector('form')) {
                    modal.querySelector('form').reset();
                }
            }
        });
    });

    // Gestionnaire de recherche d'utilisateurs
    const searchUser = document.getElementById('searchUser');
    if (searchUser) {
        searchUser.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.user-card').forEach(card => {
                const userName = card.querySelector('.user-name').textContent.toLowerCase();
                card.style.display = userName.includes(searchTerm) ? 'flex' : 'none';
            });
        });
    }
});

// Fonction pour afficher la modale d'ajout de compte
function showAddAccountModal() {
    document.getElementById('accountModalTitle').textContent = 'Nouveau Compte';
    document.getElementById('accountId').value = '';
    document.getElementById('accountForm').reset();
    document.getElementById('accountModal').style.display = 'flex';
}

// Fonction pour éditer un compte
function editAccount(accountId) {
    fetch(`get_account.php?id=${accountId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('accountModalTitle').textContent = 'Modifier le Compte';
                document.getElementById('accountId').value = data.id;
                document.getElementById('accountName').value = data.name;
                document.getElementById('accountLink').value = data.link;
                document.getElementById('accountDescription').value = data.description;
                document.getElementById('accountStatus').value = data.status;
                document.getElementById('accountModal').style.display = 'flex';
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: error.message || 'Impossible de charger les données du compte',
                icon: 'error',
                confirmButtonColor: '#FFCC00'
            });
        });
}

// Fonction pour supprimer un compte
function deleteAccount(accountId) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action supprimera définitivement le compte et tous ses utilisateurs associés !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFCC00',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ account_id: accountId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#FFCC00'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: error.message || 'Impossible de supprimer le compte',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
            });
        }
    });
}

// Fonction pour afficher les utilisateurs d'un compte
function showAccountUsers(accountId) {
    fetch(`get_account_users.php?id=${accountId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalAccountId').value = accountId;
                const usersList = document.getElementById('usersList');
                usersList.innerHTML = '';
                
                data.users.forEach(user => {
                    const userCard = createUserCard(user);
                    usersList.appendChild(userCard);
                });
                
                document.getElementById('usersModal').style.display = 'flex';
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: error.message || 'Impossible de charger les utilisateurs',
                icon: 'error',
                confirmButtonColor: '#FFCC00'
            });
        });
}

// Fonction pour créer une carte utilisateur
function createUserCard(user) {
    const div = document.createElement('div');
    div.className = 'user-card';
    div.innerHTML = `
        <div class="user-info">
            <div class="user-name">${user.employee_name}</div>
            <div class="user-status ${user.status}">
                ${getStatusLabel(user.status)}
            </div>
        </div>
        <button class="btn-icon text-danger" onclick="deleteAccountUser(${user.id})" 
                data-tooltip="Supprimer">
            <i class="fas fa-trash"></i>
        </button>
    `;
    return div;
}

// Fonction pour obtenir le libellé du statut
function getStatusLabel(status) {
    const labels = {
        'active': 'Actif',
        'blocked': 'Bloqué',
        'no_account': 'Pas de compte'
    };
    return labels[status] || status;
}

// Fonction pour exporter les données
function exportAccounts() {
    window.location.href = 'export_accounts.php';
} 