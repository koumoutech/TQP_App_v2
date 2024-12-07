// Ajouter la configuration personnalisée de SweetAlert2 au début du fichier
// const Toast = Swal.mixin({...}); // Supprimer cette partie

function editQuiz(quizId) {
    fetch(`get_quiz.php?id=${quizId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(quiz => {
            if (!quiz.success) {
                throw new Error(quiz.message || 'Erreur lors de la récupération des données');
            }

            document.getElementById('modalTitle').textContent = 'Modifier le quiz';
            document.getElementById('quizId').value = quiz.id;
            document.getElementById('title').value = quiz.title;
            document.getElementById('duration').value = quiz.duration;
            
            // Gérer les services
            const services = quiz.services ? quiz.services.split(',') : [];
            // Décocher toutes les cases d'abord
            document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            // Cocher les services du quiz
            services.forEach(service => {
                const checkbox = document.querySelector(`input[name="services[]"][value="${service.trim()}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            
            // Gérer les dates si elles existent
            if (quiz.start_date) {
                document.getElementById('start_date').value = quiz.start_date.slice(0, 16);
            }
            if (quiz.end_date) {
                document.getElementById('end_date').value = quiz.end_date.slice(0, 16);
            }
            
            document.getElementById('quizModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: error.message || 'Impossible de charger les données du quiz',
                icon: 'error',
                confirmButtonColor: '#FFCC00'
            });
        });
}

// Gestionnaire de formulaire pour la création/modification de quiz
document.addEventListener('DOMContentLoaded', function() {
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        quizForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier qu'au moins un service est sélectionné
            const selectedServices = document.querySelectorAll('input[name="services[]"]:checked');
            if (selectedServices.length === 0) {
                Swal.fire({
                    title: 'Erreur',
                    text: 'Veuillez sélectionner au moins un service',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('save_quiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message);
                } else {
                    throw new Error(data.message || 'Erreur lors de la sauvegarde du quiz');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: error.message || 'Une erreur est survenue',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
            });
        });
    }
});

// Ajouter ces fonctions
function showAddQuizModal() {
    document.getElementById('modalTitle').textContent = 'Créer un quiz';
    document.getElementById('quizForm').reset();
    document.getElementById('quizId').value = '';
    
    // Décocher tous les services
    document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    document.getElementById('quizModal').style.display = 'flex';
}

function manageQuestions(quizId) {
    document.getElementById('questionQuizId').value = quizId;
    loadQuestions(quizId);
    document.getElementById('questionsModal').style.display = 'flex';
}

function viewQuizStats(quizId) {
    window.location.href = `quiz_stats.php?id=${quizId}`;
}

function deleteQuiz(quizId) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action supprimera le quiz et toutes ses questions !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFCC00',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete_quiz.php?id=${quizId}`;
        }
    });
}

function loadQuestions(quizId) {
    fetch(`get_questions.php?quiz_id=${quizId}`)
        .then(response => response.json())
        .then(questions => {
            const questionsList = document.getElementById('questionsList');
            questionsList.innerHTML = '';
            
            if (questions.length === 0) {
                questionsList.innerHTML = '<p class="text-center text-muted">Aucune question ajoutée pour ce quiz</p>';
                return;
            }
            
            questions.forEach((question, index) => {
                const questionElement = createQuestionElement(question, index + 1);
                questionsList.appendChild(questionElement);
            });
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                title: 'Erreur',
                text: 'Impossible de charger les questions',
                icon: 'error',
                confirmButtonColor: '#FFCC00'
            });
        });
}

function createQuestionElement(question, number) {
    const div = document.createElement('div');
    div.className = 'question-item';
    div.innerHTML = `
        <div class="question-number">Question ${number}</div>
        <div class="question-text">${question.question}</div>
        <ul class="answer-list">
            ${question.answers.map((answer, index) => `
                <li class="answer-item ${answer.is_correct ? 'correct' : ''}">
                    <i class="fas ${answer.is_correct ? 'fa-check' : 'fa-times'}"></i>
                    ${answer.answer}
                </li>
            `).join('')}
        </ul>
        <div class="question-actions">
            <button class="btn-icon" onclick="editQuestion(${question.id})" data-tooltip="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon text-danger" onclick="deleteQuestion(${question.id})" data-tooltip="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    return div;
}

function closeModal() {
    document.getElementById('quizModal').style.display = 'none';
    document.getElementById('quizForm').reset();
}

function closeQuestionsModal() {
    const modalContent = document.getElementById('questionsModalContent');
    const icon = document.getElementById('fullscreenIcon');
    
    document.getElementById('questionsModal').style.display = 'none';
    document.getElementById('questionForm').reset();
    
    // Réinitialiser l'état du plein écran
    modalContent.classList.remove('modal-fullscreen');
    icon.classList.remove('fa-compress');
    icon.classList.add('fa-expand');
    isFullscreen = false;
}

function editQuestion(questionId) {
    // À implémenter : édition d'une question
    console.log('Édition de la question:', questionId);
}

function deleteQuestion(questionId) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action supprimera définitivement la question !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FFCC00',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_question.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ question_id: questionId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadQuestions(document.getElementById('questionQuizId').value);
                    Swal.fire({
                        title: 'Succès',
                        text: 'Question supprimée avec succès',
                        icon: 'success',
                        confirmButtonColor: '#FFCC00'
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: 'Impossible de supprimer la question',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
            });
        }
    });
}

// Gestionnaire pour le formulaire d'ajout de question
document.addEventListener('DOMContentLoaded', function() {
    const questionForm = document.getElementById('questionForm');
    if (questionForm) {
        questionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier qu'une réponse correcte est sélectionnée
            const correctAnswer = document.querySelector('input[name="correct_answer"]:checked');
            if (!correctAnswer) {
                Swal.fire({
                    title: 'Erreur',
                    text: 'Veuillez sélectionner une réponse correcte',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('save_question.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    loadQuestions(document.getElementById('questionQuizId').value);
                    showSuccessMessage('Question ajoutée avec succès');
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: error.message || 'Impossible d\'ajouter la question',
                    icon: 'error',
                    confirmButtonColor: '#FFCC00'
                });
            });
        });
    }
});

// Ajouter la fonction de plein écran pour les questions
let isFullscreen = false;

function toggleFullscreen() {
    const modalContent = document.getElementById('questionsModalContent');
    const icon = document.getElementById('fullscreenIcon');
    
    isFullscreen = !isFullscreen;
    
    if (isFullscreen) {
        modalContent.classList.add('modal-fullscreen');
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
    } else {
        modalContent.classList.remove('modal-fullscreen');
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
    }
}

// Remplacer Toast par une configuration personnalisée de Swal
function showSuccessMessage(message) {
    Swal.fire({
        title: 'Succès !',
        text: message,
        icon: 'success',
        confirmButtonText: 'OK',
        confirmButtonColor: '#FFCC00',
        customClass: {
            popup: 'small-popup'
        }
    }).then(() => {
        window.location.reload();
    });
}

// Ajouter le style CSS pour le petit popup
const style = document.createElement('style');
style.textContent = `
.small-popup {
    width: 300px !important;
    font-size: 1rem !important;
}
.swal2-popup {
    padding: 1rem !important;
}
.swal2-title {
    font-size: 1.2rem !important;
    margin-bottom: 0.5rem !important;
}
.swal2-html-container {
    font-size: 1rem !important;
    margin: 0.5rem 0 !important;
}
`;
document.head.appendChild(style);
 