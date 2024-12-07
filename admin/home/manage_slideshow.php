<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les slides existants
$slides = $conn->query("
    SELECT * FROM slideshow 
    ORDER BY position ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestion du Slideshow";
$base_url = "../..";

// Ajouter les styles du dropzone pour l'upload d'images
$extra_css = '
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
';

ob_start();
?>

<div class="content-wrapper">
    <div class="page-header">
        <h2>Gestion du Slideshow</h2>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showAddSlideModal()">
                <i class="fas fa-plus"></i> Ajouter une slide
            </button>
        </div>
    </div>

    <!-- Liste des slides -->
    <div class="slides-container">
        <?php if (empty($slides)): ?>
        <div class="empty-state">
            <i class="fas fa-images"></i>
            <h3>Aucune slide</h3>
            <p>Commencez par ajouter des images au slideshow</p>
        </div>
        <?php else: ?>
        <div class="slides-grid" id="slidesGrid">
            <?php foreach ($slides as $slide): ?>
            <div class="slide-card" data-id="<?php echo $slide['id']; ?>">
                <div class="slide-image">
                    <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($slide['title']); ?>">
                </div>
                <div class="slide-info">
                    <h3><?php echo htmlspecialchars($slide['title']); ?></h3>
                    <p><?php echo htmlspecialchars($slide['description']); ?></p>
                </div>
                <div class="slide-actions">
                    <button class="btn btn-icon" onclick="editSlide(<?php echo $slide['id']; ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-icon" onclick="deleteSlide(<?php echo $slide['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                    <div class="slide-position">
                        <button class="btn btn-icon" onclick="moveSlide(<?php echo $slide['id']; ?>, 'up')">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button class="btn btn-icon" onclick="moveSlide(<?php echo $slide['id']; ?>, 'down')">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal d'ajout/édition de slide -->
<div class="modal" id="slideModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter une slide</h3>
            <button class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="slideForm" class="form">
                <input type="hidden" name="slide_id" id="slideId">
                
                <div class="form-group">
                    <label for="slideTitle">Fullname</label>
                    <input type="text" 
                           id="slideTitle" 
                           name="title" 
                           class="form-control" 
                           required
                           placeholder="Enter full name">
                </div>

                <div class="form-group">
                    <label for="slideDescription">Description</label>
                    <textarea id="slideDescription" 
                            name="description" 
                            class="form-control" 
                            rows="4"
                            placeholder="Enter description"></textarea>
                    <small class="form-text text-muted">You will be able to edit this information later</small>
                </div>

                <div class="form-group">
                    <label>Attachments</label>
                    <div class="attachment-zone">
                        <div id="imageUpload" class="dropzone-container">
                            <!-- La zone de dropzone sera initialisée ici -->
                        </div>
                        <div class="upload-preview"></div>
                    </div>
                    <small class="form-text text-muted">Supported formats: JPG, PNG, GIF (max 5MB)</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.slides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.slide-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.slide-image {
    height: 200px;
    overflow: hidden;
}

.slide-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.slide-info {
    padding: 1.5rem;
}

.slide-actions {
    display: flex;
    justify-content: space-between;
    padding: 1rem;
    border-top: 1px solid #eee;
}

.slide-position {
    display: flex;
    gap: 0.5rem;
}

.dropzone {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    margin-bottom: 1rem;
   transition: all 0.3s ease;
}

.dropzone:hover,
.dropzone.dz-drag-hover {
    border-color: #FFCC30;
    background: #fff;
}

.dropzone .dz-message {
    margin: 0;
}

.dropzone .dz-preview {
    margin: 1rem;
}

.dropzone .dz-preview .dz-image {
    border-radius: 8px;
}

.dropzone .dz-preview .dz-error-message {
    color: #dc3545;
}

.image-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 4px;
}

/* Styles du nouveau formulaire */
.form-group{
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #4285f4;
    box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
    outline: none;
}

.form-text {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

.attachment-zone {
    border: 1px dashed #ddd;
    border-radius: 8px;
    padding: 1rem;
   background: #fff;
}

.dropzone-container {
    min-height: 150px !important;
    border: none !important;
    background: transparent !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dropzone .dz-message {
    margin: 0 !important;
    text-align: center;
}

.dropzone .dz-message i {
    font-size: 2.5rem;
    color: #4285f4;
    margin-bottom: 0.5rem;
}

.dropzone .dz-preview {
    margin: 1rem !important;
}

.dropzone .dz-preview .dz-image {
    border-radius: 8px !important;
}

.dropzone .dz-preview .dz-remove {
    margin-top: 0.5rem;
    color: #dc3545;
    text-decoration: none;
}

.dropzone .dz-preview .dz-remove:hover {
    text-decoration: underline;
}

.upload-preview {
    margin-top: 1rem;
    text-align: center;
}

.upload-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #4285f4;
    color: white;
}

.btn-secondary {
    background: #f8f9fa;
    color: #333;
}

.btn-primary:hover {
    background: #3367d6;
}

.btn-secondary:hover {
    background: #e9ecef;
}

.modal-content {
    max-width: 600px;
    width: 100%;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1.5rem;
}

@media (max-width: 768px) {
    .modal-content {
        margin: 1rem;
        width: calc(100% - 2rem);
    }
}
</style>

<?php
$content = ob_get_clean();

// JavaScript pour la gestion du slideshow
$extra_js = '
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="js/manage_slideshow.js"></script>';

include '../../includes/layout.php';
?> 