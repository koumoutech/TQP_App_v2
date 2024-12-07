<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $details = $_POST['details'];
    
    // Gestion du fichier média
    $media_url = '';
    $media_type = '';
    
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4'];
        $filename = $_FILES['media']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $newname = uniqid() . '.' . $filetype;
            $upload_dir = '../uploads/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['media']['tmp_name'], $upload_dir . $newname)) {
                $media_url = 'uploads/' . $newname;
                $media_type = in_array($filetype, ['mp4']) ? 'video' : 'image';
            }
        }
    }
    
    $query = "INSERT INTO products (name, category_id, description, details, media_url, media_type) 
              VALUES (:name, :category_id, :description, :details, :media_url, :media_type)";
              
    $stmt = $conn->prepare($query);
    
    try {
        $stmt->execute([
            ':name' => $name,
            ':category_id' => $category_id,
            ':description' => $description,
            ':details' => $details,
            ':media_url' => $media_url,
            ':media_type' => $media_type
        ]);
        $_SESSION['success'] = "Produit ajouté avec succès";
        header('Location: list_products.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout: " . $e->getMessage();
    }
}

$db = new Database();
$conn = $db->getConnection();
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un produit</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Ajouter un produit</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Catégorie</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Détails</label>
                <textarea name="details"></textarea>
            </div>
            
            <div class="form-group">
                <label>Média (Image ou Vidéo)</label>
                <input type="file" name="media" accept="image/*,video/*">
            </div>
            
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
</body>
</html> 