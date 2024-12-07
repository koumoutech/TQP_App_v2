document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le graphique
    const serviceDataElement = document.getElementById('serviceData');
    if (serviceDataElement) {
        const serviceData = JSON.parse(serviceDataElement.dataset.stats);
        const ctx = document.getElementById("serviceChart").getContext("2d");
        if (ctx) {
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: serviceData.map(item => item.service),
                    datasets: [{
                        label: "Score moyen (%)",
                        data: serviceData.map(item => item.avg_score),
                        backgroundColor: "#FFCC00",
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    }

    // Gestionnaire pour le bouton d'export
    const exportButton = document.getElementById('exportButton');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            const quizId = this.dataset.quizId;
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "export_quiz_stats.php";
            
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "quiz_id";
            input.value = quizId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }

    // Gestionnaire pour le bouton retour
    const backButton = document.getElementById('backButton');
    if (backButton) {
        backButton.addEventListener('click', function() {
            window.location.href = 'list_quiz.php';
        });
    }
}); 