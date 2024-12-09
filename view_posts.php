<?php
include 'db.php';
include 'auth.php';
// session_start();

// $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

$selectedLanguage = $_COOKIE['language'] ?? 'ru';
$selectedTheme = $_COOKIE['theme'] ?? 'light';
$greetingMessage = ($selectedLanguage === 'eng') ? "Welcome back, $username!" : "Добро пожаловать обратно, $username!";

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    $postSql = "SELECT user_id FROM posts WHERE id = ?";
    $postStmt = $conn->prepare($postSql);
    $postStmt->bind_param("i", $deleteId);
    $postStmt->execute();
    $postResult = $postStmt->get_result();

    if ($postResult->num_rows > 0) {
        $postRow = $postResult->fetch_assoc();

        if (isset($_SESSION['user_id'])) {
            if ($postRow['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
                // Если совпадает, удаляем пост
                $deleteSql = "DELETE FROM posts WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("i", $deleteId);
                $deleteStmt->execute();
                $deleteStmt->close();

                header("Location: view_posts.php?message=deleted");
                exit();
            } else {
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
        $insertLikeSql = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
        $insertLikeStmt = $conn->prepare($insertLikeSql);
        $insertLikeStmt->bind_param("ii", $userId, $likePostId);
        $insertLikeStmt->execute();
        $insertLikeStmt->close();
    }

    header("Location: view_posts.php");
    exit();
}

// Обработка удаления лайка
if (isset($_GET['unlike']) && isset($_SESSION['user_id'])) {
    $unlikePostId = intval($_GET['unlike']);
    $userId = $_SESSION['user_id'];
    $deleteLikeSql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $deleteLikeStmt = $conn->prepare($deleteLikeSql);
    $deleteLikeStmt->bind_param("ii", $userId, $unlikePostId);
    $deleteLikeStmt->execute();
    $deleteLikeStmt->close();

    header("Location: view_posts.php");
    exit();
}

// Получение коэффициентов из базы данных
$sql = "SELECT weight_likes, weight_location, weight_author FROM ranking_weights WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $weight_author = $row['weight_author'];
    $weight_location = $row['weight_location'];
    $weight_likes = $row['weight_likes'];
} else {
    $weight_author = 0.5;
    $weight_location = 0.3;
    $weight_likes = 0.2;
}

try {
    // Получаем значения поиска, если они есть
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    $message = isset($_GET['message']) ? $_GET['message'] : '';

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $sql = "SELECT posts.*, users.username, 
            (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS likes_count,
            (SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = posts.id) AS user_liked,
            (SELECT COUNT(*) FROM likes l INNER JOIN posts p ON l.post_id = p.id 
             WHERE l.user_id IN (SELECT user_id FROM likes WHERE user_id = ?)
             AND p.user_id = posts.user_id) AS user_favorite_count,
            (SELECT COUNT(*) FROM likes l 
             JOIN posts p ON l.post_id = p.id 
             WHERE l.user_id = ? AND p.location = posts.location) AS liked_location_count
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE (title LIKE ? OR content LIKE ? OR location LIKE ? OR users.username LIKE ?)
    ";


    if ($date) {
        $sql .= " AND DATE(created_at) = DATE(?)";
    }

    $sql .= "ORDER BY 
        (user_favorite_count * ?) +
        (liked_location_count * ?) +
        (likes_count * ?) DESC, created_At DESC";

    $stmt = $conn->prepare($sql);

    $searchTerm = "%" . $search . "%";
    $params = [$userId, $userId, $userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm];

    if ($date) {
        $params[] = $date;
    }

    $params[] = $weight_author;
    $params[] = $weight_location;
    $params[] = $weight_likes;

    $types = "iiisssssddd";

    if (!$date) {
        $types = "iiissssddd";
    }

    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дневник путешественника</title>
    <link rel="stylesheet"
        href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style2.css'; ?>">
    <!-- <style>
        .user-avatar {
            width: 80px;
            height: 80px;  
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }
    </style> -->
</head>

<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <h2 style="color: <?php echo ($selectedLanguage === 'eng') ? 'red' : 'green'; ?>;">
            <?php echo ($selectedLanguage === 'eng') ? "Welcome back, " . htmlspecialchars($username) . "!" : "Добро пожаловать обратно, " . htmlspecialchars($username) . "!"; ?>
        </h2>
    <?php endif; ?>

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

    <a href="add_post.php" class="add-post-btn">Добавить пост</a>

    <form action="view_posts.php" method="GET" class="search">
        <input type="text" name="search"
            placeholder="Поиск по заголовку, содержимому, местоположению или имени пользователя"
            value="<?php echo htmlspecialchars($search); ?>">
        Поиск по дате: <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
        <input type="submit" value="Поиск">
    </form>

    <!-- Контейнер для блоков постов -->
    <div class="post-container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="post">';
                echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                echo "<p><strong>Местоположение:</strong> " . htmlspecialchars($row['location']) . "</p>";
                echo "<p><strong>Содержимое:</strong><br>" . nl2br(htmlspecialchars($row['content'])) . "</p>";
                echo "<p><strong>Дата создания:</strong> " . htmlspecialchars($row['created_at']) . "</p>";
                echo "<p><strong>Автор:</strong> " . htmlspecialchars($row['username']) . "</p>";

                // Изображения поста
                $post_id = $row['id'];
                $image_sql = "SELECT image FROM post_images WHERE post_id = ?";
                $image_stmt = $conn->prepare($image_sql);
                $image_stmt->bind_param("i", $post_id);
                $image_stmt->execute();
                $image_result = $image_stmt->get_result();

                if ($image_result->num_rows > 0) {
                    echo '<div class="post-images">';
                    while ($image_row = $image_result->fetch_assoc()) {
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($image_row['image']) . '" alt="Изображение" class="image-preview" onclick="openModal(this);">';
                    }
                    echo '</div>';
                } else {
                    echo "<p>Изображения не найдены</p>";
                }

                // Лайки
                echo "<p><strong>Лайки:</strong> " . htmlspecialchars($row['likes_count']) . " ";
                if (isset($_SESSION['user_id'])) {
                    if ($row['user_liked'] > 0) {
                        echo "<span style='color: green;'>Вы лайкнули</span>";
                        echo " | <a href='?unlike=" . $row['id'] . "' class='unlike-btn'>Убрать лайк</a>";
                    } else {
                        echo "<a href='?like=" . $row['id'] . "' class='like-btn'>Лайкнуть</a>";
                    }
                    if ($row['user_id'] == $_SESSION['user_id']) {
                        echo " | <a href='view_likes.php?post_id=" . $row['id'] . "' class='like-btn'>Посмотреть лайки</a>";
                    }
                }
                echo "</p>";

                // Действия для поста
                echo '<div class="post-actions">';
                if (isset($_SESSION['user_id'])) {
                    if ($row['user_id'] == $_SESSION['user_id'] || $userRole === 'admin') {
                        echo "<a href='?delete=" . $row['id'] . "' onclick=\"return confirm('Вы уверены, что хотите удалить?');\" class='unlike-btn'>Удалить</a>";
                    }
                }
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<p>Нет постов, соответствующих вашему запросу.</p>";
        }

        $stmt->close();
        $conn->close();
        ?>
    </div>

    <!-- Модальное окно -->
    <div id="myModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <img id="modalImage" src="" alt="Изображение">
            <a class="prev" onclick="changeImage(-1)">&#10094;</a>
            <a class="next" onclick="changeImage(1)">&#10095;</a>
        </div>
    </div>

    <script>
        let currentImageIndex = 0;
        let images = [];

        function openModal(img) {
            const modal = document.getElementById("myModal");
            const modalImg = document.getElementById("modalImage");
            modal.style.display = "block";
            modalImg.src = img.src;

            // Запоминаем все изображения в одном посте
            images = Array.from(img.parentNode.querySelectorAll('img')).map(image => image.src);
            currentImageIndex = images.indexOf(img.src);
        }

        function closeModal() {
            const modal = document.getElementById("myModal");
            modal.style.display = "none";
        }

        function changeImage(direction) {
            currentImageIndex += direction;
            if (currentImageIndex < 0) {
                currentImageIndex = images.length - 1; // Переход к последнему изображению
            } else if (currentImageIndex >= images.length) {
                currentImageIndex = 0; // Переход к первому изображению
            }
            document.getElementById("modalImage").src = images[currentImageIndex];
        }

        // Закрыть модальное окно при клике вне него
        window.onclick = function (event) {
            const modal = document.getElementById("myModal");
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>