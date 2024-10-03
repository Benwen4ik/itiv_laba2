<?php
include 'db.php'; // Подключение к базе данных

session_start();

// $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

if (isset($_SESSION['user_id'])) {
    $username = $_SESSION['username'];
    $userRole = $_SESSION['role'] ?? 'user';
}

// Обработка удаления записи
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    // Получаем user_id поста
    $postSql = "SELECT user_id FROM posts WHERE id = ?";
    $postStmt = $conn->prepare($postSql);
    $postStmt->bind_param("i", $deleteId);
    $postStmt->execute();
    $postResult = $postStmt->get_result();

    if ($postResult->num_rows > 0) {
        $postRow = $postResult->fetch_assoc();

        // Проверяем, является ли пользователь администратором или владельцем поста
        if (isset($_SESSION['user_id'])) {
            if ($postRow['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
                // Если совпадает, удаляем пост
                $deleteSql = "DELETE FROM posts WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("i", $deleteId);
                $deleteStmt->execute();
                $deleteStmt->close();

                // Перенаправление после удаления
                header("Location: view_posts.php?message=deleted");
                exit();
            } else {
                // Если не совпадает, выдаем ошибку прав доступа
                header("Location: view_posts.php?message=access_denied");
                exit();
            }
        }
    } else {
        // Если пост не найден
        header("Location: view_posts.php?message=post_not_found");
        exit();
    }
}

// Обработка лайка
if (isset($_GET['like']) && isset($_SESSION['user_id'])) {
    $likePostId = intval($_GET['like']);
    $userId = $_SESSION['user_id'];

    // Проверяем, уже ли пользователь лайкнул этот пост
    $checkLikeSql = "SELECT * FROM likes WHERE user_id = ? AND post_id = ?";
    $checkLikeStmt = $conn->prepare($checkLikeSql);
    $checkLikeStmt->bind_param("ii", $userId, $likePostId);
    $checkLikeStmt->execute();
    $likeResult = $checkLikeStmt->get_result();

    if ($likeResult->num_rows == 0) {
        // Если лайка еще нет, добавляем его
        $insertLikeSql = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
        $insertLikeStmt = $conn->prepare($insertLikeSql);
        $insertLikeStmt->bind_param("ii", $userId, $likePostId);
        $insertLikeStmt->execute();
        $insertLikeStmt->close();
    }

    // Перенаправление после лайка
    header("Location: view_posts.php");
    exit();
}

// Обработка удаления лайка
if (isset($_GET['unlike']) && isset($_SESSION['user_id'])) {
    $unlikePostId = intval($_GET['unlike']);
    $userId = $_SESSION['user_id'];

    // Удаление лайка
    $deleteLikeSql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $deleteLikeStmt = $conn->prepare($deleteLikeSql);
    $deleteLikeStmt->bind_param("ii", $userId, $unlikePostId);
    $deleteLikeStmt->execute();
    $deleteLikeStmt->close();

    // Перенаправление после удаления лайка
    header("Location: view_posts.php");
    exit();
}

