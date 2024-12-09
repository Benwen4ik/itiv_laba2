<?
include 'auth.php';
include 'header.php';

$selectedTheme = $_COOKIE['theme'] ?? 'light';
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дневник путешественника</title>
    <link rel="stylesheet"
        href="<?php echo $selectedTheme === 'dark' ? 'styles/style_index_night.css' : 'styles/style_index_light.css'; ?>">
</head>

<body>
    <?php if ($message): ?>
        <div class="message">
            <?php if ($message === 'login'): ?>
                <p style="color: green;">Авторизация прошла успешно</p>
            <?php elseif ($message === 'logout'): ?>
                <p style="color: green;">Выход из аккаунта произошел успешно</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <main>
        <section class="intro">
            <h2>Начните своё путешествие</h2>
            <p>Исследуйте мир, создавайте воспоминания и делитесь ими.</p>
            <!-- <button><a href="#">Начать</a></button> -->
        </section>
        <section class="about">
            <h2>О нашем сайте</h2>
            <p>Дневник путешественника — это платформа, которая помогает пользователям планировать, документировать и
                делиться своими путешествиями. Мы стремимся сделать ваши приключения незабываемыми, предоставляя
                инструменты для организации и визуализации ваших воспоминаний.</p>
        </section>
    </main>
</body>

</html>