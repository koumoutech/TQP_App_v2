<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="<?php echo $base_url; ?>/assets/images/logo.png" alt="MTN">
            <h1>TQP App</h1>
        </div>
    </div>

    <div class="menu-section">
        <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Tableau de bord</span>
        </a>

        <a href="<?php echo $base_url; ?>/admin/home/manage_slideshow.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/home/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Accueil</span>
        </a>

        <a href="<?php echo $base_url; ?>/admin/products/list_products.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/products/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Produits & Services</span>
        </a>

        <a href="<?php echo $base_url; ?>/admin/users/list_users.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Utilisateurs</span>
        </a>

        <a href="<?php echo $base_url; ?>/admin/accounts/list_accounts.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/accounts/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i>
            <span>Comptes</span>
        </a>

        <a href="<?php echo $base_url; ?>/admin/quiz/list_quiz.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/quiz/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i>
            <span>Quiz</span>
        </a>
    </div>

    <div class="menu-section mt-auto">
        <a href="<?php echo $base_url; ?>/admin/profile/change_password.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'change_password.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-key"></i>
            <span>Changer mot de passe</span>
        </a>
        <a href="<?php echo $base_url; ?>/auth/logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div> 