try {
    // Получаем значения поиска, если они есть
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    $message = isset($_GET['message']) ? $_GET['message'] : '';

    // Подготовка SQL-запроса для поиска
    $sql = "SELECT posts.*, users.username, 
            (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS likes_count,
            (SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = posts.id) AS user_liked
            FROM posts 
            JOIN users ON posts.user_id = users.id 
            WHERE (title LIKE ? OR content LIKE ? OR location LIKE ? OR users.username LIKE ?)";

    // Добавляем фильтр по дате, если указано
    if ($date) {
        $sql .= " AND DATE(created_at) = DATE(?)";
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);

    $searchTerm = "%" . $search . "%";
    $params = [isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null, $searchTerm, $searchTerm, $searchTerm, $searchTerm];

    // Добавляем параметры для даты
    if ($date) {
        $params[] = $date;
    }

    // Определяем типы параметров для bind_param
    $types = "issss" . (isset($params[5]) ? "s" : "");
    $stmt->bind_param($types, ...$params);

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
    <title>Дневник путешественника</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>Дневник путешественника</h1>
    <?php if (isset($_SESSION['user_id'])): ?>
        <h2>Привет, <?php echo htmlspecialchars($username); ?>!</h2>
    <?php endif; ?>

    <!-- Сообщение о результате создания поста -->
    <?php if ($message): ?>
        <div class="message">
            <?php if ($message === 'success'): ?>
                <p style="color: green;">Пост успешно создан!</p>
            <?php elseif ($message === 'error'): ?>
                <p style="color: red;">Произошла ошибка при создании поста.</p>
            <?php elseif ($message === 'deleted'): ?>
                <p style="color: orange;">Пост успешно удален!</p>
            <?php elseif ($message === 'access_denied'): ?>
                <p style="color: red;">У вас нет прав для удаления этого поста.</p>
            <?php elseif ($message === 'post_not_found'): ?>
                <p style="color: red;">Пост не найден.</p>
            <?php elseif ($message === 'login'): ?>
                <p style="color: green;">Авторизация прошла успешно</p>
            <?php elseif ($message === 'logout'): ?>
                <p style="color: green;">Выход из аккаунта произошел успешно</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php" class="add-post-btn">Выход</a>
        <?php if ($userRole === 'traveler' || $userRole === 'admin'): ?>
            <a href="add_post.php" class="add-post-btn">Добавить пост</a>
        <?php endif; ?>
    <?php else: ?>
        <a href="login.php" class="add-post-btn">Войти</a>
        <a href="register.php" class="add-post-btn">Регистрация</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id']) && $userRole === 'admin'): ?>
        <a href="add_location.php" class="add-post-btn">Добавить местоположение</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- <h2>Привет, <?php echo htmlspecialchars($username); ?>!</h2> -->
        <a href="view_favorites.php" class="add-post-btn">Посмотреть избранные посты</a>
    <?php endif; ?>

    <!-- Форма поиска -->
    <form action="view_posts.php" method="GET" class="search">
        <input type="text" name="search"
            placeholder="Поиск по заголовку, содержимому, местоположению или имени пользователя"
            value="<?php echo htmlspecialchars($search); ?>">
        Поиск по дате: <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
        <input type="submit" value="Поиск">
    </form>

    <table>
        <thead>
            <tr>
                <th>Заголовок</th>
                <th>Местоположение</th>
                <th>Содержимое</th>
                <th>Дата создания</th>
                <th>Автор</th>
                <th>Изображения</th>
                <th>Лайки</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                    echo "<td>" . nl2br(htmlspecialchars($row['content'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";

                    // Получение изображений для текущего поста
                    $post_id = $row['id'];
                    $image_sql = "SELECT image FROM post_images WHERE post_id = ?";
                    $image_stmt = $conn->prepare($image_sql);
                    $image_stmt->bind_param("i", $post_id);
                    $image_stmt->execute();
                    $image_result = $image_stmt->get_result();

                    if ($image_result->num_rows > 0) {
                        echo '<td>';
                        while ($image_row = $image_result->fetch_assoc()) {
                            echo '<img src="data:image/jpeg;base64,' . base64_encode($image_row['image']) . '" alt="Изображение">';
                        }
                        echo '</td>';
                    } else {
                        echo "<td>Изображения не найдены</td>";
                    }

                    // Лайки
                    echo "<td>" . htmlspecialchars($row['likes_count']) . " ";
                    if (isset($_SESSION['user_id'])) {
                        if ($row['user_liked'] > 0) {
                            echo "<span style='color: green;'>Вы лайкнули</span>";
                            echo " | <a href='?unlike=" . $row['id'] . "' class='unlike-btn'>Убрать лайк</a>";
                        } else {
                            echo "<a href='?like=" . $row['id'] . "' class='like-btn'>Лайкнуть</a>";
                        }
                        if ($row['user_id'] == $_SESSION['user_id']) {
                            echo " | <a href='view_likes.php?post_id=" . $row['id'] . "'>Посмотреть лайки</a>";
                        }
                    }
                    echo "</td>";

                    // Проверка, если текущий пользователь является автором поста или администратором
                    if (isset($_SESSION['user_id'])) {
                        if ($row['user_id'] == $_SESSION['user_id'] || $userRole === 'admin') {
                            echo "<td><a href='?delete=" . $row['id'] . "' onclick=\"return confirm('Вы уверены, что хотите удалить?');\">Удалить</a></td>";
                        } else {
                            echo "<td>Нет доступа</td>";
                        }
                    } else {
                        echo "<td>Нет доступа</td>";
                    }
                    echo '</tr>';
                }
            } else {
                echo "<tr><td colspan='8'>Нет постов, соответствующих вашему запросу.</td></tr>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </tbody>
    </table>

</body>

</html>