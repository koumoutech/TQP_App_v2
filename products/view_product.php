<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: user_products.php');
    exit();
}

// Enregistrer la vue du produit
$view_query = "INSERT INTO product_views (product_id, user_id, viewed_at) 
               VALUES (:product_id, :user_id, NOW())";
$view_stmt = $conn->prepare($view_query);
$view_stmt->execute([
    ':product_id' => $product_id,
    ':user_id' => $_SESSION['user_id']
]);

// Récupérer les détails du produit
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = :id";
$stmt = $conn->prepare($query);
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: user_products.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/user_menu.php'; ?>
    
    <div class="content">
        <div class="product-details">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="category">Catégorie : <?php echo htmlspecialchars($product['category_name']); ?></p>
            
            <?php if ($product['media_url']): ?>
                <div class="product-media">
                    <?php if ($product['media_type'] === 'image'): ?>
                        <img src="../<?php echo htmlspecialchars($product['media_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <video controls>
                            <source src="../<?php echo htmlspecialchars($product['media_url']); ?>" type="video/mp4">
                            Votre navigateur ne supporte pas la lecture de vidéos.
                        </video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="product-info">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <h3>Détails</h3>
                <div class="details">
                    <?php echo nl2br(htmlspecialchars($product['details'])); ?>
                </div>
            </div>
            
            <div class="product-actions">
                <a href="user_products.php" class="btn btn-secondary">Retour à la liste</a>
            </div>
        </div>
    </div>
</body>
</html> 