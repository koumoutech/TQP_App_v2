<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé'
    ]);
    exit();
}

try {
    $product_id = $_GET['id'] ?? null;
    if (!$product_id) {
        throw new Exception('ID du produit manquant');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer les détails du produit
    $query = "SELECT p.*, c.name as category_name,
              GROUP_CONCAT(pf.feature SEPARATOR '||') as features
              FROM products p 
              LEFT JOIN product_categories c ON p.category_id = c.id
              LEFT JOIN product_features pf ON p.id = pf.product_id
              WHERE p.id = :id AND p.status = 'active'
              GROUP BY p.id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Produit non trouvé');
    }

    // Formater les caractéristiques
    if ($product['features']) {
        $features = explode('||', $product['features']);
        $formattedFeatures = '<ul class="feature-list">';
        foreach ($features as $feature) {
            $formattedFeatures .= '<li><i class="fas fa-check"></i> ' . htmlspecialchars($feature) . '</li>';
        }
        $formattedFeatures .= '</ul>';
        $product['features'] = $formattedFeatures;
    }

    // Formater le prix
    if ($product['price']) {
        $product['price'] = number_format($product['price'], 0, ',', ' ');
    }

    echo json_encode([
        'success' => true,
        'product' => [
            'id' => $product['id'],
            'name' => htmlspecialchars($product['name']),
            'description' => htmlspecialchars($product['description']),
            'image_url' => $product['image_url'],
            'price' => $product['price'],
            'category' => htmlspecialchars($product['category_name']),
            'features' => $product['features']
        ]
    ]);

} catch (Exception $e) {
    error_log("Erreur get_product_details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 