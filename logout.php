<?php
session_start();
session_destroy();
setcookie('auth_token', $token, time() - 3600, "/"); 
header("Location: index.php?message=logout");
exit();
?>