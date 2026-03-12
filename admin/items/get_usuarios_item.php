<?php
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../classes/Item.php';
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
$itemClass = new Item($db->getConnection());
$usuarioClass = new Usuario($db->getConnection());

$item_id = intval($_GET['item_id'] ?? 0);

// Obtener todos los usuarios
$usuarios_result = $usuarioClass->getAll();
$usuarios = [];
while ($u = $usuarios_result->fetch_assoc()) {
    $usuarios[] = $u;
}

// Obtener usuarios asignados
$asignados_result = $itemClass->getAsignedUsers($item_id);
$asignados = [];
while ($a = $asignados_result->fetch_assoc()) {
    $asignados[] = $a['id'];
}

header('Content-Type: application/json');
echo json_encode([
    'usuarios' => $usuarios,
    'asignados' => $asignados
]);
?>
