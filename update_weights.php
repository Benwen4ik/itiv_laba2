<?php
include 'db.php';
include 'auth.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получение коэффициентов из формы
    $weight_author = $_POST['weight_author'];
    $weight_location = $_POST['weight_location'];
    $weight_likes = $_POST['weight_likes'];


    if ($weight_likes < -100 || $weight_author < -100 || $weight_location < -100 || 
        $weight_likes > 100 || $weight_author > 100 || $weight_location > 100) {
        $_SESSION['error'] = 'Коэффициенты должны быть от -100 до 100 ';
        header("Location: setting.php");
        exit();
    }

    $sql = "UPDATE ranking_weights SET weight_author = ?, weight_location = ?, weight_likes = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddd", $weight_author, $weight_location, $weight_likes);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Коэффициенты успешно обновлены';
    } else {
        $_SESSION['error'] = 'Ошибка: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: setting.php");
    exit();
}
?>