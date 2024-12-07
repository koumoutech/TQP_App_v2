<?php
session_start();
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Statistiques de connexion par service
$service_stats_query = "SELECT 
    u.service,
    COUNT(DISTINCT u.id) as total_users,
    COUNT(l.id) as total_logins,
    MAX(l.login_time) as last_login
    FROM users u
    LEFT JOIN login_history l ON u.id = l.user_id
    GROUP BY u.service";
$service_stats = $conn->query($service_stats_query)->fetchAll(PDO::FETCH_ASSOC);

// Statistiques par utilisateur
$user_stats_query = "SELECT 
    u.username,
    u.service,
    COUNT(l.id) as login_count,
    MAX(l.login_time) as last_login
    FROM users u
    LEFT JOIN login_history l ON u.id = l.user_id
    GROUP BY u.id
    ORDER BY u.service, login_count DESC";
$user_stats = $conn->query($user_stats_query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statistiques de Connexion</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Statistiques de Connexion</h2>
        
        <div class="stats-section">
            <h3>Par Service</h3>
            <canvas id="loginChart"></canvas>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Utilisateurs</th>
                        <th>Total Connexions</th>
                        <th>Dernière Connexion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($service_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['service']); ?></td>
                        <td><?php echo $stat['total_users']; ?></td>
                        <td><?php echo $stat['total_logins']; ?></td>
                        <td><?php echo $stat['last_login'] ? date('d/m/Y H:i', strtotime($stat['last_login'])) : '-'; ?></td>
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
                        <th>Nombre de Connexions</th>
                        <th>Dernière Connexion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['username']); ?></td>
                        <td><?php echo htmlspecialchars($stat['service']); ?></td>
                        <td><?php echo $stat['login_count']; ?></td>
                        <td><?php echo $stat['last_login'] ? date('d/m/Y H:i', strtotime($stat['last_login'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('loginChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($service_stats, 'service')); ?>,
            datasets: [{
                label: 'Nombre de connexions',
                data: <?php echo json_encode(array_column($service_stats, 'total_logins')); ?>,
                backgroundColor: '#FFCC30'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html> 