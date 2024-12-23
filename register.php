<?php
include 'db.php';

$error = "";
$selectedTheme = $_COOKIE['theme'] ?? 'light';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Валидация длины имени пользователя и пароля
    if (strlen($username) < 6 || strlen($password) < 6) {
        $error = "Имя пользователя и пароль должны содержать минимум 6 символов.";
    } else {
        // Проверяем, существует ли уже пользователь с таким именем
        $checkSql = "SELECT * FROM users WHERE username = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "Пользователь с таким именем уже существует.";
        } else {
            $sql = "INSERT INTO users (username, password,email) VALUES (?, ?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $passwordHash, $email);

            if ($stmt->execute()) {
                header("Location: login.php?message=registered");
                exit();
            } else {
                $error = "Ошибка регистрации: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet"
        href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style_light.css'; ?>">
    <style>
        .body-form {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 75vh;
            margin: 0;
        }

        .form-container {
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div class="body-form">
        <div class="form-container">
            <h1>Регистрация</h1>
            <?php if ($error)
                echo "<p style='color:red;'>$error</p>"; ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Имя пользователя" required>
                </div>
                <div class="form-group">
                    <input type="text" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                </div>
                <input type="submit" value="Зарегистрироваться">
            </form>
            <br>
            <a href="login.php" class="add-post-btn"> Войти</a>
        </div>
    </div>
</body>

</html>