<?php
include 'db.php'; // Подключение к базе данных
include 'auth.php';
// session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// Получение пользователей, поставивших лайки
$sql = "SELECT users.username FROM likes 
        JOIN users ON likes.user_id = users.id 
        WHERE likes.post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лайки на посте</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Лайки на посте</h1>
<a href="view_posts.php">Назад к постам</a>

<table>
    <thead>
        <tr>
            <th>Пользователь</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo '</tr>';
            }
        } else {
            echo "<tr><td colspan='1'>Нет лайков на этом посте.</td></tr>";
        }

        $stmt->close();
        $conn->close();
        ?>
    </tbody>
</table>

</body>
</html>