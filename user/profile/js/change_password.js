document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('changePasswordForm');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    
    // Éléments de validation
    const lengthCheck = document.getElementById('length');
    const upperCheck = document.getElementById('uppercase');
    const lowerCheck = document.getElementById('lowercase');
    const numberCheck = document.getElementById('number');
    
    // Validation en temps réel du mot de passe
    newPassword.addEventListener('input', function() {
        const password = this.value;
        
        // Vérifier la longueur
        if (password.length >= 8) {
            lengthCheck.classList.add('valid');
        } else {
            lengthCheck.classList.remove('valid');
        }
        
        // Vérifier les majuscules
        if (/[A-Z]/.test(password)) {
            upperCheck.classList.add('valid');
        } else {
            upperCheck.classList.remove('valid');
        }
        
        // Vérifier les minuscules
        if (/[a-z]/.test(password)) {
            lowerCheck.classList.add('valid');
        } else {
            lowerCheck.classList.remove('valid');
        }
        
        // Vérifier les chiffres
        if (/[0-9]/.test(password)) {
            numberCheck.classList.add('valid');
        } else {
            numberCheck.classList.remove('valid');
        }
    });
    
    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Vérifier que les mots de passe correspondent
        if (newPassword.value !== confirmPassword.value) {
            Swal.fire({
                title: 'Erreur',
                text: 'Les mots de passe ne correspondent pas',
                icon: 'error',
                confirmButtonColor: '#FFCC30'
            });
            return;
        }
        
        // Vérifier la complexité du mot de passe
        const password = newPassword.value;
        const isValid = password.length >= 8 && 
                       /[A-Z]/.test(password) && 
                       /[a-z]/.test(password) && 
                       /[0-9]/.test(password);
        
        if (!isValid) {
            Swal.fire({
                title: 'Erreur',
                text: 'Le mot de passe ne respecte pas les critères de sécurité',
                icon: 'error',
                confirmButtonColor: '#FFCC30'
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
                    confirmButtonColor: '#FFCC30'
                }).then(() => {
                    window.location.href = '../home.php';
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
                confirmButtonColor: '#FFCC30'
            });
        });
    });
}); 