<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: list_products.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les détails du produit
$query = "SELECT p.*, c.name as category_name,
          GROUP_CONCAT(pf.feature SEPARATOR '||') as features
          FROM products p 
          LEFT JOIN product_categories c ON p.category_id = c.id
          LEFT JOIN product_features pf ON p.id = pf.product_id
          WHERE p.id = :id AND p.status = 'active'
          GROUP BY p.id";

$stmt = $conn->prepare($query);
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: list_products.php');
    exit();
}

$features = $product['features'] ? explode('||', $product['features']) : [];

$page_title = $product['name'];
$base_url = "../..";

ob_start();
?>

<div class="content-wrapper">
    <!-- En-tête -->
    <div class="page-header">
        <div class="header-nav">
            <a href="list_products.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i>
                Retour aux produits
            </a>
        </div>
    </div>

    <!-- Détails du produit -->
    <div class="product-details">
        <div class="product-media">
            <div class="product-image">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <?php if ($product['video_url']): ?>
            <div class="product-video">
                <video controls>
                    <source src="<?php echo htmlspecialchars($product['video_url']); ?>" type="video/mp4">
                    Votre navigateur ne supporte pas la lecture de vidéos.
                </video>
            </div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <div class="product-header">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-category">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </div>
            </div>

            <div class="product-description">
                <h2>Description</h2>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <?php if (!empty($features)): ?>
            <div class="product-features">
                <h2>Caractéristiques</h2>
                <ul>
                    <?php foreach ($features as $feature): ?>
                    <li>
                        <i class="fas fa-check"></i>
                        <?php echo htmlspecialchars($feature); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="product-actions">
                <button class="btn btn-share" onclick="shareProduct(<?php echo $product['id']; ?>)">
                    <i class="fas fa-share-alt"></i>
                    Partager
                </button>
                <?php if ($product['external_link']): ?>
                <a href="<?php echo htmlspecialchars($product['external_link']); ?>" 
                   target="_blank" 
                   class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i>
                    En savoir plus
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.btn-back:hover {
    transform: translateX(-5px);
}

.product-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.product-media {
    position: sticky;
    top: 2rem;
}

.product-image {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.product-image img {
    width: 100%;
    height: auto;
    display: block;
}

.product-video {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-video video {
    width: 100%;
    display: block;
}

.product-info {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-header h1 {
    margin: 0;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.product-category {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #FFCC30;
    color: black;
    border-radius: 50px;
    font-size: 0.875rem;
    margin-bottom: 2rem;
}

.product-description,
.product-features {
    margin-bottom: 2rem;
}

.product-description h2,
.product-features h2 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.product-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.product-features li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    color: #666;
}

.product-features i {
    color: #28c76f;
}

.product-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

@media (max-width: 1024px) {
    .product-details {
        grid-template-columns: 1fr;
    }

    .product-media {
        position: static;
    }
}

@media (max-width: 768px) {
    .product-actions {
        flex-direction: column;
    }

    .product-actions .btn {
        width: 100%;
    }
}
</style>

<script>
function shareProduct(id) {
    // Implémenter le partage
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($product['name']); ?>',
            text: '<?php echo addslashes($product['description']); ?>',
            url: window.location.href
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?> 