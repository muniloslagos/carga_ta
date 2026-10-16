<?php
require_once '../includes/check_auth.php';
require_login();

$conn = $db->getConnection();
$itemId = (int)($_GET['item_id'] ?? 0);
$usuarioId = (int)($current_user['id'] ?? 0);
$perfil = $current_profile ?? ($current_user['perfil'] ?? '');

$column = $conn->query("SHOW COLUMNS FROM items_transparencia LIKE 'plantilla_archivo'");
if ($itemId <= 0 || !$column || $column->num_rows === 0) {
    http_response_code(404);
    exit('Plantilla no disponible.');
}

$autorizado = $perfil === 'administrativo';
if (!$autorizado) {
    $stmtPermiso = $conn->prepare('SELECT 1 FROM item_usuarios WHERE item_id = ? AND usuario_id = ? LIMIT 1');
    $stmtPermiso->bind_param('ii', $itemId, $usuarioId);
    $stmtPermiso->execute();
    $autorizado = (bool)$stmtPermiso->get_result()->fetch_row();
    $stmtPermiso->close();
}
if (!$autorizado) {
    http_response_code(403);
    exit('No tiene permiso para descargar esta plantilla.');
}

$stmt = $conn->prepare('SELECT plantilla_archivo, plantilla_nombre_original FROM items_transparencia WHERE id = ? AND activo = 1 LIMIT 1');
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$item || empty($item['plantilla_archivo'])) {
    http_response_code(404);
    exit('El item no tiene una plantilla asociada.');
}

$base = realpath(dirname(__DIR__) . '/uploads/plantillas/items');
$archivo = realpath(dirname(__DIR__) . '/' . ltrim($item['plantilla_archivo'], '/\\'));
if (!$base || !$archivo || strpos($archivo, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($archivo)) {
    http_response_code(404);
    exit('El archivo de la plantilla no existe.');
}

$nombre = basename($item['plantilla_nombre_original'] ?: basename($archivo));
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($archivo));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($nombre));
header('X-Content-Type-Options: nosniff');
readfile($archivo);
exit;
