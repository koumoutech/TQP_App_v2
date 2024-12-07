<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <h1>TQP Management</h1>
        </div>
    </div>

    <div class="menu-section">
        <a href="<?php echo $base_url; ?>/user/home.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'home.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Page d'Accueil</span>
        </a>

        <a href="<?php echo $base_url; ?>/user/products/list_products.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/products/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Produits & Services</span>
        </a>

        <a href="<?php echo $base_url; ?>/user/quiz/list_quiz.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/quiz/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i>
            <span>Quiz</span>
        </a>

        <a href="<?php echo $base_url; ?>/user/accounts/my_accounts.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/accounts/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i>
            <span>Comptes</span>
        </a>
    </div>

    <div class="menu-section mt-auto">
        <a href="<?php echo $base_url; ?>/user/profile/change_password.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'change_password.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-key"></i>
            <span>Changer mot de passe</span>
        </a>
        <a href="<?php echo $base_url; ?>/auth/logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: #FFCC30;
    color: #000;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 1rem;
    margin-bottom: 2rem;
}

.sidebar-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.menu-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #000;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    gap: 1rem;
}

.menu-item:hover {
    background: rgba(0, 0, 0, 0.1);
}

.menu-item.active {
    background: rgba(0, 0, 0, 0.15);
    font-weight: 500;
}

.menu-item i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

.mt-auto {
    margin-top: auto;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    padding-top: 1rem;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        z-index: 1000;
    }

    .sidebar.active {
        transform: translateX(0);
    }
}
</style> 