<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Paramètres de pagination
$items_per_page = 9;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Paramètres de filtrage
$category_id = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Construction de la requête avec filtres
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($category_id) {
    $where_conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if ($search) {
    $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Récupérer le nombre total de produits
$count_query = "SELECT COUNT(*) as total 
                FROM products p 
                WHERE $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Récupérer les produits avec pagination
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN product_categories c ON p.category_id = c.id 
          WHERE $where_clause
          ORDER BY p.created_at DESC
          LIMIT :offset, :limit";

$stmt = $conn->prepare($query);
$stmt->execute(array_merge($params, [
    ':offset' => $offset,
    ':limit' => $items_per_page
]));
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les catégories pour le filtre
$categories = $conn->query("SELECT * FROM product_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Produits & Services";
$base_url = "../..";

ob_start();
?>

<div class="content-wrapper">
    <!-- En-tête -->
    <div class="page-header">
        <h2>Produits & Services</h2>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section">
        <form id="filterForm" class="filters-form" method="GET">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Rechercher..." 
                       class="form-control">
            </div>

            <div class="filter-box">
                <select name="category" class="form-control" onchange="this.form.submit()">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $cat['id'] == $category_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($search || $category_id): ?>
            <a href="list_products.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Réinitialiser
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Résultats -->
    <div class="results-info">
        <?php if ($total_items > 0): ?>
        <p>Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $items_per_page, $total_items); ?> 
           sur <?php echo $total_items; ?> produits</p>
        <?php endif; ?>
    </div>

    <!-- Liste des produits -->
    <div class="products-grid">
        <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>Aucun produit trouvé</h3>
            <p>Essayez de modifier vos critères de recherche</p>
        </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
            <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="product-category">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </div>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                </div>
                <div class="product-actions">
                    <button class="btn btn-view" onclick="viewProduct(<?php echo $product['id']; ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-share" onclick="shareProduct(<?php echo $product['id']; ?>)">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <button class="btn btn-info" onclick="showProductInfo(<?php echo $product['id']; ?>)">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
        <a href="?page=<?php echo $current_page - 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
           class="btn btn-page">
            <i class="fas fa-chevron-left"></i>
        </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
           class="btn btn-page <?php echo $i === $current_page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?php echo $current_page + 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
           class="btn btn-page">
            <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.filters-section {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-image {
    height: 200px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    padding: 1.5rem;
}

.product-info h3 {
    margin: 0;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.product-category {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #FFCC30;
    color: black;
    border-radius: 50px;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.product-info p {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid #eee;
    justify-content: flex-end;
}

.btn-view, .btn-share, .btn-info {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-view {
    background: #FFCC30;
    color: black;
}

.btn-share {
    background: #f8f9fa;
    color: #666;
}

.btn-info {
    background: #f8f9fa;
    color: #666;
}

.btn-view:hover, .btn-share:hover, .btn-info:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .filters-section {
        flex-direction: column;
    }

    .products-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

.filters-form {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.results-info {
    color: #666;
    margin: 1rem 0;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.btn-page {
    min-width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: white;
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-page:hover {
    background: #f8f9fa;
}

.btn-page.active {
    background: #FFCC30;
    color: black;
}

@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
        width: 100%;
    }

    .search-box,
    .filter-box {
        width: 100%;
    }
}
</style>

<script>
function applyFilter() {
    const category = document.getElementById('categoryFilter').value;
    const products = document.querySelectorAll('.product-card');
    
    products.forEach(product => {
        if (!category || product.dataset.category === category) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

function viewProduct(id) {
    window.location.href = `view_product.php?id=${id}`;
}

function shareProduct(id) {
    // Implémenter le partage
}

function showProductInfo(id) {
    // Afficher les informations détaillées
}

// Soumission automatique du formulaire lors du changement de catégorie
document.querySelector('select[name="category"]').addEventListener('change', function() {
    this.form.submit();
});

// Soumission du formulaire après un délai lors de la recherche
let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        this.form.submit();
    }, 500);
});
</script>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?> 