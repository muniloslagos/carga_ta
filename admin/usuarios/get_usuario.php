<?php
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../classes/Usuario.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$db = new Database();
$usuarioClass = new Usuario($db->getConnection());

$id = intval($_GET['id'] ?? 0);
$usuario = $usuarioClass->getById($id);

if (!$usuario) {
    http_response_code(404);
    exit;
}

// Obtener perfiles asignados al usuario
$perfiles = $usuarioClass->getPerfiles($id);
// Si no tiene perfiles en la nueva tabla, usar el perfil de la tabla usuarios
if (empty($perfiles)) {
    $perfiles = [$usuario['perfil']];
}

// No enviar contraseña al cliente
unset($usuario['password']);

// Agregar perfiles al array del usuario
$usuario['perfiles'] = $perfiles;

header('Content-Type: application/json');
echo json_encode($usuario);
?>
