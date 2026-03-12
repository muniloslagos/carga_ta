<?php
require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../classes/Item.php';

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

$id = intval($_GET['id'] ?? 0);
$item = $itemClass->getById($id);

if (!$item) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');
echo json_encode($item);
?>
