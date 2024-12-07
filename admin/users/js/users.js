function showAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter un utilisateur';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('confirm_password').required = true;
    document.getElementById('passwordHint').style.display = 'block';
    
    // Charger les services avant d'afficher la modale
    loadServices()
        .then(() => {
            document.getElementById('userModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: 'Impossible de charger la liste des services',
                icon: 'error',
                confirmButtonColor: '#FFCC00'
            });
        });
}

function editUser(userId) {
    fetch(`get_user.php?id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(user => {
            if (!user.success) {
                throw new Error(user.message || 'Erreur lors de la récupération des données');
            }
            
            // Charger les services avant de remplir le formulaire
            return loadServices().then(() => {
                document.getElementById('modalTitle').textContent = 'Modifier l\'utilisateur';
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username;
                document.getElementById('email').value = user.email || '';
                document.getElementById('service').value = user.service;
                document.getElementById('role').value = user.role;
                
                // Le mot de passe n'est pas requis en modification
                document.getElementById('password').required = false;
                document.getElementById('confirm_password').required = false;
                document.getElementById('passwordHint').style.display = 'none';
                
                document.getElementById('userModal').style.display = 'flex';
            });
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: error.message || 'Impossible de charger les données de l\'utilisateur',
                icon: 'error',
                confirmButtonColor: '#FFCC00'
            });
        });
}

function resetPassword(userId, username) {
    Swal.fire({
        title: 'Réinitialiser le mot de passe ?',
        text: `Voulez-vous réinitialiser le mot de passe de ${username} ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFCC00',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, réinitialiser',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('reset_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#FFCC00'
                    });
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire('Erreur', 'Impossible de réinitialiser le mot de passe', 'error');
            });
        }
    });
}

function deleteUser(userId, username) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: `Voulez-vous vraiment supprimer l'utilisateur ${username} ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFCC00',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete_user.php?id=${userId}`;
        }
    });
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('userForm').reset();
}

// Validation du formulaire
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Vérifier si les mots de passe correspondent
    if (password !== confirmPassword) {
        Swal.fire('Erreur', 'Les mots de passe ne correspondent pas', 'error');
        return;
    }
    
    // Vérifier la complexité du mot de passe si renseigné
    if (password && !isPasswordValid(password)) {
        Swal.fire('Erreur', 'Le mot de passe doit contenir au moins 8 caractères, incluant majuscules, minuscules et chiffres', 'error');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('save_user.php', {
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
            Swal.fire('Erreur', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        Swal.fire('Erreur', 'Une erreur est survenue', 'error');
    });
});

// Fonction de validation du mot de passe
function isPasswordValid(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    
    return password.length >= minLength && hasUpperCase && hasLowerCase && hasNumbers;
} 