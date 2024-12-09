<?php
include 'db.php';
include 'auth.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$selectedTheme = $_COOKIE['theme'] ?? 'light';

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    // Проверяем, является ли пользователь владельцем поста
    $checkPostSql = "SELECT user_id FROM posts WHERE id = ?";
    $checkPostStmt = $conn->prepare($checkPostSql);
    $checkPostStmt->bind_param("i", $deleteId);
    $checkPostStmt->execute();
    $checkPostResult = $checkPostStmt->get_result();

    if ($checkPostResult->num_rows > 0) {
        $postRow = $checkPostResult->fetch_assoc();
        if ($postRow['user_id'] == $userId) {
            $deleteSql = "DELETE FROM posts WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("i", $deleteId);
            $deleteStmt->execute();
            $deleteStmt->close();
            header("Location: view_myPosts.php?message=deleted");
            exit();
        } else {
            header("Location: view_myPosts.php?message=access_denied");
            exit();
        }
    } else {
        header("Location: view_myPosts.php?message=post_not_found");
        exit();
    }
}

if (isset($_POST['update'])) {
    $updateId = intval($_POST['post_id']);
    $title = $_POST['title'];
    $content = $_POST['content'];
    $location = $_POST['location'];
    $checkPostSql = "SELECT user_id FROM posts WHERE id = ?";
    $checkPostStmt = $conn->prepare($checkPostSql);
    $checkPostStmt->bind_param("i", $updateId);
    $checkPostStmt->execute();
    $checkPostResult = $checkPostStmt->get_result();

    if ($checkPostResult->num_rows > 0) {
        $postRow = $checkPostResult->fetch_assoc();
        if ($postRow['user_id'] == $userId) {
            $updateSql = "UPDATE posts SET title = ?, content = ?, location = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssi", $title, $content, $location, $updateId);
            $updateStmt->execute();
            $updateStmt->close();
            header("Location: view_myPosts.php?message=updated");
            exit();
        } else {
            header("Location: view_myPosts.php?message=access_denied");
            exit();
        }
    } else {
        header("Location: view_myPosts.php?message=post_not_found");
        exit();
    }
}

try {
    $message = isset($_GET['message']) ? $_GET['message'] : '';
    $sql = "SELECT posts.*, users.username, 
            (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS likes_count,
            (SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = posts.id) AS user_liked
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.user_id = ? 
        ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
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
    <title>Ваши посты - Дневник путешественника</title>
    <link rel="stylesheet" href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style2.css'; ?>">
    <style>
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        .post-actions {
            margin-top: 10px;
        }
        
        .post-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .image-container {
            width: 150px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background-color: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .image-preview {
            width: 100%;
            height: auto;
        }
    </style>
</head>

<body>
    <h1>Ваши посты</h1>
    <a href="add_post.php" class="add-post-btn">Добавить запись</a>
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
            <?php elseif ($message === 'updated'): ?>
                <p style="color: green;">Пост успешно обновлен</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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

                $postId = $row['id'];
                $imageSql = "SELECT image FROM post_images WHERE post_id = ?";
                $imageStmt = $conn->prepare($imageSql);
                $imageStmt->bind_param("i", $postId);
                $imageStmt->execute();
                $imagesResult = $imageStmt->get_result();

                if ($imagesResult->num_rows > 0) {
                    echo '<div class="post-images">';
                    while ($imageRow = $imagesResult->fetch_assoc()) {
                        echo '<div class="image-container">';
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($imageRow['image']) . '" alt="Изображение" class="image-preview">';
                        echo '</div>';
                    }
                    echo '</div>'; 
                }

                // Лайки
                echo "<p><strong>Лайки:</strong> " . htmlspecialchars($row['likes_count']) . "</p>";

                // Действия для поста
                echo '<div class="post-actions">';
                echo "<a href='?delete=" . $row['id'] . "' onclick=\"return confirm('Вы уверены, что хотите удалить?');\" class='unlike-btn'>Удалить</a>";
                echo "<a href='edit_post.php?id=" . $row['id'] . "' class='add-post-btn'  >Изменить</a>";
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<p>У вас нет постов.</p>";
        }
        $stmt->close();
        $conn->close();
        ?>
    </div>
</body>

</html>