<?php
session_start();
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Produits les plus consultés
$popular_products_query = "SELECT 
    p.name,
    c.name as category_name,
    COUNT(pv.id) as view_count,
    COUNT(DISTINCT pv.user_id) as unique_viewers
    FROM products p
    LEFT JOIN product_views pv ON p.id = pv.product_id
    LEFT JOIN categories c ON p.category_id = c.id
    GROUP BY p.id
    ORDER BY view_count DESC
    LIMIT 10";
$popular_products = $conn->query($popular_products_query)->fetchAll(PDO::FETCH_ASSOC);

// Statistiques par catégorie
$category_stats_query = "SELECT 
    c.name as category_name,
    COUNT(DISTINCT p.id) as product_count,
    COUNT(pv.id) as total_views
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    LEFT JOIN product_views pv ON p.id = pv.product_id
    GROUP BY c.id
    ORDER BY total_views DESC";
$category_stats = $conn->query($category_stats_query)->fetchAll(PDO::FETCH_ASSOC);

// Statistiques par utilisateur
$user_stats_query = "SELECT 
    u.username,
    u.service,
    COUNT(pv.id) as view_count,
    COUNT(DISTINCT pv.product_id) as unique_products
    FROM users u
    LEFT JOIN product_views pv ON u.id = pv.user_id
    GROUP BY u.id
    ORDER BY view_count DESC";
$user_stats = $conn->query($user_stats_query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statistiques des Produits</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Statistiques des Produits</h2>
        
        <div class="stats-section">
            <h3>Produits les plus consultés</h3>
            <canvas id="productsChart"></canvas>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Vues totales</th>
                        <th>Visiteurs uniques</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popular_products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td><?php echo $product['view_count']; ?></td>
                        <td><?php echo $product['unique_viewers']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="stats-section">
            <h3>Statistiques par catégorie</h3>
            <canvas id="categoryChart"></canvas>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Nombre de produits</th>
                        <th>Vues totales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($category_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['category_name']); ?></td>
                        <td><?php echo $stat['product_count']; ?></td>
                        <td><?php echo $stat['total_views']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="stats-section">
            <h3>Consultations par utilisateur</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Service</th>
                        <th>Produits consultés</th>
                        <th>Produits uniques</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['username']); ?></td>
                        <td><?php echo htmlspecialchars($stat['service']); ?></td>
                        <td><?php echo $stat['view_count']; ?></td>
                        <td><?php echo $stat['unique_products']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Graphique des produits populaires
    new Chart(document.getElementById('productsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($popular_products, 'name')); ?>,
            datasets: [{
                label: 'Nombre de vues',
                data: <?php echo json_encode(array_column($popular_products, 'view_count')); ?>,
                backgroundColor: '#FFCC30'
            }]
        }
    });

    // Graphique des catégories
    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($category_stats, 'category_name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($category_stats, 'total_views')); ?>,
                backgroundColor: ['#FFCC30', '#FFD966', '#FFE699', '#FFF2CC', '#FFF9E6']
            }]
        }
    });
    </script>
</body>
</html> 