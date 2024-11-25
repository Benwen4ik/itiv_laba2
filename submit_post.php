<?php
include 'db.php';
// include 'auth.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: add_post.php?message=login_error");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $location = $_POST['location'];
    $content = $_POST['content'];

    if (strlen($title) > 50) {
        header("Location: add_post.php?message=title_error");
        exit();
    }

    if (strlen($location) > 50) {
        header("Location: add_post.php?message=location_error");
        exit();
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $images = [];

    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['images']['type'][$key];

                if (in_array($fileType, $allowedTypes)) {
                    $imgData = file_get_contents($tmp_name);
                    $images[] = $imgData; // Сохраняем изображение для дальнейшей вставки
                } else {
                    header("Location: add_post.php?message=image_error");
                    exit();
                }
            }
        }
    }

    // Сохранение поста в базу данных
    $link = mysqli_connect("localhost", $username, $password, $dbname);
    if (!$link) {
        die("Ошибка подключения: " . mysqli_connect_error());
    }

    // Добавляем user_id в SQL-запрос
    $sql = "INSERT INTO posts (title, location, content, user_id) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $title, $location, $content, $_SESSION['user_id']); // Передаем user_id

    if (mysqli_stmt_execute($stmt)) {
        // Получаем ID последнего вставленного поста
        $post_id = mysqli_insert_id($link);

        // Вставка изображений в базу данных
        foreach ($images as $imgData) {
            $image_sql = "INSERT INTO post_images (post_id, image) VALUES (?, ?)";
            $image_stmt = mysqli_prepare($link, $image_sql);
            mysqli_stmt_bind_param($image_stmt, "is", $post_id, $imgData);

            // Выполнение запроса
            if (!mysqli_stmt_execute($image_stmt)) {
                echo "Ошибка при загрузке изображения: " . mysqli_error($link);
            }
            mysqli_stmt_close($image_stmt);
        }

        // Закрытие подготовленного запроса и соединения
        mysqli_stmt_close($stmt);
        mysqli_close($link);
        header("Location: view_posts.php?message=success");
        exit(); // Завершаем выполнение скрипта
    } else {
        echo "Ошибка при создании поста: " . mysqli_error($link);
    }
}
?>