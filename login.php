<?php
session_start();
include 'db.php'; // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $rememberMe = isset($_POST['remember_me']);

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            if ($rememberMe) {
                // Генерация токена
                $token = bin2hex(random_bytes(16));
                setcookie('auth_token', $token, time() + 3600, "/"); 
                setcookie("language", $user['lang'], time() + 3600, "/");

                // Сохраните токен в базе данных для текущего пользователя
                $updateTokenSql = "UPDATE users SET auth_token = ? WHERE id = ?";
                $updateTokenStmt = $conn->prepare($updateTokenSql);
                $updateTokenStmt->bind_param("si", $token, $_SESSION['user_id']);
                $updateTokenStmt->execute();
            }
            header("Location: view_posts.php?message=login");
            exit();
        } else {
            $error = "Неверный пароль";
        }
    } else {
        $error = "Пользователь не найден";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            /* Центрируем содержимое по горизонтали */
            align-items: center;
            /* Центрируем содержимое по вертикали */
            height: 100vh;
            /* Высота на весь экран */
            margin: 0;
            /* Убираем отступы по умолчанию */
        }

        .form-container {
            text-align: center;
            /* Центрируем текст внутри контейнера */
            width: 100%;
            /* Ширина 100% */
            max-width: 400px;
            /* Максимальная ширина контейнера */
        }

        .form-group {
            margin-bottom: 20px;
            /* Отступ между полями */
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            /* Ширина полей на 100% */
            padding: 10px;
            /* Внутренние отступы */
            box-sizing: border-box;
            /* Учитываем отступы в ширину */
        }
    </style>
</head>

<body>

    <div class="form-container">
        <h1>Вход</h1>
        <?php if (isset($error))
            echo "<p style='color:red;'>$error</p>"; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Имя пользователя" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember_me"> Запомнить меня
                </label>
            </div>
            <input type="submit" value="Войти">
        </form>

        <a class="add-post-btn" href="register.php">Зарегистрироваться</a>
    </div>
</body>

</html>