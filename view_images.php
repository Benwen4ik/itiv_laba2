<?php
include 'db.php';
include 'auth.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$selectedTheme = $_COOKIE['theme'] ?? 'light';

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

unset($_SESSION['error']);
unset($_SESSION['success']);

try {
    $sqlMedia = "SELECT id,file_path FROM media_files WHERE user_id = ?";
    $stmtMedia = $conn->prepare($sqlMedia);
    $stmtMedia->bind_param("i", $userId);
    $stmtMedia->execute();
    $mediaResult = $stmtMedia->get_result();
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}

if (isset($_GET['delete_image'])) {
    $imageId = intval($_GET['delete_image']);
    $deleteImageSql = "DELETE FROM media_files WHERE id = ?";
    $deleteImageStmt = $conn->prepare($deleteImageSql);
    $deleteImageStmt->bind_param("i", $imageId);
    $deleteImageStmt->execute();
    $_SESSION['success'] = "Медиа файл удален" ;

    header("Location: view_images.php");
    exit();
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Изображения ваших постов и медиафайлы</title>
    <link rel="stylesheet"
        href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style2.css'; ?>">
    <style>
        .image-container {
            display: flex;
            flex-wrap: wrap;
            /* Позволяет элементам обтекать друг друга */
            gap: 10px;
            /* Отступ между элементами */
            justify-content: center;
            /* Центрируем элементы по горизонтали */
        }

        .image-item {
            display: flex;
            flex-direction: column;
            /* Располагаем элементы вертикально */
            align-items: center;
            /* Центрируем элементы */
            text-align: center;
            /* Центрируем текст внутри элемента */
        }

        .delete-link {
            margin-top: 5px;
            /* Отступ сверху для ссылки */
            color: #ff4d4d;
            /* Цвет ссылки для удаления */
            text-decoration: none;
            /* Убираем подчеркивание */
            font-size: 14px;
            /* Размер шрифта для ссылки */
        }

        .delete-link:hover {
            text-decoration: underline;
            /* Подчеркивание при наведении */
        }

        .image-preview {
            max-width: 100%;
            max-height: 150px;
            /* Фиксированная высота для изображений */
            object-fit: cover;
            /* Изображение будет обрезано по размеру контейнера */
            cursor: pointer;
            /* Курсор меняется на указатель при наведении на изображение */
        }
    </style>
    <script>
        function openModal(mediaSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const modalVideo = document.getElementById('modalVideo');
            modal.style.display = "flex";
            if (mediaSrc.endsWith('.mp4') || mediaSrc.endsWith('.avi') || mediaSrc.endsWith('.mov')) {
                modalImg.style.display = "none";
                modalVideo.style.display = "block";
                modalVideo.src = mediaSrc;
            } else {
                modalImg.style.display = "block";
                modalVideo.style.display = "none";
                modalImg.src = mediaSrc;
            }
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = "none";
            document.getElementById('modalVideo').pause(); // Остановить видео при закрытии
        }
    </script>
</head>

<body>
    <h1>Ваши изображения и медиафайлы</h1>
    <form action="upload_files.php" method="POST" enctype="multipart/form-data">
        <label for="file">Выберите файл для загрузки:</label>
        <input type="file" name="file" id="file" required>
        <input type="submit" value="Загрузить">
    </form>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <br>

    <div class="image-container">
        <?php
        if ($mediaResult->num_rows > 0) {
            while ($mediaRow = $mediaResult->fetch_assoc()) {
                $filePath = $mediaRow['file_path'];
                $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                echo '<div class="image-item">';
                if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo '<img src="' . $filePath . '" alt="Медиафайл" class="image-preview" onclick="openModal(\'' . $filePath . '\')">';
                } elseif (in_array($fileType, ['mp4', 'avi', 'mov'])) {
                    echo '<video class="image-preview" controls><source src="' . $filePath . '" type="video/' . $fileType . '">Ваш браузер не поддерживает видео.</video>';
                }
                echo '<a href="?delete_image=' . $mediaRow['id'] . '" class="delete-link" onclick="return confirm(\'Вы уверены, что хотите удалить это изображение?\');">Удалить</a>';
                echo '</div>';
            }
        } else {
            echo "<p>У вас нет загруженных медиафайлов</p>";
        }
        ?>
    </div>
    <!-- Модальное окно для увеличения изображений и видео -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage" alt="Изображение" style="display: none;">
        <video id="modalVideo" class="modal-content" controls style="display: none;"></video>
    </div>
</body>

</html>