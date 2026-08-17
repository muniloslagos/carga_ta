<?php
/**
 * Reemplaza un documento existente por una declaración "Sin Movimiento".
 * Conserva el documento anterior en el historial y resuelve sus observaciones.
 */

require_once '../includes/check_auth.php';
require_login();
require_once '../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$usuarioId = (int)($_SESSION['user_id'] ?? 0);
$perfil = $_SESSION['profile'] ?? '';
$itemId = (int)($_POST['item_id'] ?? 0);
$mes = (int)($_POST['mes'] ?? 0);
$ano = (int)($_POST['ano'] ?? 0);
$documentoId = (int)($_POST['documento_id'] ?? 0);
$observacion = trim($_POST['observacion'] ?? '');

if (!$usuarioId || !$itemId || !$documentoId || $mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100 || $observacion === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos o el período no es válido']);
    exit;
}

if ($perfil !== 'cargador_informacion') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo un cargador puede realizar esta corrección']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$transaccionIniciada = false;

try {
    // El cargador debe estar asignado al ítem y el documento debe pertenecerle.
    $validar = $conn->prepare("
        SELECT d.id, d.estado, d.mes_carga, d.ano_carga, i.nombre AS item_nombre
        FROM documentos d
        INNER JOIN items_transparencia i ON i.id = d.item_id
        INNER JOIN item_usuarios iu ON iu.item_id = d.item_id AND iu.usuario_id = ?
        WHERE d.id = ? AND d.item_id = ? AND d.usuario_id = ?
        LIMIT 1
    ");
    $validar->bind_param('iiii', $usuarioId, $documentoId, $itemId, $usuarioId);
    $validar->execute();
    $documento = $validar->get_result()->fetch_assoc();
    $validar->close();

    if (!$documento) {
        throw new Exception('El documento no existe o no tiene permisos para corregirlo');
    }
    if ($documento['mes_carga'] !== null && (int)$documento['mes_carga'] !== $mes) {
        throw new Exception('El documento no corresponde al mes seleccionado');
    }
    if ($documento['ano_carga'] !== null && (int)$documento['ano_carga'] !== $ano) {
        throw new Exception('El documento no corresponde al año seleccionado');
    }

    $checkVerificador = $conn->prepare("SELECT id FROM verificadores_publicador WHERE documento_id = ? LIMIT 1");
    $checkVerificador->bind_param('i', $documentoId);
    $checkVerificador->execute();
    if ($checkVerificador->get_result()->num_rows > 0) {
        throw new Exception('No se puede modificar porque el documento ya fue publicado');
    }
    $checkVerificador->close();

    $conn->begin_transaction();
    $transaccionIniciada = true;

    // Evitar más de una declaración para el mismo ítem y período.
    $eliminarDeclaracionAnterior = $conn->prepare("DELETE FROM observaciones_sin_movimiento WHERE item_id = ? AND mes = ? AND ano = ?");
    $eliminarDeclaracionAnterior->bind_param('iii', $itemId, $mes, $ano);
    $eliminarDeclaracionAnterior->execute();
    $eliminarDeclaracionAnterior->close();

    $insertarDeclaracion = $conn->prepare("
        INSERT INTO observaciones_sin_movimiento (item_id, usuario_id, mes, ano, observacion)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertarDeclaracion->bind_param('iiiis', $itemId, $usuarioId, $mes, $ano, $observacion);
    if (!$insertarDeclaracion->execute()) {
        throw new Exception('No se pudo guardar la declaración Sin Movimiento');
    }
    $insertarDeclaracion->close();

    $titulo = 'Sin Movimiento - ' . $documento['item_nombre'];
    $descripcion = 'Documento placeholder para Sin Movimiento. Observación: ' . $observacion;
    $archivo = 'sin_movimiento_placeholder_' . uniqid() . '.txt';
    $insertarPlaceholder = $conn->prepare("
        INSERT INTO documentos
            (item_id, usuario_id, titulo, descripcion, archivo, mes_carga, ano_carga, estado, fecha_subida)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
    ");
    $insertarPlaceholder->bind_param('iisssii', $itemId, $usuarioId, $titulo, $descripcion, $archivo, $mes, $ano);
    if (!$insertarPlaceholder->execute()) {
        throw new Exception('No se pudo crear el registro Sin Movimiento');
    }
    $placeholderId = $conn->insert_id;
    $insertarPlaceholder->close();

    $checkSeguimiento = $conn->query("SHOW TABLES LIKE 'documento_seguimiento'");
    if ($checkSeguimiento && $checkSeguimiento->num_rows > 0) {
        $insertarSeguimiento = $conn->prepare("
            INSERT INTO documento_seguimiento (documento_id, item_id, usuario_id, mes, ano, fecha_envio)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insertarSeguimiento->bind_param('iiiii', $placeholderId, $itemId, $usuarioId, $mes, $ano);
        $insertarSeguimiento->execute();
        $insertarSeguimiento->close();
    }

    // El documento corregido no se elimina: queda como reemplazado en el historial.
    $marcarReemplazado = $conn->prepare("UPDATE documentos SET estado = 'reemplazado' WHERE id = ?");
    $marcarReemplazado->bind_param('i', $documentoId);
    $marcarReemplazado->execute();
    $marcarReemplazado->close();

    $checkObservaciones = $conn->query("SHOW TABLES LIKE 'observaciones_documentos'");
    if ($checkObservaciones && $checkObservaciones->num_rows > 0) {
        $resolver = $conn->prepare("
            UPDATE observaciones_documentos
            SET resuelta = 1, fecha_resolucion = NOW()
            WHERE item_id = ? AND mes = ? AND ano = ?
              AND cargador_id = ? AND resuelta = 0
        ");
        $resolver->bind_param('iiii', $itemId, $mes, $ano, $usuarioId);
        $resolver->execute();
        $resolver->close();
    }

    $conn->commit();
    $transaccionIniciada = false;
    echo json_encode([
        'success' => true,
        'message' => 'Documento corregido como Sin Movimiento. La observación fue resuelta.',
        'documento_id' => $placeholderId
    ]);
} catch (Throwable $e) {
    if ($transaccionIniciada) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
