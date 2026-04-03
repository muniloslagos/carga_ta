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

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$mes = isset($_POST['mes']) ? (int)$_POST['mes'] : 0;
$ano = isset($_POST['ano']) ? (int)$_POST['ano'] : 0;
$accion = $_POST['accion'] ?? ''; // 'actualizar_observacion' o 'subir_documento'

// Validación detallada para debugging
$errores = [];
if (!$item_id) $errores[] = 'item_id';
if (!$mes) $errores[] = 'mes';
if (!$ano) $errores[] = 'ano';
if (!$accion) $errores[] = 'accion';

if (!empty($errores)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Faltan datos requeridos: ' . implode(', ', $errores),
        'recibido' => [
            'item_id' => $item_id,
            'mes' => $mes,
            'ano' => $ano,
            'accion' => $accion
        ]
    ]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verificar que el usuario está asignado al item O es publicador
$check = $conn->prepare("SELECT COUNT(*) as c FROM item_usuarios WHERE item_id = ? AND usuario_id = ?");
$check->bind_param('ii', $item_id, $user_id);
$check->execute();
$result = $check->get_result()->fetch_assoc();
$tiene_permiso = ($result['c'] > 0) || ($user_perfil === 'publicador');

if (!$tiene_permiso) {
    echo json_encode(['success' => false, 'error' => 'No tiene permisos sobre este item']);
    exit;
}

// Verificar que existe observación sin movimiento para este item/periodo
$checkObservacion = $conn->prepare("SELECT id FROM observaciones_sin_movimiento WHERE item_id = ? AND mes = ? AND ano = ?");
$checkObservacion->bind_param('iii', $item_id, $mes, $ano);
$checkObservacion->execute();
$observacionResult = $checkObservacion->get_result();

if ($observacionResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No existe declaración de Sin Movimiento para este período']);
    exit;
}

// Verificar que no existe verificador (si existe, está bloqueado)
$checkVerificador = $conn->prepare("
    SELECT v.id 
    FROM verificadores_publicador v 
    INNER JOIN documento_seguimiento ds ON v.documento_id = ds.documento_id
    WHERE v.item_id = ? AND ds.mes = ? AND ds.ano = ?
");
$checkVerificador->bind_param('iii', $item_id, $mes, $ano);
$checkVerificador->execute();
$verificadorResult = $checkVerificador->get_result();

if ($verificadorResult->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'No se puede modificar: ya existe un verificador publicado para este período']);
    exit;
}

// Procesar según la acción
if ($accion === 'actualizar_observacion') {
    $nueva_observacion = trim($_POST['observacion'] ?? '');
    
    if ($nueva_observacion === '') {
        echo json_encode(['success' => false, 'error' => 'La observación no puede estar vacía']);
        exit;
    }
    
    $updateObs = $conn->prepare("UPDATE observaciones_sin_movimiento SET observacion = ? WHERE item_id = ? AND mes = ? AND ano = ?");
    $updateObs->bind_param('siii', $nueva_observacion, $item_id, $mes, $ano);
    
    if ($updateObs->execute()) {
        echo json_encode(['success' => true, 'message' => 'Observación actualizada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar la observación']);
    }
    
} elseif ($accion === 'subir_documento') {
    
    // Validar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No se recibió el archivo o hubo un error en la carga']);
        exit;
    }
    
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if ($titulo === '') {
        echo json_encode(['success' => false, 'error' => 'El título es requerido']);
        exit;
    }
    
    // Subir archivo
    $file = $_FILES['archivo'];
    $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
        exit;
    }
    
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'error' => 'El archivo es demasiado grande (máximo 10MB)']);
        exit;
    }
    
    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid('doc_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'error' => 'Error al subir el archivo']);
        exit;
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Eliminar la observación de sin movimiento
        $deleteObs = $conn->prepare("DELETE FROM observaciones_sin_movimiento WHERE item_id = ? AND mes = ? AND ano = ?");
        $deleteObs->bind_param('iii', $item_id, $mes, $ano);
        $deleteObs->execute();
        
        // 2. Crear el documento
        $insertDoc = $conn->prepare("INSERT INTO documentos (item_id, usuario_id, titulo, descripcion, archivo, estado, fecha_subida) VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())");
        $insertDoc->bind_param('iisss', $item_id, $user_id, $titulo, $descripcion, $filename);
        $insertDoc->execute();
        $documento_id = $conn->insert_id;
        
        // 3. Crear entrada en documento_seguimiento (si la tabla existe)
        $checkTableSeguimiento = $conn->query("SHOW TABLES LIKE 'documento_seguimiento'");
        if ($checkTableSeguimiento->num_rows > 0) {
            $insertSeg = $conn->prepare("INSERT INTO documento_seguimiento (documento_id, mes, ano, fecha_envio) VALUES (?, ?, ?, NOW())");
            $insertSeg->bind_param('iii', $documento_id, $mes, $ano);
            $insertSeg->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Documento cargado exitosamente. La declaración de Sin Movimiento ha sido reemplazada.',
            'documento_id' => $documento_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        // Eliminar archivo si hubo error
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        echo json_encode(['success' => false, 'error' => 'Error al procesar: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
