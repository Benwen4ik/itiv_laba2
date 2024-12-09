<?php
include 'db.php';
include 'auth.php';
$selectedTheme = $_COOKIE['theme'] ?? 'light';

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить пост</title>
    <link rel="stylesheet"
        href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style_light.css'; ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map {
            height: 100%;
            width: 100%;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            position: relative;
            margin: auto;
            padding: 0;
            width: 90%;
            height: 90%;
            /* Устанавливаем высоту модального окна */
            background: white;
            border-radius: 5px;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 25px;
            color: black;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>Добавить пост о путешествии</h1>
    <div class="message">
        <?php if (isset($_GET['message'])): ?>
            <span style="color: red;">
                <?php
                if ($_GET['message'] === 'title_error') {
                    echo "Заголовок не должен превышать 50 символов.";
                } elseif ($_GET['message'] === 'location_error') {
                    echo "Местоположение не должно превышать 100 символов.";
                } elseif ($_GET['message'] === 'image_error') {
                    echo "Прикрепляемый файл должен быть изображением (JPEG, PNG, GIF, JPG).";
                } elseif ($_GET['message'] === 'error') {
                    echo "Произошла ошибка при создании поста.";
                } elseif ($_GET['message'] === 'file_error') {
                    echo "Произошла ошибка при работе с файлом.";
                } elseif ($_GET['message'] === 'login_error') {
                    echo "Для создания постов необходимо авторизоваться";
                }
                ?>
            </span>
        <?php endif; ?>
    </div>
    <form action="submit_post.php" method="post" enctype="multipart/form-data">
        <label for="title">Заголовок:</label>
        <input type="text" id="title" name="title" required maxlength="50">

        <label for="content">Текст поста:</label>
        <textarea id="content" name="content" required></textarea>

        <label for="location">Местоположение:</label>
        <input type="text" id="location" name="location" maxlength="150">

        <button type="button" id="openMapBtn">Открыть карту</button>
        <div id="mapModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeMapBtn">&times;</span>
                <div id="map"></div>
            </div>
        </div>

        <label for="images">Прикрепить файлы:</label>
        <input type="file" id="images" name="images[]" multiple>

        <input type="submit" value="Добавить пост">
    </form>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;

        // Инициализация карты
        function initMap() {
            map = L.map('map').setView([53.9, 27.5667], 10); // Центр карты (Минск)

            // Добавление слоя карты
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Обработка клика по карте
            map.on('click', function (e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(e.latlng).addTo(map);

                // Получаем адрес по координатам
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${e.latlng.lat}&lon=${e.latlng.lng}&format=json`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            document.getElementById('location').value = data.display_name; // Сохраняем название места
                        }
                    })
                    .catch(error => console.error('Ошибка при получении адреса:', error));
            });
        }

        // Открытие модального окна
        document.getElementById('openMapBtn').onclick = function () {
            document.getElementById('mapModal').style.display = 'block';
            initMap(); // Инициализируем карту при открытии
        }

        // Закрытие модального окна
        document.getElementById('closeMapBtn').onclick = function () {
            document.getElementById('mapModal').style.display = 'none';
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function (event) {
            const modal = document.getElementById('mapModal');
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>