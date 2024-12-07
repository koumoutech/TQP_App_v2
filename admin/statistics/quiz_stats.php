<?php
session_start();
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Statistiques par service
$service_stats_query = "SELECT 
    u.service,
    COUNT(DISTINCT u.id) as total_users,
    COUNT(DISTINCT qr.id) as total_attempts,
    AVG(qr.score) as avg_score
    FROM users u
    LEFT JOIN quiz_results qr ON u.id = qr.user_id
    GROUP BY u.service";
$service_stats = $conn->query($service_stats_query)->fetchAll(PDO::FETCH_ASSOC);

// Statistiques par utilisateur
$user_stats_query = "SELECT 
    u.username,
    u.service,
    COUNT(qr.id) as attempts,
    AVG(qr.score) as avg_score,
    MAX(qr.score) as best_score
    FROM users u
    LEFT JOIN quiz_results qr ON u.id = qr.user_id
    GROUP BY u.id
    ORDER BY u.service, u.username";
$user_stats = $conn->query($user_stats_query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statistiques des Quiz</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Statistiques des Quiz</h2>
        
        <div class="stats-section">
            <h3>Par Service</h3>
            <canvas id="serviceChart"></canvas>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Utilisateurs</th>
                        <th>Tentatives</th>
                        <th>Score moyen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($service_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['service']); ?></td>
                        <td><?php echo $stat['total_users']; ?></td>
                        <td><?php echo $stat['total_attempts']; ?></td>
                        <td><?php echo number_format($stat['avg_score'], 2); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="stats-section">
            <h3>Par Utilisateur</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Service</th>
                        <th>Tentatives</th>
                        <th>Score moyen</th>
                        <th>Meilleur score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['username']); ?></td>
                        <td><?php echo htmlspecialchars($stat['service']); ?></td>
                        <td><?php echo $stat['attempts']; ?></td>
                        <td><?php echo number_format($stat['avg_score'], 2); ?>%</td>
                        <td><?php echo number_format($stat['best_score'], 2); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Graphique des statistiques par service
    const ctx = document.getElementById('serviceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($service_stats, 'service')); ?>,
            datasets: [{
                label: 'Score moyen (%)',
                data: <?php echo json_encode(array_column($service_stats, 'avg_score')); ?>,
                backgroundColor: '#FFCC30'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    </script>
</body>
</html> 