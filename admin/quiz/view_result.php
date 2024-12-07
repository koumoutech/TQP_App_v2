<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$result_id = $_GET['id'] ?? null;
if (!$result_id) {
    header('Location: quiz_results.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les détails du résultat
$query = "SELECT qr.*, q.title, q.category, u.username, u.service
          FROM quiz_results qr
          JOIN quizzes q ON qr.quiz_id = q.id
          JOIN users u ON qr.user_id = u.id
          WHERE qr.id = :id";
$stmt = $conn->prepare($query);
$stmt->execute([':id' => $result_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header('Location: quiz_results.php');
    exit();
}

// Récupérer les réponses détaillées
$answers_query = "SELECT q.question, a.answer, a.is_correct
                 FROM quiz_answers qa
                 JOIN questions q ON qa.question_id = q.id
                 JOIN answers a ON qa.answer_id = a.id
                 WHERE qa.result_id = :result_id
                 ORDER BY q.id";
$stmt = $conn->prepare($answers_query);
$stmt->execute([':result_id' => $result_id]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Détails du Résultat - <?php echo htmlspecialchars($result['username']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Détails du Résultat</h2>
        
        <div class="result-header">
            <div class="result-info">
                <p><strong>Utilisateur:</strong> <?php echo htmlspecialchars($result['username']); ?></p>
                <p><strong>Service:</strong> <?php echo htmlspecialchars($result['service']); ?></p>
                <p><strong>Quiz:</strong> <?php echo htmlspecialchars($result['title']); ?></p>
                <p><strong>Catégorie:</strong> <?php echo htmlspecialchars($result['category']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($result['completed_at'])); ?></p>
                <p><strong>Score:</strong> <?php echo number_format($result['score'], 2); ?>%</p>
            </div>
        </div>

        <div class="answers-list">
            <h3>Réponses détaillées</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Réponse donnée</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($answers as $index => $answer): ?>
                    <tr class="<?php echo $answer['is_correct'] ? 'correct-answer' : 'wrong-answer'; ?>">
                        <td><?php echo htmlspecialchars($answer['question']); ?></td>
                        <td><?php echo htmlspecialchars($answer['answer']); ?></td>
                        <td>
                            <?php if ($answer['is_correct']): ?>
                                <span class="badge badge-success">Correct</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Incorrect</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="actions">
            <a href="quiz_results.php" class="btn btn-secondary">Retour aux résultats</a>
            <a href="export_detail.php?id=<?php echo $result_id; ?>" class="btn btn-primary">
                Exporter en Excel
            </a>
        </div>
    </div>
</body>
</html> 