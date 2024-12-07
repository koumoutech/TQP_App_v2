// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les écouteurs d'événements
    const serviceForm = document.getElementById('serviceForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const serviceName = document.getElementById('serviceName').value.trim();
            if (!serviceName) {
                Swal.fire('Erreur', 'Le nom du service est requis', 'error');
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('save_service.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.reset();
                    loadServices().then(() => {
                        Swal.fire({
                            title: 'Succès',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#FFCC00'
                        });
                    });
                } else {
                    throw new Error(data.message || 'Erreur lors de l\'ajout du service');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: error.message || 'Impossible d\'ajouter le service',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
            });
        });
    }

    // Gestionnaire pour les boutons de fermeture
    document.querySelectorAll('[data-action="close"]').forEach(button => {
        button.addEventListener('click', closeServiceModal);
    });

    // Gestionnaire pour les boutons de suppression
    document.querySelectorAll('[data-action="delete"]').forEach(button => {
        button.addEventListener('click', function() {
            const serviceId = this.dataset.serviceId;
            const serviceName = this.dataset.serviceName;
            deleteService(serviceId, serviceName);
        });
    });
});

function loadServices() {
    return new Promise((resolve, reject) => {
        fetch('get_services.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(services => {
                if (!Array.isArray(services)) {
                    services = [];
                }
                
                const serviceSelect = document.getElementById('service');
                if (serviceSelect) {
                    const currentValue = serviceSelect.value;
                    serviceSelect.innerHTML = '<option value="">Sélectionner un service</option>';
                    services.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.name;
                        option.textContent = service.name;
                        serviceSelect.appendChild(option);
                    });
                    serviceSelect.value = currentValue;
                }

                const servicesList = document.getElementById('servicesList');
                if (servicesList) {
                    servicesList.innerHTML = '';
                    services.forEach(service => {
                        const serviceElement = createServiceElement(service);
                        servicesList.appendChild(serviceElement);
                    });
                }

                resolve(services);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des services:', error);
                reject(error);
            });
    });
}

function showServiceModal() {
    loadServices()
        .then(() => {
            document.getElementById('serviceModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: 'Impossible de charger les services',
                icon: 'error',
                confirmButtonColor: '#FFCC00'
            });
        });
}

let isServiceFullscreen = false;

function toggleServiceFullscreen() {
    const modalContent = document.getElementById('serviceModalContent');
    const icon = document.getElementById('serviceFullscreenIcon');
    
    isServiceFullscreen = !isServiceFullscreen;
    
    if (isServiceFullscreen) {
        modalContent.classList.add('modal-fullscreen');
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
    } else {
        modalContent.classList.remove('modal-fullscreen');
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
    }
}

function closeServiceModal() {
    document.getElementById('serviceModal').style.display = 'none';
    document.getElementById('serviceForm').reset();
    
    // Réinitialiser l'état du plein écran
    const modalContent = document.getElementById('serviceModalContent');
    const icon = document.getElementById('serviceFullscreenIcon');
    modalContent.classList.remove('modal-fullscreen');
    icon.classList.remove('fa-compress');
    icon.classList.add('fa-expand');
    isServiceFullscreen = false;
}

function createServiceElement(service) {
    const div = document.createElement('div');
    div.className = 'service-item';
    div.innerHTML = `
        <span class="service-name">${service.name}</span>
        <button class="btn-icon text-danger" onclick="deleteService(${service.id}, '${service.name}')" 
                data-tooltip="Supprimer">
            <i class="fas fa-trash"></i>
        </button>
    `;
    return div;
}

function deleteService(serviceId, serviceName) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: `Voulez-vous vraiment supprimer le service "${serviceName}" ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFCC00',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_service.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: serviceId, name: serviceName })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadServices().then(() => {
                        Swal.fire({
                            title: 'Succès',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#FFCC00'
                        });
                    });
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire('Erreur', 'Impossible de supprimer le service', 'error');
            });
        }
    });
} 