<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registrar logout en logs
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $action = "Cierre de sesión";
    
    $sql = "INSERT INTO logs (usuario_id, accion, ip_address) VALUES ($user_id, '$action', '$ip')";
    $conn->query($sql);
}

// Destruir sesión
session_destroy();

// Redirigir al login
header('Location: login.php');
exit;
?>
