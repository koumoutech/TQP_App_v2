document.addEventListener('DOMContentLoaded', function() {
    const quizForm = document.getElementById('quizForm');
    const questions = document.querySelectorAll('.question-card');
    const prevButton = document.getElementById('prevQuestion');
    const nextButton = document.getElementById('nextQuestion');
    const submitDiv = document.querySelector('.quiz-submit');
    const dots = document.querySelectorAll('.dot');
    const progressFill = document.querySelector('.progress-fill');
    const currentQuestionNum = document.getElementById('currentQuestionNum');
    let currentQuestion = 1;
    let isSubmitting = false;

    // Timer
    const timerElement = document.getElementById('quizTimer');
    const duration = parseInt(timerElement.dataset.duration);
    let timeLeft = duration;
    
    // Initialisation
    showQuestion(currentQuestion);
    updateNavigation();
    startTimer();

    // Gestion du timer
    function startTimer() {
        const timer = setInterval(() => {
            if (isSubmitting) {
                clearInterval(timer);
                return;
            }

            timeLeft--;
            updateTimerDisplay();

            if (timeLeft <= 0) {
                clearInterval(timer);
                submitQuiz(true);
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.querySelector('span').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        // Alertes de temps
        if (timeLeft === 300) { // 5 minutes
            showTimeWarning('Plus que 5 minutes !');
        } else if (timeLeft === 60) { // 1 minute
            showTimeWarning('Dernière minute !');
        }
    }

    function showTimeWarning(message) {
        Swal.fire({
            title: 'Attention',
            text: message,
            icon: 'warning',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    // Navigation entre les questions
    function showQuestion(number) {
        questions.forEach(q => q.classList.remove('active'));
        document.querySelector(`[data-question="${number}"]`).classList.add('active');
        currentQuestionNum.textContent = number;
        updateProgress();
        updateDots();
    }

    function updateNavigation() {
        prevButton.disabled = currentQuestion === 1;
        nextButton.textContent = currentQuestion === questions.length ? 'Terminer' : 'Suivant';
        nextButton.innerHTML = currentQuestion === questions.length ? 
            '<i class="fas fa-check"></i> Terminer' : 
            'Suivant <i class="fas fa-arrow-right"></i>';
        
        submitDiv.style.display = currentQuestion === questions.length ? 'block' : 'none';
    }

    function updateProgress() {
        const progress = (currentQuestion / questions.length) * 100;
        progressFill.style.width = `${progress}%`;
    }

    function updateDots() {
        dots.forEach(dot => {
            const questionNum = parseInt(dot.dataset.question);
            dot.classList.remove('active');
            if (questionNum === currentQuestion) {
                dot.classList.add('active');
            }
            // Vérifier si la question est répondue
            const questionCard = document.querySelector(`.question-card[data-question="${questionNum}"]`);
            const answered = Array.from(questionCard.querySelectorAll('input[type="radio"]'))
                .some(input => input.checked);
            if (answered) {
                dot.classList.add('answered');
            }
        });
    }

    // Événements de navigation
    prevButton.addEventListener('click', () => {
        if (currentQuestion > 1) {
            currentQuestion--;
            showQuestion(currentQuestion);
            updateNavigation();
        }
    });

    nextButton.addEventListener('click', () => {
        if (currentQuestion < questions.length) {
            const currentInputs = document.querySelector(`.question-card[data-question="${currentQuestion}"]`)
                .querySelectorAll('input[type="radio"]');
            const answered = Array.from(currentInputs).some(input => input.checked);
            
            if (!answered) {
                Swal.fire({
                    title: 'Question non répondue',
                    text: 'Veuillez répondre à la question avant de continuer.',
                    icon: 'warning',
                    confirmButtonColor: '#FFCC30'
                });
                return;
            }
            
            currentQuestion++;
            showQuestion(currentQuestion);
            updateNavigation();
        }
    });

    // Navigation par points
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const questionNum = parseInt(dot.dataset.question);
            currentQuestion = questionNum;
            showQuestion(currentQuestion);
            updateNavigation();
        });
    });

    // Soumission du quiz
    quizForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitQuiz();
    });

    function submitQuiz(timeOut = false) {
        if (isSubmitting) return;

        // Vérifier que toutes les questions sont répondues
        const allAnswered = Array.from(questions).every(question => {
            const inputs = question.querySelectorAll('input[type="radio"]');
            return Array.from(inputs).some(input => input.checked);
        });

        if (!allAnswered && !timeOut) {
            Swal.fire({
                title: 'Quiz incomplet',
                text: 'Veuillez répondre à toutes les questions avant de soumettre.',
                icon: 'warning',
                confirmButtonColor: '#FFCC30'
            });
            return;
        }

        const confirmMessage = timeOut ? 
            'Le temps est écoulé ! Le quiz va être soumis automatiquement.' :
            'Êtes-vous sûr de vouloir terminer le quiz ?';

        Swal.fire({
            title: timeOut ? 'Temps écoulé !' : 'Confirmation',
            text: confirmMessage,
            icon: timeOut ? 'warning' : 'question',
            showCancelButton: !timeOut,
            confirmButtonColor: '#FFCC30',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, terminer',
            cancelButtonText: 'Non, vérifier'
        }).then((result) => {
            if (result.isConfirmed) {
                isSubmitting = true;
                const formData = new FormData(quizForm);
                formData.append('time_taken', duration - timeLeft);
                
                fetch('submit_quiz.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Quiz terminé !',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#FFCC30'
                        }).then(() => {
                            window.location.href = `view_result.php?id=${data.result_id}`;
                        });
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    isSubmitting = false;
                    Swal.fire({
                        title: 'Erreur',
                        text: error.message || 'Une erreur est survenue',
                        icon: 'error',
                        confirmButtonColor: '#FFCC30'
                    });
                });
            }
        });
    }
}); 