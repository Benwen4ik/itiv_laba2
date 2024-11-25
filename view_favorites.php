<?php
include 'db.php';
include 'auth.php';
// session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $sql = "SELECT posts.*, users.username 
            FROM posts 
            JOIN likes ON posts.id = likes.post_id 
            JOIN users ON posts.user_id = users.id 
            WHERE likes.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Избранные посты</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Избранные посты</h1>

<table>
    <thead>
        <tr>
            <th>Заголовок</th>
            <th>Содержимое</th>
            <th>Дата создания</th>
            <th>Автор</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($row['content'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo '</tr>';
            }
        } else {
            echo "<tr><td colspan='4'>Нет избранных постов.</td></tr>";
        }

        $stmt->close();
        $conn->close();
        ?>
    </tbody>
</table>

<a href="view_posts.php" class="add-post-btn">Вернуться к постам</a>

</body>
</html>