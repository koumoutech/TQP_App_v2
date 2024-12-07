<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Filtres
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construction de la requête
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($category) {
    $query .= " AND c.id = :category";
    $params[':category'] = $category;
}

// Compte total pour la pagination
$count_stmt = $conn->prepare(str_replace('p.*, c.name as category_name', 'COUNT(*)', $query));
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Requête finale avec pagination
$query .= " ORDER BY p.created_at DESC LIMIT :offset, :limit";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les catégories pour le filtre
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestion des Produits";
$base_url = "../..";

// Actions dans l'en-tête
$header_actions = '
<div class="header-actions">
    <button class="btn btn-primary" onclick="showAddProductModal()">
        <i class="fas fa-plus"></i> Ajouter un produit
    </button>
    <button class="btn btn-secondary" onclick="exportProducts()">
        <i class="fas fa-download"></i> Exporter
    </button>
</div>';

// Contenu principal
ob_start();
?>

<div class="content-wrapper">
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div class="form-group">
                <input type="text" name="search" placeholder="Rechercher un produit..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            <div class="form-group">
                <select name="category" class="form-control">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"
                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
    </div>

    <div class="products-grid">
        <?php foreach ($products as $product): ?>
        <div class="product-card">
            <div class="product-media">
                <?php if ($product['media_url']): ?>
                    <?php if ($product['media_type'] === 'video'): ?>
                        <video src="<?php echo htmlspecialchars($product['media_url']); ?>" 
                               controls class="product-video"></video>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($product['media_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    <?php endif; ?>
                <?php else: ?>
                    <div class="product-image-placeholder">
                        <i class="fas fa-box"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <span class="badge badge-category">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </span>
                <p class="product-description">
                    <?php echo htmlspecialchars($product['description']); ?>
                </p>
            </div>
            <div class="product-actions">
                <button class="btn-icon" onclick="editProduct(<?php echo $product['id']; ?>)"
                        data-tooltip="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon" onclick="viewDetails(<?php echo $product['id']; ?>)"
                        data-tooltip="Voir détails">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-icon text-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)"
                        data-tooltip="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
               class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

// CSS supplémentaire
$extra_css = '
<style>
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.product-card {
    background: var(--bg-color);
    border-radius: 1rem;
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: transform 0.2s;
}

.product-card:hover {
    transform: translateY(-2px);
}

.product-media {
    position: relative;
    width: 100%;
    padding-top: 75%; /* Ratio 4:3 */
    background: var(--bg-light);
    overflow: hidden;
}

.product-image, .product-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain; /* ou cover selon vos besoins */
}

.product-image-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    color: var(--text-light);
    text-align: center;
}

.product-info {
    padding: 1.5rem;
}

.product-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
}

.product-description {
    color: var(--text-light);
    margin: 0.5rem 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-actions {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.badge-category {
    background-color: var(--primary-color);
    color: var(--bg-dark);
}
</style>';

// Inclure la modale de produit
include 'modals/product_modal.php';

// JavaScript et CSS supplémentaires
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/products.js"></script>';

// Inclure le layout
include '../../includes/layout.php';
?> 