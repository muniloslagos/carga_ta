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
$user_perfil = $_SESSION['profile'] ?? null;

if (!$user_id || $user_perfil !== 'publicador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo el publicador puede realizar esta acción']);
    exit;
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$mes = isset($_POST['mes']) ? (int)$_POST['mes'] : 0;
$ano = isset($_POST['ano']) ? (int)$_POST['ano'] : 0;

// Debug: guardar en log
error_log("crear_documento_placeholder - Datos recibidos: item_id=$item_id, mes=$mes, ano=$ano");

if (!$item_id || !$mes || !$ano) {
    error_log("crear_documento_placeholder - ERROR: Faltan datos. item_id=$item_id, mes=$mes, ano=$ano");
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verificar que existe observación sin movimiento para este item/periodo
$checkObservacion = $conn->prepare("SELECT id, observacion FROM observaciones_sin_movimiento WHERE item_id = ? AND mes = ? AND ano = ?");
$checkObservacion->bind_param('iii', $item_id, $mes, $ano);
$checkObservacion->execute();
$observacionResult = $checkObservacion->get_result();

if ($observacionResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No existe declaración de Sin Movimiento para este período']);
    exit;
}

$observacionData = $observacionResult->fetch_assoc();

// Verificar que no existe documento ya creado para este período
$checkDoc = $conn->prepare("
    SELECT d.id 
    FROM documentos d
    INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
    WHERE d.item_id = ? AND ds.mes = ? AND ds.ano = ?
    LIMIT 1
");
$checkDoc->bind_param('iii', $item_id, $mes, $ano);
$checkDoc->execute();
$docResult = $checkDoc->get_result();

if ($docResult->num_rows > 0) {
    // Ya existe documento, devolver ese ID
    $existingDoc = $docResult->fetch_assoc();
    echo json_encode(['success' => true, 'documento_id' => $existingDoc['id'], 'already_exists' => true]);
    exit;
}

// Obtener nombre del item para el título
$checkItem = $conn->prepare("SELECT nombre FROM items_transparencia WHERE id = ?");
$checkItem->bind_param('i', $item_id);
$checkItem->execute();
$itemResult = $checkItem->get_result();

if ($itemResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Item no encontrado']);
    exit;
}

$itemData = $itemResult->fetch_assoc();
$itemNombre = $itemData['nombre'];

// Obtener ID del usuario cargador asignado al item (para documento.usuario_id)
$checkUsuario = $conn->prepare("SELECT usuario_id FROM item_usuarios WHERE item_id = ? LIMIT 1");
$checkUsuario->bind_param('i', $item_id);
$checkUsuario->execute();
$usuarioResult = $checkUsuario->get_result();

if ($usuarioResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No hay usuario asignado a este item']);
    exit;
}

$usuarioData = $usuarioResult->fetch_assoc();
$cargador_id = $usuarioData['usuario_id'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // Crear documento placeholder
    $titulo = "Sin Movimiento - " . $itemNombre;
    $descripcion = "Documento placeholder para Sin Movimiento. Observación: " . $observacionData['observacion'];
    $archivo = "sin_movimiento_placeholder_" . uniqid() . ".txt"; // Archivo ficticio
    
    // Debug: verificar valores antes del INSERT
    error_log("crear_documento_placeholder - INSERT values: item_id=$item_id, cargador_id=$cargador_id, titulo=$titulo");
    
    $insertDoc = $conn->prepare("INSERT INTO documentos (item_id, usuario_id, titulo, descripcion, archivo, estado, fecha_subida) VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())");
    if (!$insertDoc) {
        throw new Exception("Error al preparar INSERT: " . $conn->error);
    }
    $insertDoc->bind_param('iisss', $item_id, $cargador_id, $titulo, $descripcion, $archivo);
    if (!$insertDoc->execute()) {
        throw new Exception("Error al ejecutar INSERT: " . $insertDoc->error);
    }
    $documento_id = $conn->insert_id;
    
    // Crear entrada en documento_seguimiento
    $checkTableSeguimiento = $conn->query("SHOW TABLES LIKE 'documento_seguimiento'");
    if ($checkTableSeguimiento->num_rows > 0) {
        $insertSeg = $conn->prepare("INSERT INTO documento_seguimiento (documento_id, item_id, usuario_id, mes, ano, fecha_envio) VALUES (?, ?, ?, ?, ?, NOW())");
        $insertSeg->bind_param('iiiii', $documento_id, $item_id, $cargador_id, $mes, $ano);
        $insertSeg->execute();
    }
    
    // NO eliminar la observación de Sin Movimiento - se mantiene para referencia
    // El verificador se asociará al documento placeholder
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'documento_id' => $documento_id,
        'message' => 'Documento placeholder creado exitosamente'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Error al crear documento placeholder: ' . $e->getMessage()]);
}
