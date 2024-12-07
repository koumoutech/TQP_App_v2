document.addEventListener('DOMContentLoaded', function() {
    // Créer le bouton du menu burger si on est sur mobile
    if (window.innerWidth <= 768) {
        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(menuToggle);

        // Gérer le clic sur le menu burger
        menuToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
            this.innerHTML = sidebar.classList.contains('active') ? 
                '<i class="fas fa-times"></i>' : 
                '<i class="fas fa-bars"></i>';
        });

        // Fermer le menu au clic en dehors
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            if (!e.target.closest('.sidebar') && 
                !e.target.closest('.menu-toggle') && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    }

    // Gérer le redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        if (window.innerWidth > 768) {
            if (menuToggle) menuToggle.style.display = 'none';
        } else {
            if (menuToggle) menuToggle.style.display = 'flex';
        }
    });
}); 