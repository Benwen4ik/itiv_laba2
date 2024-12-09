<?php
include 'db.php';
include 'auth.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$selectedTheme = $_COOKIE['theme'] ?? 'light';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['trip_id'])) {
        $tripId = intval($_POST['trip_id']);

        // Проверка, что путевка существует
        $checkTripSql = "SELECT * FROM trips WHERE id = ?";
        $checkTripStmt = $conn->prepare($checkTripSql);
        $checkTripStmt->bind_param("i", $tripId);
        $checkTripStmt->execute();
        $checkTripResult = $checkTripStmt->get_result();

        if ($checkTripResult->num_rows > 0) {
            // Проверка на действие: удаление или бронирование
            if (isset($_POST['delete_trip']) && $userRole === 'admin') {
                // Удаление путевки
                $deleteSql = "DELETE FROM trips WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("i", $tripId);
                $deleteStmt->execute();
                $_SESSION['success'] = "Путевка успешно удалена!";
            } else {
                // Проверка, забронирована ли поездка
                $bookingCheckSql = "SELECT * FROM bookings WHERE user_id = ? AND trip_id = ?";
                $bookingCheckStmt = $conn->prepare($bookingCheckSql);
                $bookingCheckStmt->bind_param("ii", $userId, $tripId);
                $bookingCheckStmt->execute();
                $bookingCheckResult = $bookingCheckStmt->get_result();

                if ($bookingCheckResult->num_rows > 0) {
                    // Если поездка уже забронирована, отменяем бронирование
                    $cancelSql = "DELETE FROM bookings WHERE user_id = ? AND trip_id = ?";
                    $cancelStmt = $conn->prepare($cancelSql);
                    $cancelStmt->bind_param("ii", $userId, $tripId);
                    $cancelStmt->execute();
                    $_SESSION['success'] = "Бронирование отменено!";
                } else {
                    // Сохранение нового бронирования
                    $bookSql = "INSERT INTO bookings (user_id, trip_id) VALUES (?, ?)";
                    $bookStmt = $conn->prepare($bookSql);
                    $bookStmt->bind_param("ii", $userId, $tripId);
                    $bookStmt->execute();
                    $_SESSION['success'] = "Путевка успешно забронирована!";
                }
            }
        } else {
            $_SESSION['error'] = "Ошибка: Путевка не найдена.";
        }
    }
}

try {
    $sql = "SELECT * FROM trips ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование путевок</title>
    <link rel="stylesheet"
        href="<?php echo $selectedTheme === 'dark' ? 'styles/style_night.css' : 'styles/style2.css'; ?>">
    <style>
        .trip-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .trip {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background-color: #f9f9f9;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .trip h3 {
            margin: 0;
        }

        .trip p {
            margin: 5px 0;
        }

        .trip img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .book-btn, .delete-btn {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .book-btn:hover, .delete-btn:hover {
            background: #218838;
        }

        .delete-btn {
            background: #dc3545; /* Красный цвет для удаления */
        }

        .delete-btn:hover {
            background: #c82333; /* Темнее красный при наведении */
        }
    </style>
</head>

<body>
    <h1>Доступные путевки</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="message" style="color: green;">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="message" style="color: red;">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    <?php if ($userRole === 'traveler' || $userRole === 'admin'): ?>
        <a href="add_trip.php" class="add-post-btn">Добавить пост</a>
    <?php endif; ?>
    <div class="trip-container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="trip">';
                echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                echo "<p><strong>Местоположение:</strong> " . htmlspecialchars($row['location']) . "</p>";
                echo "<p><strong>Цена:</strong> " . htmlspecialchars($row['price']) . " руб.</p>";
                echo "<p><strong>Описание:</strong><br>" . nl2br(htmlspecialchars($row['content'])) . "</p>";

                if ($row['image']) {
                    echo '<img src="data:image/jpeg;base64,' . base64_encode($row['image']) . '" alt="Изображение путевки">';
                }

                echo '<form method="POST" action="">';
                echo '<input type="hidden" name="trip_id" value="' . $row['id'] . '">';

                // Если пользователь администратор, добавляем кнопку удаления
                if ($userRole === 'admin') {
                    echo '<button type="submit" name="delete_trip" class="delete-btn">Удалить путевку</button>';
                }

                // Проверка, забронирована ли поездка
                $bookingCheckSql = "SELECT * FROM bookings WHERE user_id = ? AND trip_id = ?";
                $bookingCheckStmt = $conn->prepare($bookingCheckSql);
                $bookingCheckStmt->bind_param("ii", $userId, $row['id']);
                $bookingCheckStmt->execute();
                $bookingCheckResult = $bookingCheckStmt->get_result();

                if ($bookingCheckResult->num_rows > 0) {
                    echo '<button type="submit" name="book_trip" class="book-btn">Отменить бронирование</button>';
                } else {
                    echo '<button type="submit" name="book_trip" class="book-btn">Забронировать</button>';
                }

                echo '</form>';
                echo '</div>';
            }
        } else {
            echo "<p>Нет доступных путевок.</p>";
        }
        $stmt->close();
        $conn->close();
        ?>
    </div>
</body>

</html>