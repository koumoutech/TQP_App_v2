// Configuration de Dropzone
Dropzone.autoDiscover = false;
let myDropzone;

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Dropzone
    myDropzone = new Dropzone("#imageUpload", {
        url: "upload_slide.php",
        method: "post",
        paramName: "file",
        maxFilesize: 5, // MB
        acceptedFiles: "image/*",
        maxFiles: 1,
        addRemoveLinks: true,
        dictDefaultMessage: '<i class="fas fa-folder-open"></i><br>Attach you files here',
        dictRemoveFile: "Remove",
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        init: function() {
            this.on("sending", function(file, xhr, formData) {
                // Ajouter des données supplémentaires si nécessaire
                formData.append("csrf_token", document.querySelector('meta[name="csrf-token"]').content);
            });

            this.on("success", function(file, response) {
                try {
                    console.log('Upload response:', response);
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        // Ajouter l'URL de l'image au formulaire
                        let imageUrlInput = document.querySelector('input[name="image_url"]');
                        if (!imageUrlInput) {
                            imageUrlInput = document.createElement('input');
                            imageUrlInput.type = 'hidden';
                            imageUrlInput.name = 'image_url';
                            document.getElementById('slideForm').appendChild(imageUrlInput);
                        }
                        imageUrlInput.value = data.file;
                        
                        // Afficher un message de succès
                        Swal.fire({
                            title: 'Succès',
                            text: 'Image téléchargée avec succès',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Erreur lors de l\'upload');
                    }
                } catch (error) {
                    console.error('Error parsing response:', error);
                    this.removeFile(file);
                    Swal.fire('Erreur', error.message, 'error');
                }
            });

            this.on("error", function(file, errorMessage) {
                console.error('Upload error:', errorMessage);
                this.removeFile(file);
                Swal.fire('Erreur', typeof errorMessage === 'string' ? errorMessage : 'Erreur lors de l\'upload', 'error');
            });

            this.on("removedfile", function(file) {
                // Supprimer l'URL de l'image du formulaire
                const imageUrlInput = document.querySelector('input[name="image_url"]');
                if (imageUrlInput) {
                    imageUrlInput.remove();
                }
            });
        }
    });

    // Initialiser le tri des slides
    if (document.getElementById('slidesGrid')) {
        new Sortable(document.getElementById('slidesGrid'), {
            animation: 150,
            onEnd: function(evt) {
                updateSlidesOrder();
            }
        });
    }

    // Gérer le formulaire
    document.getElementById('slideForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!myDropzone.getAcceptedFiles().length && !document.querySelector('input[name="image_url"]')) {
            Swal.fire({
                title: 'Erreur',
                text: 'Veuillez ajouter une image',
                icon: 'error'
            });
            return;
        }
        
        saveSlide();
    });
});

// Fonctions de gestion des slides
function showAddSlideModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter une slide';
    document.getElementById('slideId').value = '';
    document.getElementById('slideForm').reset();
    if (myDropzone) {
        myDropzone.removeAllFiles(true);
    }
    document.getElementById('slideModal').style.display = 'flex';
}

function editSlide(slideId) {
    fetch(`get_slide.php?id=${slideId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Modifier la slide';
                document.getElementById('slideId').value = data.slide.id;
                document.getElementById('slideTitle').value = data.slide.title;
                document.getElementById('slideDescription').value = data.slide.description;
                document.getElementById('slideModal').style.display = 'flex';
            }
        });
}

function saveSlide() {
    const formData = new FormData(document.getElementById('slideForm'));
    
    fetch('save_slide.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Succès',
                text: data.message,
                icon: 'success'
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Erreur',
            text: error.message,
            icon: 'error'
        });
    });
}

function deleteSlide(slideId) {
    Swal.fire({
        title: 'Confirmation',
        text: 'Voulez-vous vraiment supprimer cette slide ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`delete_slide.php?id=${slideId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Erreur',
                        text: error.message,
                        icon: 'error'
                    });
                });
        }
    });
}

function updateSlidesOrder() {
    const slides = Array.from(document.querySelectorAll('.slide-card')).map((card, index) => ({
        id: card.dataset.id,
        position: index + 1
    }));

    fetch('update_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ slides })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Erreur',
            text: error.message,
            icon: 'error'
        });
    });
}

function closeModal() {
    document.getElementById('slideModal').style.display = 'none';
}