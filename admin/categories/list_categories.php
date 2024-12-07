<?php
session_start();
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Récupérer toutes les catégories
$query = "SELECT c.*, COUNT(p.id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$categories = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestion des catégories</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Catégories de produits</h2>
        
        <div class="actions">
            <button onclick="showAddForm()" class="btn btn-primary">Ajouter une catégorie</button>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Nombre de produits</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td><?php echo $category['product_count']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                    <td>
                        <button onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
                                class="btn btn-primary">Éditer</button>
                        <?php if ($category['product_count'] == 0): ?>
                            <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                    class="btn btn-danger">Supprimer</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal pour ajouter/éditer une catégorie -->
    <div id="categoryModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3 id="modalTitle">Ajouter une catégorie</h3>
            <form id="categoryForm" method="POST" action="save_category.php">
                <input type="hidden" name="id" id="categoryId">
                <div class="form-group">
                    <label>Nom de la catégorie</label>
                    <input type="text" name="name" id="categoryName" required>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Annuler</button>
            </form>
        </div>
    </div>

    <script>
    function showAddForm() {
        document.getElementById('modalTitle').textContent = 'Ajouter une catégorie';
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryName').value = '';
        document.getElementById('categoryModal').style.display = 'block';
    }

    function editCategory(id, name) {
        document.getElementById('modalTitle').textContent = 'Modifier la catégorie';
        document.getElementById('categoryId').value = id;
        document.getElementById('categoryName').value = name;
        document.getElementById('categoryModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('categoryModal').style.display = 'none';
    }

    function deleteCategory(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
            window.location.href = 'delete_category.php?id=' + id;
        }
    }
    </script>
</body>
</html> 