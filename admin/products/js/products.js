// Fonctions pour gérer les modales de produits
function showAddProductModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter un produit';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('mediaPreview').innerHTML = '';
    document.getElementById('productModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('productModal').style.display = 'none';
}

function editProduct(productId) {
    fetch(`get_product.php?id=${productId}`)
        .then(response => response.json())
        .then(product => {
            if (product.success === false) {
                Swal.fire('Erreur', product.message, 'error');
                return;
            }
            document.getElementById('modalTitle').textContent = 'Modifier le produit';
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('description').value = product.description;
            document.getElementById('details').value = product.details || '';
            
            // Afficher le média existant
            const mediaPreview = document.getElementById('mediaPreview');
            mediaPreview.innerHTML = '';
            if (product.media_url) {
                if (product.media_type === 'video') {
                    mediaPreview.innerHTML = `
                        <video src="${product.media_url}" controls class="preview-media"></video>
                    `;
                } else {
                    mediaPreview.innerHTML = `
                        <img src="${product.media_url}" alt="${product.name}" class="preview-media">
                    `;
                }
            }
            
            document.getElementById('productModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Une erreur est survenue lors de la récupération des données', 'error');
        });
}

function viewDetails(productId) {
    window.location.href = `view_product.php?id=${productId}`;
}

function deleteProduct(productId) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est irréversible !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFCC00',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete_product.php?id=${productId}`;
        }
    });
}

function exportProducts() {
    // Récupérer les filtres actuels
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('category') || '';
    const search = urlParams.get('search') || '';
    
    // Construire l'URL d'export avec les filtres
    const exportUrl = `export_products.php?category=${encodeURIComponent(category)}&search=${encodeURIComponent(search)}`;
    window.location.href = exportUrl;
}

// Gestionnaire d'événements pour le formulaire
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('save_product.php', {
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

// Prévisualisation du média
document.getElementById('media').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('mediaPreview');
    preview.innerHTML = '';
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Aperçu" class="preview-media">
                `;
            } else if (file.type.startsWith('video/')) {
                preview.innerHTML = `
                    <video src="${e.target.result}" controls class="preview-media"></video>
                `;
            }
        }
        reader.readAsDataURL(file);
    }
}); 