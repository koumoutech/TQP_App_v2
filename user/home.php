<?php
session_start();
require_once '../config/database.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les derniers quiz
$query = "SELECT q.*, COUNT(qr.id) as attempts, MAX(qr.score) as best_score
          FROM quizzes q 
          LEFT JOIN quiz_results qr ON q.id = qr.quiz_id AND qr.user_id = :user_id
          WHERE q.status = 'active'
          GROUP BY q.id
          ORDER BY q.created_at DESC
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$recent_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les produits actifs
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN product_categories c ON p.category_id = c.id 
          WHERE p.status = 'active'
          ORDER BY p.created_at DESC
          LIMIT 5";
$products = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Page d'Accueil";
$base_url = "..";

// Ajouter les styles du carousel
$extra_css = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">';

ob_start();
?>

<div class="content-wrapper">
    <!-- En-tête de bienvenue -->
    <div class="welcome-header">
        <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?> !</h1>
        <p>Découvrez nos derniers produits et quiz disponibles</p>
    </div>

    <!-- Carousel des produits -->
    <div class="section">
        <div class="section-header">
            <h2>Nos Produits & Services</h2>
            <a href="products/list_products.php" class="btn btn-primary">Voir tout</a>
        </div>
        <div class="swiper products-carousel">
            <div class="swiper-wrapper">
                <?php foreach ($products as $product): ?>
                <div class="swiper-slide">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="product-tag">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>
                        <div class="product-footer">
                            <button class="btn btn-primary" onclick="showProductDetails(<?php echo $product['id']; ?>)">
                                Voir les détails
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>

    <!-- Carousel des quiz -->
    <div class="section">
        <div class="section-header">
            <h2>Quiz Disponibles</h2>
            <a href="quiz/list_quiz.php" class="btn btn-primary">Voir tout</a>
        </div>
        <div class="swiper quiz-carousel">
            <div class="swiper-wrapper">
                <?php foreach ($recent_quizzes as $quiz): ?>
                <div class="swiper-slide">
                    <div class="quiz-card">
                        <div class="quiz-status">
                            <?php if ($quiz['attempts'] > 0): ?>
                            <div class="status-badge <?php echo $quiz['best_score'] >= 70 ? 'success' : 'warning'; ?>">
                                Meilleur score: <?php echo number_format($quiz['best_score'], 1); ?>%
                            </div>
                            <?php else: ?>
                            <div class="status-badge new">Nouveau</div>
                            <?php endif; ?>
                        </div>
                        <div class="quiz-content">
                            <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                            <div class="quiz-meta">
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $quiz['duration']; ?> minutes</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-question-circle"></i>
                                    <span><?php echo $quiz['total_questions']; ?> questions</span>
                                </div>
                            </div>
                            <div class="quiz-actions">
                                <a href="quiz/take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary">
                                    <?php echo $quiz['attempts'] > 0 ? 'Retenter' : 'Commencer'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>
</div>

<style>
.welcome-header {
    text-align: center;
    margin-bottom: 3rem;
}

.welcome-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.section {
    margin-bottom: 3rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.swiper {
    padding: 1rem;
}

.swiper-slide {
    height: auto;
}

.swiper-button-next,
.swiper-button-prev {
    color: var(--sidebar-bg);
}

.swiper-pagination-bullet-active {
    background: var(--sidebar-bg);
}

@media (max-width: 768px) {
    .welcome-header {
        text-align: left;
        padding: 0 1rem;
    }

    .section-header {
        padding: 0 1rem;
    }
}
</style>

<?php
$content = ob_get_clean();

// JavaScript pour le carousel
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const carouselOptions = {
        slidesPerView: 1,
        spaceBetween: 20,
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 3,
            },
        },
    };

    new Swiper(".products-carousel", carouselOptions);
    new Swiper(".quiz-carousel", carouselOptions);
});
</script>';

include '../includes/layout.php';
?> 