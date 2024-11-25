<?php
include 'db.php'; // Подключение к базе данных
include 'auth.php';
// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: view_posts.php?message=access_denied");
    exit();
}

// Обработка добавления нового местоположения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationName = trim($_POST['location_name']);
    
    if (!empty($locationName)) {
        // Проверка на существование местоположения
        $checkSql = "SELECT * FROM locations WHERE name = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $locationName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Местоположение уже существует
            header("Location: add_location.php?message=exists");
            exit();
        } else {
            // Добавление нового местоположения
            $sql = "INSERT INTO locations (name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $locationName);
            
            if ($stmt->execute()) {
                header("Location: add_location.php?message=success");
                exit();
            } else {
                header("Location: add_location.php?message=error");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить местоположение</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<a href="view_posts.php" class="add-post-btn">Вернуться к таблице</a>
<h1>Добавить новое местоположение</h1>

<?php if (isset($_GET['message'])): ?>
    <p style="color: <?php 
        echo ($_GET['message'] === 'success') ? 'green' : 
             (($_GET['message'] === 'exists') ? 'orange' : 'red'); ?>">
        <?php 
        echo ($_GET['message'] === 'success') ? 'Местоположение успешно добавлено!' : 
             (($_GET['message'] === 'exists') ? 'Такое местоположение уже существует!' : 
             'Ошибка при добавлении.'); 
        ?>
    </p>
<?php endif; ?>

<form action="add_location.php" method="POST">
    <input type="text" name="location_name" placeholder="Название местоположения" required>
    <input type="submit" value="Добавить">
</form>

</body>
</html>