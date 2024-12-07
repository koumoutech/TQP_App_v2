<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE (p.name LIKE :search OR p.description LIKE :search)";
if ($category) {
    $query .= " AND p.category_id = :category";
}
$query .= " LIMIT :offset, :limit";

$stmt = $conn->prepare($query);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
if ($category) {
    $stmt->bindValue(':category', $category, PDO::PARAM_INT);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Liste des produits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Liste des produits</h2>
        
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="">Toutes les catégories</option>
                    <?php
                    $cat_stmt = $conn->query("SELECT * FROM categories");
                    while ($cat = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                        $selected = $category == $cat['id'] ? 'selected' : '';
                        echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">Éditer</a>
                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="btn btn-danger">Supprimer</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function deleteProduct(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
            window.location.href = 'delete_product.php?id=' + id;
        }
    }
    </script>
</body>
</html> 