<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Récupérer le nom d'utilisateur de la session

// Traitement du formulaire pour ajouter un quiz
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'add_quiz') {
    $title = $_POST['title'];

    try {
        $stmt = $conn->prepare("INSERT INTO quizzes (user_id, title) VALUES (:user_id, :title)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->execute();
        echo "Quiz ajouté avec succès !";
    } catch (Exception $e) {
        echo "Une erreur s'est produite lors de l'ajout du quiz : " . $e->getMessage();
    }
}

// Traitement du formulaire pour ajouter une question
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'add_question') {
    $question_text = $_POST['question'];
    $quiz_id = $_POST['quiz_id']; // ID du quiz auquel la question appartient
    $answers = $_POST['answers']; // Tableau des réponses
    $correct_answer_index = $_POST['correct_answer']; // Index de la réponse correcte dans le tableau des réponses

    try {
        $conn->beginTransaction();

        // Insérer la question dans la table 'questions'
        $stmt = $conn->prepare("INSERT INTO questions (quiz_id, user_id, question_text) VALUES (:quiz_id, :user_id, :question_text)");
        $stmt->bindParam(':quiz_id', $quiz_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':question_text', $question_text);
        $stmt->execute();
        $question_id = $conn->lastInsertId();

        // Insérer les réponses dans la table 'answers'
        $stmt = $conn->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (:question_id, :answer_text, :is_correct)");

        foreach ($answers as $index => $answer) {
            $is_correct = ($index == $correct_answer_index - 1) ? 1 : 0; // Vérifie si l'index de la réponse correspond à la réponse correcte
            $stmt->bindParam(':question_id', $question_id);
            $stmt->bindParam(':answer_text', $answer);
            $stmt->bindParam(':is_correct', $is_correct);
            $stmt->execute();
        }

        $conn->commit();
        echo "Question ajoutée avec succès !";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Une erreur s'est produite lors de l'ajout de la question : " . $e->getMessage();
    }
}

// Récupérer tous les quiz existants
$stmt = $conn->prepare("SELECT quizzes.*, users.username FROM quizzes INNER JOIN users ON quizzes.user_id = users.id");
$stmt->execute();
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Récupérer les quiz de l'utilisateur connecté
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : null;

// Récupérer les questions et les réponses en relation avec le quiz sélectionné
if ($selected_quiz_id) {
    $stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = :quiz_id");
    $stmt->bindParam(':quiz_id', $selected_quiz_id);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM answers WHERE question_id IN (SELECT id FROM questions WHERE quiz_id = :quiz_id)");
    $stmt->bindParam(':quiz_id', $selected_quiz_id);
    $stmt->execute();
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $questions = [];
    $answers = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Manager</title>
    <style>
        .container {
            display: flex;
        }
        .column {
            flex: 1;
            padding: 10px;
        }
        .column h2 {
            margin-top: 0;
        }
        .all-quizzes {
            border-right: 1px solid #ccc;
        }
    </style>
</head>
<body>

<h2>Bonjour <?php echo htmlspecialchars($username); ?>, bienvenue sur votre dashboard</h2>

<div class="container">
    <div class="column all-quizzes">
        <h2>Tous les Quizz</h2>
        <ul>
        <?php foreach ($quizzes as $quiz): ?>
    <li>
        <a href="?quiz_id=<?php echo $quiz['id']; ?>"><?php echo htmlspecialchars($quiz['title']); ?></a>
        (Créé par <?php echo htmlspecialchars($quiz['username']); ?>)
    </li>
<?php endforeach; ?>

        </ul>
    </div>

    <div class="column your-quizzes">
        <h2>Vos Quizz</h2>
        <form method="post">
            <input type="hidden" name="form_type" value="add_quiz">
            Title: <input type="text" name="title" required><br>
            <input type="submit" value="Add Quiz">
        </form>

        <ul>
            <?php foreach ($user_quizzes as $quiz): ?>
                <li><a href="?quiz_id=<?php echo $quiz['id']; ?>"><?php echo htmlspecialchars($quiz['title']); ?></a></li>
            <?php endforeach; ?>
        </ul>

        <?php if ($selected_quiz_id && array_search($selected_quiz_id, array_column($user_quizzes, 'id')) !== false): ?>
            <h2>Add a Question to <?php echo htmlspecialchars($user_quizzes[array_search($selected_quiz_id, array_column($user_quizzes, 'id'))]['title']); ?></h2>
            <form method="post">
                <input type="hidden" name="form_type" value="add_question">
                <input type="hidden" name="quiz_id" value="<?php echo $selected_quiz_id; ?>">
                <label for="question">Question:</label><br>
                <textarea id="question" name="question" rows="4" cols="50" required></textarea><br>
                
                <label for="answer1">Réponse 1:</label><br>
                <input type="text" id="answer1" name="answers[]" required><br>
                
                <label for="answer2">Réponse 2:</label><br>
                <input type="text" id="answer2" name="answers[]" required><br>
                
                <label for="answer3">Réponse 3:</label><br>
                <input type="text" id="answer3" name="answers[]" required><br>
                
                <label for="answer4">Réponse 4:</label><br>
                <input type="text" id="answer4" name="answers[]" required><br>
                
                <label for="correct_answer">Réponse Correcte:</label><br>
                <select id="correct_answer" name="correct_answer" required>
                    <option value="1">Réponse 1</option>
                    <option value="2">Réponse 2</option>
                    <option value="3">Réponse 3</option>
                    <option value="4">Réponse 4</option>
                </select><br>
                
                <input type="submit" value="Add Question">
            </form>
        <?php endif; ?>
    </div>

    <div class="column questions-answers">
        <?php if ($selected_quiz_id): ?>
            <h2>Questions and Answers for <?php echo htmlspecialchars($user_quizzes[array_search($selected_quiz_id, array_column($user_quizzes, 'id'))]['title']); ?></h2>
            <ul>
                <?php foreach ($questions as $question): ?>
                    <li>
                        <?php echo htmlspecialchars($question['question_text']); ?>
                        <ul>
                            <?php foreach ($answers as $answer): ?>
                                <?php if ($answer['question_id'] == $question['id']): ?>
                                    <li <?php if ($answer['is_correct']) echo 'style="font-weight:bold;"'; ?>>
                                        <?php echo htmlspecialchars($answer['answer_text']); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

</body>
</html>