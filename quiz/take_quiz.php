<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    header('Location: list_quiz.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les informations du quiz
$quiz_query = "SELECT * FROM quizzes WHERE id = :id";
$quiz_stmt = $conn->prepare($quiz_query);
$quiz_stmt->execute([':id' => $quiz_id]);
$quiz = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: list_quiz.php');
    exit();
}

// Récupérer les questions et réponses
$questions_query = "SELECT q.*, GROUP_CONCAT(a.id, ':', a.answer) as answers 
                   FROM questions q 
                   LEFT JOIN answers a ON q.id = a.question_id 
                   WHERE q.quiz_id = :quiz_id 
                   GROUP BY q.id";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->execute([':quiz_id' => $quiz_id]);
$questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz: <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        #timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div id="timer">Temps restant: <span id="time"><?php echo $quiz['duration']; ?>:00</span></div>
    
    <div class="content">
        <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
        
        <form method="POST" action="submit_quiz.php" id="quiz-form">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
            
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-block">
                    <h3>Question <?php echo $index + 1; ?></h3>
                    <p><?php echo htmlspecialchars($question['question']); ?></p>
                    
                    <?php
                    $answers = explode(',', $question['answers']);
                    shuffle($answers);
                    foreach ($answers as $answer):
                        list($answer_id, $answer_text) = explode(':', $answer);
                    ?>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="answer[<?php echo $question['id']; ?>]" 
                                   value="<?php echo $answer_id; ?>" required>
                            <?php echo htmlspecialchars($answer_text); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary">Soumettre</button>
        </form>
    </div>

    <script>
    // Timer
    let duration = <?php echo $quiz['duration']; ?> * 60;
    const timerDisplay = document.getElementById('time');
    
    const timer = setInterval(() => {
        duration--;
        const minutes = Math.floor(duration / 60);
        const seconds = duration % 60;
        timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (duration <= 0) {
            clearInterval(timer);
            document.getElementById('quiz-form').submit();
        }
    }, 1000);
    </script>
</body>
</html> 