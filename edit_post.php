<?php
include 'db.php';
include 'auth.php';

$userId = $_SESSION['user_id'] ?? null;
$postId = intval($_GET['id'] ?? 0);
$selectedTheme = $_COOKIE['theme'] ?? 'light';

if ($postId && $userId) {
    // Получаем пост по ID и проверяем, принадлежит ли он пользователю
    $sql = "SELECT * FROM posts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();

    if (!$post) {
        die("Пост не найден или у вас нет доступа к нему.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_post'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $location = $_POST['location'];

        $updateSql = "UPDATE posts SET title = ?, content = ?, location = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sssi", $title, $content, $location, $postId);
        $updateStmt->execute();
    }

    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == UPLOAD_ERR_OK) {
                $imageData = file_get_contents($_FILES['images']['tmp_name'][$key]);
                $insertImageSql = "INSERT INTO post_images (post_id, image) VALUES (?, ?)";
                $insertImageStmt = $conn->prepare($insertImageSql);
                $insertImageStmt->bind_param("ib", $postId, $imageData);
                $insertImageStmt->execute();
            }
        }
    }

    header("Location: view_myPosts.php?message=updated");
    exit();
}

// Обработка удаления изображения
if (isset($_GET['delete_image'])) {
    $imageId = intval($_GET['delete_image']);
    $deleteImageSql = "DELETE FROM post_images WHERE id = ? AND post_id = ?";
    $deleteImageStmt = $conn->prepare($deleteImageSql);
    $deleteImageStmt->bind_param("ii", $imageId, $postId);
    $deleteImageStmt->execute();
    $sql = "SELECT * FROM posts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();

    header("Location: edit_post.php?id=$postId");
    exit();
}

// Получение изображений поста
$imageSql = "SELECT id, image FROM post_images WHERE post_id = ?";
$imageStmt = $conn->prepare($imageSql);
$imageStmt->bind_param("i", $postId);
$imageStmt->execute();
$imagesResult = $imageStmt->get_result();

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать пост</title>
    <link rel="stylesheet" href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style2.css'; ?>">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }
        h1, h2 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .post-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .image-container {
            position: relative;
            width: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background-color: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .image-preview {
            width: 100%;
            height: auto;
            display: block;
        }
        .image-container a {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            padding: 5px;
            border-radius: 3px;
            text-decoration: none;
        }
        .image-container a:hover {
            background-color: rgba(255, 0, 0, 0.9);
        }
    </style>
</head>
<body>
    <h1>Редактировать пост</h1>
    <a href="view_myPosts.php" class="add-post-btn">Назад к постам</a>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Заголовок:</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

        <label for="content">Содержимое:</label>
        <textarea name="content" id="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>

        <label for="location">Местоположение:</label>
        <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($post['location']); ?>" required>

        <label for="images">Добавить изображения:</label>
        <input type="file" name="images[]" id="images" multiple accept="image/*">

        <input type="submit" name="update_post" value="Обновить пост">
    </form>

    <h2>Изображения поста</h2>
    <div class="post-images">
        <?php while ($imageRow = $imagesResult->fetch_assoc()): ?>
            <div class="image-container">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($imageRow['image']); ?>" alt="Изображение" class="image-preview">
                <a href="?delete_image=<?php echo $imageRow['id']; ?>&id=<?php echo $post['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить это изображение?');">Удалить</a>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>