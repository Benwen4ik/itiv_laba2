<?php
$servername = "localhost";
$username = "root";
$password = "root"; 
$dbname = "itiv_laba1"; 

// Создание подключения
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Проверка подключения
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }


} catch (Exception $e) {
    // Обработка ошибок
    error_log($e->getMessage()); // Записываем ошибку в лог
    die("Ошибка подключения к базе данных: " .$e->getMessage() ); // Сообщение для пользователя
}
?>