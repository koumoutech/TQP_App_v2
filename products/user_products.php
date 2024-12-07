<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Requête de base
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE (p.name LIKE :search OR p.description LIKE :search)";
if ($category) {
    $query .= " AND p.category_id = :category";
}
$query .= " ORDER BY p.created_at DESC LIMIT :offset, :limit";

$stmt = $conn->prepare($query);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
if ($category) {
    $stmt->bindValue(':category', $category, PDO::PARAM_INT);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les catégories pour le filtre
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Produits & Services</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/user_menu.php'; ?>
    
    <div class="content">
        <h2>Produits & Services</h2>
        
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Rechercher..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"
                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                
                <?php if ($product['media_url']): ?>
                    <?php if ($product['media_type'] === 'image'): ?>
                        <img src="../<?php echo htmlspecialchars($product['media_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <video controls>
                            <source src="../<?php echo htmlspecialchars($product['media_url']); ?>" type="video/mp4">
                            Votre navigateur ne supporte pas la lecture de vidéos.
                        </video>
                    <?php endif; ?>
                <?php endif; ?>
                
                <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                <a href="view_product.php?id=<?php echo $product['id']; ?>" 
                   class="btn btn-primary">Voir les détails</a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php
        // Pagination
        $total_query = "SELECT COUNT(*) FROM products";
        $total_products = $conn->query($total_query)->fetchColumn();
        $total_pages = ceil($total_products / $records_per_page);
        ?>
        
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                   class="btn <?php echo $page == $i ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html> 