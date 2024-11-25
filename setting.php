<?php
include 'db.php';
include 'auth.php';
// session_start();

try {

    $languages = ['ru' => 'Русский', 'eng' => 'English'];
    $selectedLanguage = $_COOKIE['language'] ?? 'ru';
    $userRole = $_SESSION['role'] ?? 'user';

    // Проверка на наличие сообщений
    $error = $_SESSION['error'] ?? null;
    $success = $_SESSION['success'] ?? null;

    // Сбрасываем сообщения после отображения
    unset($_SESSION['error']);
    unset($_SESSION['success']);

    $weight_author = 0.5;
    $weight_location = 0.3;
    $weight_likes = 0.2;

    $sql = "SELECT weight_likes, weight_location, weight_author FROM ranking_weights WHERE id = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $weight_author = $row['weight_author'];
        $weight_location = $row['weight_location'];
        $weight_likes = $row['weight_likes'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Изменение языка
        if (isset($_POST['language'])) {
            $selectedLanguage = $_POST['language'];
            setcookie("language", $selectedLanguage, time() + 1440, "/");
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $updateLangSql = "UPDATE users SET lang = ? WHERE id = ?";
                $stmt = $conn->prepare($updateLangSql);
                $stmt->bind_param("si", $selectedLanguage, $userId);
                $stmt->execute();
                $stmt->close();
            }

            header("Location: setting.php");
            exit();
        }

        // Обработка загрузки аватарки
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $userId = $_SESSION['user_id'];
            $avatarFile = $_FILES['avatar'];
            $avatarPath = 'uploads/' . basename($avatarFile['name']);

            // Проверка типа файла
            $fileType = strtolower(pathinfo($avatarPath, PATHINFO_EXTENSION));
            if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $_SESSION['error'] = "Неверный формат файла. Пожалуйста, загрузите изображение";
                header("Location: setting.php");
                exit();
            }

            // Проверка размера файла
            $maxFileSize = 8 * 1024 * 1024; // 8 МБ
            if ($avatarFile['size'] > $maxFileSize) {
                $_SESSION['error'] = "Размер файла превышает 8 МБ. Пожалуйста, загрузите меньший файл";
                header("Location: setting.php");
                exit();
            }

            // Перемещение файла
            if (move_uploaded_file($avatarFile['tmp_name'], $avatarPath)) {
                $updateAvatarSql = "UPDATE users SET avatar = ? WHERE id = ?";
                $stmt = $conn->prepare($updateAvatarSql);
                $stmt->bind_param("si", $avatarPath, $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success'] = "Аватарка успешно обновлена";
            } else {
                $_SESSION['error'] = "Ошибка при загрузке файла";
            }

            header("Location: setting.php");
            exit();
        }
    }
} catch (Exception $exp) {
    $_SESSION['error'] = "Ошибка " . $exp->getMessage();
}

// $greeting = ($selectedLanguage === 'eng') ? 'Welcome!' : 'Добро пожаловать!';
?>

<!DOCTYPE html>
<html lang="<?php echo $selectedLanguage; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <form action="setting.php" method="POST">
        <label for="language">Выберите язык приветствия:</label>
        <select name="language" id="language">
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo $code; ?>" <?php echo ($selectedLanguage === $code) ? 'selected' : ''; ?>>
                    <?php echo $name; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" value="Сохранить">
    </form>
    <br>

    <!-- Форма для загрузки аватарки -->
    <form action="setting.php" method="POST" enctype="multipart/form-data">
        <label for="avatar">Загрузить аватарку:</label>
        <input type="file" name="avatar" id="avatar" accept="image/*" required>
        <input type="submit" value="Сохранить аватарку">
    </form>
    <br>

    <?php if (isset($_SESSION['user_id']) && $userRole === 'admin'): ?>
        <form method="POST" action="update_weights.php">
            <label for="weight_author">Коэффициент авторов:</label>
            <input type="number" step="0.1" name="weight_author" id="weight_author" value="<?php echo $weight_author; ?>"
                required>

            <label for="weight_location">Коэффициент местоположения:</label>
            <input type="number" step="0.1" name="weight_location" id="weight_location"
                value="<?php echo $weight_location; ?>" required>

            <label for="weight_likes">Коэффициент количества лайков:</label>
            <input type="number" step="0.1" name="weight_likes" id="weight_likes" value="<?php echo $weight_likes; ?>"
                required>

            <input type="submit" value="Сохранить">
        </form>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <a href="view_posts.php" class="add-post-btn">Вернуться к постам</a>
</body>

</html>