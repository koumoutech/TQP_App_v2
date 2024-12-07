<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter un produit</h3>
            <button type="button" class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="productForm" method="POST" action="save_product.php" enctype="multipart/form-data">
            <input type="hidden" name="product_id" id="productId">
            
            <div class="form-group">
                <label for="name">
                    <i class="fas fa-box"></i>
                    Nom du produit
                </label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="category_id">
                    <i class="fas fa-tag"></i>
                    Catégorie
                </label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">Sélectionner une catégorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i>
                    Description
                </label>
                <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="details">
                    <i class="fas fa-list"></i>
                    Détails
                </label>
                <textarea id="details" name="details" class="form-control" rows="5"></textarea>
            </div>
            
            <div class="form-group">
                <label for="media">
                    <i class="fas fa-file-upload"></i>
                    Média (Image/Vidéo)
                </label>
                <div class="media-upload-container">
                    <input type="file" id="media" name="media" class="form-control" 
                           accept="image/*,video/*">
                    <div id="mediaPreview" class="media-preview"></div>
                </div>
                <small class="form-text">Formats acceptés : JPG, PNG, MP4. Taille max : 50MB</small>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div> 