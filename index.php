<?php
include 'config.php';

$stmt = $conn->query("SELECT * FROM quizzes");
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Index</title>
</head>
<body>
    <h1>Quiz Index</h1>
    <ul>
        <?php foreach ($quizzes as $quiz): ?>
            <li><?php echo htmlspecialchars($quiz['title']); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
