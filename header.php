<?php if (isset($_SESSION['user_id'])): ?>
    <div class="header">
        <!-- <div class="user-buttons">
            <a href="logout.php" class="add-post-btn">Выход</a>
            <a href="setting.php" class="add-post-btn">Настройки</a>
            <a href="view_myPosts.php" class="add-post-btn">Мои посты</a>
            <a href="view_images.php" class="add-post-btn">Мои изображения</a>

            <?php if ($userRole === 'traveler' || $userRole === 'admin'): ?>
                <a href="add_post.php" class="add-post-btn">Добавить пост</a>
            <?php endif; ?>

            <?php if ($userRole === 'admin'): ?>
                <a href="add_location.php" class="add-post-btn">Добавить местоположение</a>
            <?php endif; ?>

            <a href="view_favorites.php" class="add-post-btn">Посмотреть избранные посты</a>
        </div> -->

        <nav class="navbar">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li>
                    <a href="#" class="dropdown-toggle">Посты</a>
                    <div class="dropdown-menu">
                        <a href="view_posts.php">Все посты</a>
                        <a href="view_myPosts.php">Мои посты</a>
                        <a href="view_favorites.php">Избранные посты</a>
                    </div>
                </li>
                <li><a href="view_images.php">Медиа</a></li>
                <li> <a href="view_booking.php">Бронирование</a></li>
                <li> <a href="setting.php">Настройки</a></li>
                <li> <a href="logout.php">Выход</a></li>
            </ul>
        </nav>
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Аватар" class="user-avatar">
    </div>
<?php else: ?>
    <div class="header">
        <nav class="navbar">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="login.php">Войти</a></li>
                <li><a href="register.php">Регистрация</a></li>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdownToggle = document.querySelector('.dropdown-toggle');
        const dropdownMenu = document.querySelector('.dropdown-menu');

        dropdownToggle.addEventListener('click', function (e) {
            e.preventDefault(); // Отменяем стандартное поведение ссылки
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Закрываем меню, если кликнули вне его
        document.addEventListener('click', function (event) {
            if (!dropdownToggle.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
    });
</script>