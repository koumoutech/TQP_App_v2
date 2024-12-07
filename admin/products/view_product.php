<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = :id";
$stmt = $conn->prepare($query);
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: list_products.php');
    exit();
}

// Statistiques de vues
$views_query = "SELECT COUNT(*) as total_views,
                COUNT(DISTINCT user_id) as unique_viewers,
                DATE(MAX(viewed_at)) as last_viewed
                FROM product_views 
                WHERE product_id = :product_id";
$stmt = $conn->prepare($views_query);
$stmt->execute([':product_id' => $product_id]);
$views_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Dernières vues
$recent_views_query = "SELECT v.*, u.username, u.service
                      FROM product_views v
                      JOIN users u ON v.user_id = u.id
                      WHERE v.product_id = :product_id
                      ORDER BY v.viewed_at DESC
                      LIMIT 10";
$stmt = $conn->prepare($recent_views_query);
$stmt->execute([':product_id' => $product_id]);
$recent_views = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Détails du produit";
$base_url = "../..";

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="product-header">
        <div class="product-title">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <span class="badge badge-category">
                <?php echo htmlspecialchars($product['category_name']); ?>
            </span>
        </div>
        <div class="product-actions">
            <button class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
            <button class="btn btn-primary" onclick="editProduct(<?php echo $product['id']; ?>)">
                <i class="fas fa-edit"></i> Modifier
            </button>
        </div>
    </div>

    <div class="product-content">
        <div class="product-info-card">
            <?php if ($product['media_url']): ?>
                <div class="product-media">
                    <?php if ($product['media_type'] === 'video'): ?>
                        <video src="../../<?php echo htmlspecialchars($product['media_url']); ?>" 
                               controls class="product-video"></video>
                    <?php else: ?>
                        <img src="../../<?php echo htmlspecialchars($product['media_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="info-section">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <?php if ($product['details']): ?>
            <div class="info-section">
                <h3>Détails</h3>
                <div class="details-content">
                    <?php echo nl2br(htmlspecialchars($product['details'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="info-section">
                <h3>Statistiques de consultation</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <i class="fas fa-eye"></i>
                        <span class="stat-value"><?php echo $views_stats['total_views']; ?></span>
                        <span class="stat-label">Vues totales</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span class="stat-value"><?php echo $views_stats['unique_viewers']; ?></span>
                        <span class="stat-label">Visiteurs uniques</span>
                    </div>
                    <?php if ($views_stats['last_viewed']): ?>
                    <div class="stat-item">
                        <i class="fas fa-clock"></i>
                        <span class="stat-value">
                            <?php echo date('d/m/Y', strtotime($views_stats['last_viewed'])); ?>
                        </span>
                        <span class="stat-label">Dernière consultation</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="recent-views-card">
            <h3>Dernières consultations</h3>
            <?php if ($recent_views): ?>
                <div class="views-list">
                    <?php foreach ($recent_views as $view): ?>
                    <div class="view-item">
                        <div class="viewer-info">
                            <i class="fas fa-user"></i>
                            <div>
                                <strong><?php echo htmlspecialchars($view['username']); ?></strong>
                                <span class="service-badge">
                                    <?php echo htmlspecialchars($view['service']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="view-time">
                            <?php echo date('d/m/Y H:i', strtotime($view['viewed_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-views">Aucune consultation pour le moment</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Inclure la modale de produit pour l'édition
include 'modals/product_modal.php';

// JavaScript supplémentaire
$extra_js = '
<script src="js/products.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Inclure le layout
include '../../includes/layout.php';
?> 