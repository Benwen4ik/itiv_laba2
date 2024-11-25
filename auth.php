<?php
// auth.php
include 'db.php';

session_start();

// Функция для получения данных пользователя
function getUserData($conn, $userId) {
    $userSql = "SELECT avatar, username, role FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    return $userStmt->get_result();
}

// Проверка сессии
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userResult = getUserData($conn, $userId);
    
    if ($userResult->num_rows > 0) {
        $userRow = $userResult->fetch_assoc();
        $avatar = $userRow['avatar'] ? $userRow['avatar'] : 'uploads/avatar.png';
        $username = $userRow['username'];
        $userRole = $userRow['role'] ?? 'user';
    } /* else {
        // Если данные не найдены, перенаправляем на страницу входа
        header("Location: login.php");
        exit();
    } */
} elseif (isset($_COOKIE['auth_token'])) {
    // Проверка токена
    $authToken = $_COOKIE['auth_token'];
    $tokenSql = "SELECT id FROM users WHERE auth_token = ?";
    $tokenStmt = $conn->prepare($tokenSql);
    $tokenStmt->bind_param("s", $authToken);
    $tokenStmt->execute();
    $tokenResult = $tokenStmt->get_result();
    
    if ($tokenResult->num_rows > 0) {
        $userRow = $tokenResult->fetch_assoc();
        $_SESSION['user_id'] = $userRow['id'];
        
        // Получение данных пользователя
        $userId = $userRow['id'];
        $userResult = getUserData($conn, $userId);
        
        if ($userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            $avatar = $userRow['avatar'] ? $userRow['avatar'] : 'uploads/avatar.png';
            $username = $userRow['username'];
            $userRole = $userRow['role'] ?? 'user';
        }
    } else {
        // Если токен недействителен, удаляем куки и перенаправляем на страницу входа
        setcookie('auth_token', '', time() - 3600, "/");
        header("Location: login.php");
        exit();
    }
} /* else {
    // Если ни сессия, ни токен не найдены, перенаправляем на страницу входа
    header("Location: login.php");
    exit();
} */
?>