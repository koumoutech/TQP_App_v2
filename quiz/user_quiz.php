<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Vérifier si l'utilisateur a déjà passé un quiz ce mois-ci
$current_month = date('Y-m');
$check_query = "SELECT qr.*, q.title, q.category
                FROM quiz_results qr
                JOIN quizzes q ON qr.quiz_id = q.id
                WHERE qr.user_id = :user_id 
                AND DATE_FORMAT(qr.completed_at, '%Y-%m') = :current_month";
$stmt = $conn->prepare($check_query);
$stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':current_month' => $current_month
]);
$completed_quiz = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer le quiz disponible pour ce mois
$available_quiz_query = "SELECT * FROM quizzes 
                        WHERE DATE_FORMAT(created_at, '%Y-%m') = :current_month
                        ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($available_quiz_query);
$stmt->execute([':current_month' => $current_month]);
$available_quiz = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Mensuel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/user_menu.php'; ?>
    
    <div class="content">
        <h2>Quiz Mensuel</h2>
        
        <?php if ($completed_quiz): ?>
            <div class="quiz-result">
                <h3>Quiz déjà complété ce mois-ci</h3>
                <div class="result-details">
                    <p>Quiz : <?php echo htmlspecialchars($completed_quiz['title']); ?></p>
                    <p>Catégorie : <?php echo htmlspecialchars($completed_quiz['category']); ?></p>
                    <p>Score : <?php echo $completed_quiz['score']; ?>%</p>
                    <p>Complété le : <?php echo date('d/m/Y H:i', strtotime($completed_quiz['completed_at'])); ?></p>
                </div>
            </div>
        <?php elseif ($available_quiz): ?>
            <div class="quiz-available">
                <h3><?php echo htmlspecialchars($available_quiz['title']); ?></h3>
                <p>Catégorie : <?php echo htmlspecialchars($available_quiz['category']); ?></p>
                <p>Durée : <?php echo $available_quiz['duration']; ?> minutes</p>
                <div class="quiz-instructions">
                    <h4>Instructions :</h4>
                    <ul>
                        <li>Le quiz contient 30 questions à choix multiples</li>
                        <li>Vous avez <?php echo $available_quiz['duration']; ?> minutes pour compléter le quiz</li>
                        <li>Une seule tentative est autorisée</li>
                        <li>Assurez-vous d'avoir une connexion internet stable</li>
                    </ul>
                </div>
                <a href="take_quiz.php?id=<?php echo $available_quiz['id']; ?>" 
                   class="btn btn-primary">Commencer le Quiz</a>
            </div>
        <?php else: ?>
            <div class="no-quiz">
                <p>Aucun quiz n'est disponible pour le moment.</p>
                <p>Le prochain quiz sera disponible le mois prochain.</p>
            </div>
        <?php endif; ?>

        <div class="quiz-history">
            <h3>Historique des Quiz</h3>
            <?php
            $history_query = "SELECT qr.*, q.title, q.category
                            FROM quiz_results qr
                            JOIN quizzes q ON qr.quiz_id = q.id
                            WHERE qr.user_id = :user_id
                            ORDER BY qr.completed_at DESC";
            $stmt = $conn->prepare($history_query);
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quiz</th>
                        <th>Catégorie</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $result): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($result['completed_at'])); ?></td>
                        <td><?php echo htmlspecialchars($result['title']); ?></td>
                        <td><?php echo htmlspecialchars($result['category']); ?></td>
                        <td><?php echo $result['score']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 