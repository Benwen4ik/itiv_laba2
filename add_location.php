<?php
include 'db.php';
include 'auth.php';
$selectedTheme = $_COOKIE['theme'] ?? 'light';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: view_posts.php?message=access_denied");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationName = trim($_POST['location_name']);
    
    
    if (!empty($locationName)) {
        $checkSql = "SELECT * FROM locations WHERE name = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $locationName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            header("Location: add_location.php?message=exists");
            exit();
        } else {
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

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить местоположение</title>
    <link rel="stylesheet" href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style_light.css'; ?>">
</head>
<body>
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