<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

// Vérification de la connexion admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Configuration
$dossier_upload = "../../uploads/slides/";
$types_autorises = array('jpg', 'jpeg', 'png', 'gif');
$taille_max = 5 * 1024 * 1024; // 5 MB

// Création du dossier s'il n'existe pas
if (!file_exists($dossier_upload)) {
    mkdir($dossier_upload, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["slide"])) {
        try {
            $pdo = getConnection();
            
            // Traitement de l'upload
            $fichier = $_FILES["slide"];
            $nom_fichier = basename($fichier["name"]);
            $extension = strtolower(pathinfo($nom_fichier, PATHINFO_EXTENSION));
            
            // Vérifications
            $erreurs = array();
            
            // Vérification du type MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $fichier["tmp_name"]);
            finfo_close($finfo);
            
            $mime_autorises = array(
                'image/jpeg',
                'image/png',
                'image/gif'
            );
            
            if (!in_array($mime_type, $mime_autorises)) {
                $erreurs[] = "Type de fichier non autorisé";
            }
            
            if ($fichier["size"] > $taille_max) {
                $erreurs[] = "Fichier trop volumineux (max 5MB)";
            }
            
            // Upload si pas d'erreurs
            if (empty($erreurs)) {
                $nouveau_nom = uniqid() . "_" . time() . "." . $extension;
                $chemin_complet = $dossier_upload . $nouveau_nom;
                
                if (move_uploaded_file($fichier["tmp_name"], $chemin_complet)) {
                    // Insertion dans la base de données
                    $sql = "INSERT INTO slides (filename, path, uploaded_by, created_at) VALUES (?, ?, ?, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $nom_fichier,
                        '/uploads/slides/' . $nouveau_nom,
                        $_SESSION['user_id']
                    ]);
                    
                    $message_succes = "Slide uploadé avec succès";
                } else {
                    $erreurs[] = "Erreur lors de l'upload";
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            $erreurs[] = "Erreur lors de l'enregistrement";
        } catch (Exception $e) {
            error_log("Erreur: " . $e->getMessage());
            $erreurs[] = "Une erreur est survenue";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload de Slide - Administration</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Upload de nouveau slide</h1>
        
        <?php if (isset($erreurs) && !empty($erreurs)): ?>
            <div class="alert alert-danger">
                <?php foreach($erreurs as $erreur): ?>
                    <p><?php echo htmlspecialchars($erreur); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($message_succes)): ?>
            <div class="alert alert-success">
                <p><?php echo htmlspecialchars($message_succes); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="upload-form">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="slide">Sélectionner une image</label>
                    <input type="file" id="slide" name="slide" accept="image/*" required>
                    <small class="form-text">Formats acceptés: JPG, PNG, GIF - Max 5MB</small>
                </div>
                <button type="submit" class="btn btn-primary">Uploader</button>
                <a href="index.php" class="btn btn-secondary">Retour</a>
            </form>
        </div>
        
        <div class="upload-preview" id="imagePreview"></div>
    </div>

    <script>
    document.getElementById('slide').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                preview.innerHTML = `<img src="${e.target.result}" alt="Aperçu" class="preview-image">`;
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html>