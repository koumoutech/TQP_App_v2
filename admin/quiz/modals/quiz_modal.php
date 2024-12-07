<div id="quizModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Créer un quiz</h3>
            <button type="button" class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="quizForm" method="POST" action="save_quiz.php">
            <input type="hidden" name="quiz_id" id="quizId">
            
            <div class="form-group">
                <label for="title">
                    <i class="fas fa-heading"></i>
                    Titre du quiz
                </label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="services">
                    <i class="fas fa-building"></i>
                    Services concernés
                </label>
                <div class="services-select">
                    <?php
                    // Récupérer tous les services
                    $services_query = "SELECT name FROM services ORDER BY name";
                    $services_stmt = $conn->query($services_query);
                    while ($service = $services_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="service-checkbox">';
                        echo '<input type="checkbox" name="services[]" id="service_' . htmlspecialchars($service['name']) . '" 
                                     value="' . htmlspecialchars($service['name']) . '">';
                        echo '<label for="service_' . htmlspecialchars($service['name']) . '">' . 
                             htmlspecialchars($service['name']) . '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="duration">
                    <i class="fas fa-clock"></i>
                    Durée (minutes)
                </label>
                <input type="number" id="duration" name="duration" class="form-control" 
                       min="1" max="120" required>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="start_date">
                        <i class="fas fa-calendar-plus"></i>
                        Date de début
                    </label>
                    <input type="datetime-local" id="start_date" name="start_date" class="form-control">
                    <small class="form-text text-muted">Optionnel - Début de la disponibilité</small>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="end_date">
                        <i class="fas fa-calendar-minus"></i>
                        Date de fin
                    </label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control">
                    <small class="form-text text-muted">Optionnel - Fin de la disponibilité</small>
                </div>
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

<!-- Modale pour gérer les questions -->
<div id="questionsModal" class="modal" style="display: none;">
    <div class="modal-content modal-lg" id="questionsModalContent">
        <div class="modal-header">
            <div class="modal-title">
                <h3 id="questionsModalTitle">Questions du Quiz</h3>
            </div>
            <div class="modal-header-actions">
                <button type="button" class="btn-icon" onclick="toggleFullscreen()" data-tooltip="Plein écran">
                    <i class="fas fa-expand" id="fullscreenIcon"></i>
                </button>
                <button type="button" class="close-modal" onclick="closeQuestionsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="questions-container">
            <!-- Liste des questions existantes -->
            <div class="existing-questions">
                <h4>Questions existantes</h4>
                <div id="questionsList" class="questions-list">
                    <!-- Les questions seront chargées dynamiquement ici -->
                </div>
            </div>
            
            <!-- Formulaire d'ajout de question -->
            <div class="add-question-form">
                <h4>Ajouter une question</h4>
                <form id="questionForm">
                    <input type="hidden" name="quiz_id" id="questionQuizId">
                    
                    <div class="form-group">
                        <label for="questionText">Question</label>
                        <textarea id="questionText" name="question" class="form-control" 
                                rows="2" required></textarea>
                    </div>
                    
                    <div class="answers-container">
                        <?php for($i = 0; $i < 4; $i++): ?>
                        <div class="answer-group">
                            <div class="answer-input">
                                <input type="text" name="answers[]" class="form-control" 
                                       placeholder="Réponse <?php echo $i + 1; ?>" required>
                                <div class="answer-radio">
                                    <input type="radio" name="correct_answer" value="<?php echo $i; ?>" required>
                                    <label>Correcte</label>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Ajouter la question
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
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
    margin: 0 !important;
    border-radius: 0 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
}

.modal-fullscreen .questions-container {
    height: calc(100vh - 70px);
    max-height: none;
    overflow: auto;
}

.modal-fullscreen .existing-questions {
    max-height: calc(100vh - 100px);
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
    color: var(--primary-color);
}

.questions-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    padding: 1.5rem;
}

.existing-questions {
    border-right: 1px solid var(--border-color);
    padding-right: 1.5rem;
    max-height: 70vh;
    overflow-y: auto;
}

.questions-list {
    margin-top: 1rem;
}

.question-item {
    background: var(--bg-light);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.question-text {
    font-weight: 500;
    margin-bottom: 0.75rem;
    color: var(--text-color);
}

.answer-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.answer-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.25rem;
    margin-bottom: 0.25rem;
    background: var(--bg-color);
}

.answer-item.correct {
    background-color: rgba(0, 166, 81, 0.1);
    color: var(--success-color);
}

.answer-item i {
    width: 1.5rem;
}

.question-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}

.answer-group {
    margin-bottom: 1rem;
}

.answer-input {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.answer-radio {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
}

.answer-radio input[type="radio"] {
    width: 1.25rem;
    height: 1.25rem;
}

.question-number {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .questions-container {
        grid-template-columns: 1fr;
    }
    
    .existing-questions {
        border-right: none;
        border-bottom: 1px solid var(--border-color);
        padding-right: 0;
        padding-bottom: 1.5rem;
    }
}

.form-row {
    display: flex;
    margin-right: -0.75rem;
    margin-left: -0.75rem;
    gap: 1rem;
}

.form-row > .form-group {
    flex: 1;
    padding-right: 0.75rem;
    padding-left: 0.75rem;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    
    .form-row > .form-group {
        padding-right: 0;
        padding-left: 0;
    }
}

.services-select {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.5rem;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    max-height: 200px;
    overflow-y: auto;
}

.service-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: var(--bg-light);
    border-radius: 0.25rem;
}

.service-checkbox input[type="checkbox"] {
    width: 1.2rem;
    height: 1.2rem;
}

.service-checkbox label {
    margin: 0;
    cursor: pointer;
}
</style> 