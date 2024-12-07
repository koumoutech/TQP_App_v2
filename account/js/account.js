document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour le formulaire de profil
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
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

    // Gestionnaire pour le formulaire de mot de passe
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Vérifier que les mots de passe correspondent
            if (newPassword !== confirmPassword) {
                Swal.fire({
                    title: 'Erreur',
                    text: 'Les mots de passe ne correspondent pas',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
                return;
            }
            
            // Vérifier la complexité du mot de passe
            if (!isPasswordValid(newPassword)) {
                Swal.fire({
                    title: 'Erreur',
                    text: 'Le mot de passe doit contenir au moins 8 caractères, incluant majuscules, minuscules et chiffres',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    Swal.fire({
                        title: 'Succès',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#FFCC00'
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
});

// Fonction de validation du mot de passe
function isPasswordValid(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    
    return password.length >= minLength && hasUpperCase && hasLowerCase && hasNumbers;
} 