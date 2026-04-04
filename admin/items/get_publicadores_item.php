<?php
require_once '../../config/config.php';
require_once '../../config/Database.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$item_id = intval($_GET['item_id'] ?? 0);

// Obtener todos los publicadores (perfil = 'publicador')
$stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE perfil = 'publicador' AND activo = 1 ORDER BY nombre");
$stmt->execute();
$result = $stmt->get_result();
$publicadores = [];
while ($u = $result->fetch_assoc()) {
    $publicadores[] = $u;
}

// Obtener publicadores asignados a este item
$stmt = $conn->prepare("SELECT usuario_id FROM item_publicadores WHERE item_id = ?");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();
$asignados = [];
while ($a = $result->fetch_assoc()) {
    $asignados[] = (int)$a['usuario_id'];
}

header('Content-Type: application/json');
echo json_encode([
    'publicadores' => $publicadores,
    'asignados' => $asignados
]);
?>
