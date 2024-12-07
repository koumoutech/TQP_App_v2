<?php
// Démarrer le buffer de sortie
ob_start();

// Utiliser le nonce s'il existe, sinon en créer un
if (!isset($nonce)) {
    $nonce = base64_encode(random_bytes(16));
}

// Définir l'en-tête CSP
header("Content-Security-Policy: script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net/npm/sweetalert2@11;");
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title ?? 'TQP App'; ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <div class="app-container">
        <?php include $_SESSION['role'] === 'admin' ? 'admin_menu.php' : 'user_menu.php'; ?>
        
        <main class="main-content">
            <div class="content-wrapper">
                <?php echo $content; ?>
            </div>
        </main>
    </div>

    <script src="<?php echo $base_url; ?>/assets/js/main.js"></script>
    <?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
<?php
ob_end_flush();
?> 