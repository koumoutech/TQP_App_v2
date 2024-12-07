<?php
// Récupérer la liste des services pour l'affichage initial
$services_query = "SELECT id, name FROM services ORDER BY name";
$services_stmt = $conn->query($services_query);
$services_list = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="serviceModal" class="modal" style="display: none;">
    <div class="modal-content modal-lg" id="serviceModalContent">
        <div class="modal-header">
            <div class="modal-title">
                <h3 id="serviceModalTitle">Gérer les services</h3>
            </div>
            <div class="modal-header-actions">
                <button type="button" class="btn-icon" onclick="toggleServiceFullscreen()" data-tooltip="Plein écran">
                    <i class="fas fa-expand" id="serviceFullscreenIcon"></i>
                </button>
                <button type="button" class="close-modal" data-action="close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="services-container">
            <!-- Liste des services existants -->
            <div class="existing-services">
                <h4>Services existants</h4>
                <div id="servicesList" class="services-list">
                    <?php foreach ($services_list as $service): ?>
                    <div class="service-item">
                        <span class="service-name"><?php echo htmlspecialchars($service['name']); ?></span>
                        <button class="btn-icon text-danger" 
                                data-action="delete"
                                data-service-id="<?php echo $service['id']; ?>"
                                data-service-name="<?php echo htmlspecialchars($service['name']); ?>"
                                data-tooltip="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Formulaire d'ajout de service -->
            <div class="add-service-section">
                <h4>Ajouter un service</h4>
                <form id="serviceForm" class="add-service-form">
                    <div class="form-group">
                        <label for="serviceName">Nom du service</label>
                        <div class="input-group">
                            <input type="text" id="serviceName" name="name" class="form-control" 
                                   placeholder="Entrez le nom du service" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.services-container {
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.existing-services {
    background: var(--bg-light);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.services-list {
    margin-top: 1rem;
    max-height: 300px;
    overflow-y: auto;
}

.service-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--bg-color);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}

.service-item:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow);
}

.service-name {
    font-weight: 500;
    color: var(--text-color);
}

.add-service-section {
    background: var(--bg-light);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.input-group .form-control {
    flex: 1;
}

.btn-icon {
    padding: 0.5rem;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--text-light);
    border-radius: 0.375rem;
    transition: all 0.2s;
}

.btn-icon:hover {
    background-color: var(--bg-light);
}

.btn-icon.text-danger:hover {
    background-color: #dc354522;
    color: #dc3545;
}

h4 {
    color: var(--text-color);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

@media (min-width: 768px) {
    .services-container {
        grid-template-columns: 2fr 1fr;
    }
}

.modal-lg {
    max-width: 900px;
    width: 90%;
    transition: all 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
}

.modal-header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-fullscreen {
    width: 100% !important;
    max-width: 100% !important;
    height: 100vh !important;
    margin: 0;
    border-radius: 0;
}

.modal-fullscreen .services-container {
    height: calc(100vh - 70px);
    max-height: none;
}

.modal-fullscreen .existing-services {
    max-height: calc(100vh - 100px);
}
</style> 