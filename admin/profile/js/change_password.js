document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
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
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
    if (!passwordRegex.test(newPassword)) {
        Swal.fire({
            title: 'Erreur',
            text: 'Le mot de passe doit contenir au moins 8 caractères, incluant majuscules, minuscules et chiffres',
            icon: 'error',
            confirmButtonColor: '#FFCC00'
        });
        return;
    }
    
    // Envoyer le formulaire
    const formData = new FormData(this);
    
    fetch('save_password.php', {
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
                this.reset();
            });
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Erreur',
            text: error.message || 'Une erreur est survenue',
            icon: 'error',
            confirmButtonColor: '#FFCC00'
        });
    });
}); 