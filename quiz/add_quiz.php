<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $title = $_POST['title'];
    $category = $_POST['category'];
    $duration = $_POST['duration'];
    
    try {
        $conn->beginTransaction();
        
        // Insertion du quiz
        $quiz_query = "INSERT INTO quizzes (title, category, duration) VALUES (:title, :category, :duration)";
        $quiz_stmt = $conn->prepare($quiz_query);
        $quiz_stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':duration' => $duration
        ]);
        
        $quiz_id = $conn->lastInsertId();
        
        // Insertion des questions et réponses
        for ($i = 1; $i <= 30; $i++) {
            if (!empty($_POST["question$i"])) {
                // Insertion de la question
                $question_query = "INSERT INTO questions (quiz_id, question) VALUES (:quiz_id, :question)";
                $question_stmt = $conn->prepare($question_query);
                $question_stmt->execute([
                    ':quiz_id' => $quiz_id,
                    ':question' => $_POST["question$i"]
                ]);
                
                $question_id = $conn->lastInsertId();
                
                // Insertion des réponses
                for ($j = 1; $j <= 4; $j++) {
                    $answer = $_POST["answer{$i}_{$j}"];
                    $is_correct = isset($_POST["correct$i"]) && $_POST["correct$i"] == $j;
                    
                    $answer_query = "INSERT INTO answers (question_id, answer, is_correct) 
                                   VALUES (:question_id, :answer, :is_correct)";
                    $answer_stmt = $conn->prepare($answer_query);
                    $answer_stmt->execute([
                        ':question_id' => $question_id,
                        ':answer' => $answer,
                        ':is_correct' => $is_correct
                    ]);
                }
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Quiz ajouté avec succès";
        header('Location: list_quiz.php');
        exit();
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erreur lors de l'ajout: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un Quiz</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_menu.php'; ?>
    
    <div class="content">
        <h2>Créer un nouveau Quiz</h2>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Titre du Quiz</label>
                <input type="text" name="title" required>
            </div>
            
            <div class="form-group">
                <label>Catégorie</label>
                <select name="category" required>
                    <option value="CEX">CEX</option>
                    <option value="KIOSQUE">KIOSQUE</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Durée (en minutes)</label>
                <input type="number" name="duration" value="40" min="1" required>
            </div>
            
            <div id="questions-container">
                <?php for($i = 1; $i <= 30; $i++): ?>
                <div class="question-block">
                    <h3>Question <?php echo $i; ?></h3>
                    <div class="form-group">
                        <label>Question</label>
                        <textarea name="question<?php echo $i; ?>" required></textarea>
                    </div>
                    
                    <?php for($j = 1; $j <= 4; $j++): ?>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="correct<?php echo $i; ?>" value="<?php echo $j; ?>" required>
                            Réponse <?php echo $j; ?>
                        </label>
                        <input type="text" name="answer<?php echo $i; ?>_<?php echo $j; ?>" required>
                    </div>
                    <?php endfor; ?>
                </div>
                <?php endfor; ?>
            </div>
            
            <button type="submit" class="btn btn-primary">Créer le Quiz</button>
        </form>
    </div>
</body>
</html> 