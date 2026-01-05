<?php
require_once '../includes/check_auth.php';
require_login();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Historial.php';

header('Content-Type: application/json');

if (!isset($_GET['item_id'])) {
    echo json_encode(['error' => 'item_id no proporcionado']);
    exit;
}

$item_id = (int)$_GET['item_id'];
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : null;

$db = new Database();
$historialClass = new Historial($db->getConnection());

// Obtener historial
$historial = $historialClass->getHistorialItem($item_id, $mes, $ano);

echo json_encode([
    'success' => true,
    'historial' => $historial
]);
?>
