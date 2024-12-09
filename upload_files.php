<?php
include 'db.php';
include 'auth.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES["file"])) {
        $target_dir = "media/"; // Директория для хранения загруженных файлов
        $target_file = $target_dir . basename($_FILES["file"]["name"]);
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                $sql = "INSERT INTO media_files (file_path, user_id) VALUES ('$target_file', '$userId')";
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['success'] = "Файл загружен и путь сохранен в базе данных";
                } else {
                    $_SESSION['error'] = "Ошибка: " . $sql . "<br>" . $conn->error;
                }
            } else {
                $_SESSION['error'] =  "Ошибка при загрузке файла";
            }
        } else {
            $_SESSION['error'] = "Ошибка: Неподдерживаемый формат файла";
        }
    } else {
        $_SESSION['error'] = "Ошибка: Файл не загружен";
    }

    header("Location: view_images.php");
    exit();
}

$conn->close();
?>