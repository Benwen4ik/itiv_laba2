<?php
include 'db.php';
include 'auth.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $location = $_POST['location'];
    $price = intval($_POST['price']);
    
    // Обработка загрузки изображения
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['image']['tmp_name']);
    }

    // Вставка новой путевки в базу данных
    $sql = "INSERT INTO trips (title, content, location, price, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $title, $content, $location, $price, $image);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Путевка успешно добавлена!";
        header("Location: view_booking.php"); 
        exit();
    } else {
        $_SESSION['error'] = "Ошибка: " . $stmt->error;
    }

    $stmt->close();
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить путевку</title>
    <link rel="stylesheet" href="styles/style2.css">
</head>

<body>
    <h1>Добавить новую путевку</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="message" style="color: green;">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="message" style="color: red;">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div>
            <label for="title">Заголовок:</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div>
            <label for="content">Описание:</label>
            <textarea id="content" name="content" required></textarea>
        </div>
        <div>
            <label for="location">Местоположение:</label>
            <input type="text" id="location" name="location" required>
        </div>
        <div>
            <label for="price">Цена:</label>
            <input type="number" id="price" name="price" required>
        </div>
        <div>
            <label for="image">Изображение:</label>
            <input type="file" id="image" name="image" accept="image/*" required>
        </div>
        <div>
            <button type="submit">Добавить путевку</button>
        </div>
    </form>
</body>

</html>