<?php
$servername = "localhost";
$username = "root";
$password = "root"; 
$dbname = "itiv_kyrsach"; 

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }


} catch (Exception $e) {
    error_log($e->getMessage());
    die("Ошибка подключения к базе данных: " .$e->getMessage() );
}
?>