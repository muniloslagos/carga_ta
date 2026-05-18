<?php
require_once '../includes/check_auth.php';
require_login();
require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$mes = isset($_POST['mes']) ? (int)$_POST['mes'] : 0;
$ano = isset($_POST['ano']) ? (int)$_POST['ano'] : 0;
$observacion = trim($_POST['observacion'] ?? '');

if (!$item_id || !$mes || !$ano || $observacion === '') {
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos']);
    exit;
}

if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
    echo json_encode(['success' => false, 'error' => 'Período inválido']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verificar que el usuario está asignado al item
$check = $conn->prepare("SELECT COUNT(*) as c FROM item_usuarios WHERE item_id = ? AND usuario_id = ?");
$check->bind_param('ii', $item_id, $user_id);
$check->execute();
$result = $check->get_result()->fetch_assoc();
if ($result['c'] == 0) {
    echo json_encode(['success' => false, 'error' => 'No tiene permisos sobre este item']);
    exit;
}

// Crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS `observaciones_sin_movimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `observacion` text NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `periodo` (`item_id`, `mes`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$stmt = $conn->prepare("INSERT INTO observaciones_sin_movimiento (item_id, usuario_id, mes, ano, observacion) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('iiiis', $item_id, $user_id, $mes, $ano, $observacion);

if ($stmt->execute()) {

    // ─── CREAR DOCUMENTO PLACEHOLDER AUTOMÁTICAMENTE ───────────────────
    try {
        // Obtener nombre del item
        $checkItem = $conn->prepare("SELECT nombre FROM items_transparencia WHERE id = ?");
        $checkItem->bind_param('i', $item_id);
        $checkItem->execute();
        $itemResult = $checkItem->get_result();
        
        if ($itemResult->num_rows === 0) {
            throw new Exception('Item no encontrado');
        }
        $itemData = $itemResult->fetch_assoc();
        $itemNombre = $itemData['nombre'];
        
        // Obtener cargador_id (usuario asignado al item)
        $checkUsuario = $conn->prepare("SELECT usuario_id FROM item_usuarios WHERE item_id = ? LIMIT 1");
        $checkUsuario->bind_param('i', $item_id);
        $checkUsuario->execute();
        $usuarioResult = $checkUsuario->get_result();
        
        if ($usuarioResult->num_rows === 0) {
            throw new Exception('No hay usuario asignado a este item');
        }
        $usuarioData = $usuarioResult->fetch_assoc();
        $cargador_id = $usuarioData['usuario_id'];
        
        // Crear documento placeholder
        $titulo = "Sin Movimiento - " . $itemNombre;
        $descripcion = "Documento placeholder para Sin Movimiento. Observación: " . $observacion;
        $archivo = "sin_movimiento_placeholder_" . uniqid() . ".txt";
        
        $insertDoc = $conn->prepare("INSERT INTO documentos (item_id, usuario_id, titulo, descripcion, archivo, mes_carga, ano_carga, estado, fecha_subida) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())");
        if (!$insertDoc) {
            throw new Exception("Error al preparar INSERT de documento: " . $conn->error);
        }
        $insertDoc->bind_param('iisssii', $item_id, $cargador_id, $titulo, $descripcion, $archivo, $mes, $ano);
        if (!$insertDoc->execute()) {
            throw new Exception("Error al ejecutar INSERT de documento: " . $insertDoc->error);
        }
        $documento_id = $conn->insert_id;
        
        // Crear entrada en documento_seguimiento si la tabla existe
        $checkTableSeguimiento = $conn->query("SHOW TABLES LIKE 'documento_seguimiento'");
        if ($checkTableSeguimiento->num_rows > 0) {
            $insertSeg = $conn->prepare("INSERT INTO documento_seguimiento (documento_id, item_id, usuario_id, mes, ano, fecha_envio) VALUES (?, ?, ?, ?, ?, NOW())");
            $insertSeg->bind_param('iiiii', $documento_id, $item_id, $cargador_id, $mes, $ano);
            if (!$insertSeg->execute()) {
                throw new Exception("Error al crear documento_seguimiento: " . $insertSeg->error);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Observación registrada exitosamente y documento placeholder creado',
            'documento_id' => $documento_id
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => true,
            'message' => 'Observación registrada, pero hubo un error al crear el documento: ' . $e->getMessage(),
            'observation_saved' => true
        ]);
    }
    // ────────────────────────────────────────────────────────────────────
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar la observación']);
}
