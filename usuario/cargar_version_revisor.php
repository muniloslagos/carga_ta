<?php
require_once '../includes/check_auth.php';
require_login();

if ($current_profile !== 'revisor') {
    $_SESSION['mensaje_error'] = 'No tiene permisos para cargar versiones finales.';
    header('Location: dashboard.php');
    exit;
}

require_once '../classes/Revisor.php';
require_once '../classes/Documento.php';

$conn = $db->getConnection();
$revisorClass = new Revisor($conn);
$documentoClass = new Documento($conn);
$rutaGuardada = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Solicitud no válida.');
    }
    if (!hash_equals((string)($_SESSION['csrf_version_revisor'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
        throw new Exception('La sesión expiró. Recargue el panel e inténtelo nuevamente.');
    }
    if (!$revisorClass->versionesFinalesDisponibles()) {
        throw new Exception('La actualización de versiones finales debe ser instalada por un administrador.');
    }

    $documentoId = (int)($_POST['documento_id'] ?? 0);
    $comentarios = trim((string)($_POST['comentarios'] ?? ''));
    $documento = $documentoClass->getById($documentoId);
    if (!$documento || !Revisor::requiereRevision($documentoId, $conn)) {
        throw new Exception('El documento no existe o no admite revisión previa.');
    }
    if (empty($_FILES['archivo_final'])) {
        throw new Exception('Debe seleccionar el archivo final validado.');
    }

    $resultado = $documentoClass->uploadFile(
        $_FILES['archivo_final'],
        (int)$documento['item_id'],
        (int)$documento['mes_carga'],
        (int)$documento['ano_carga'],
        'revisiones'
    );
    if (empty($resultado['success'])) {
        throw new Exception($resultado['error'] ?? 'No se pudo guardar el archivo.');
    }
    $rutaGuardada = $resultado['filepath'];
    $hash = hash_file('sha256', $rutaGuardada) ?: null;

    $conn->begin_transaction();
    $revisorId = (int)$_SESSION['user_id'];
    $revisorClass->registrarVersionFinal(
        $documentoId,
        $revisorId,
        $resultado['filename'],
        basename(str_replace('\\', '/', (string)$_FILES['archivo_final']['name'])),
        $comentarios ?: null,
        $hash
    );
    if (!$revisorClass->aprobar($documentoId, $revisorId, $comentarios ?: 'Versión final validada por revisor')) {
        throw new Exception('No se pudo aprobar el documento.');
    }

    $checkObservaciones = $conn->query("SHOW TABLES LIKE 'observaciones_documentos'");
    if ($checkObservaciones && $checkObservaciones->num_rows > 0) {
        $stmtResolver = $conn->prepare("UPDATE observaciones_documentos
            SET resuelta = 1, fecha_resolucion = NOW()
            WHERE documento_id = ? AND resuelta = 0");
        $stmtResolver->bind_param('i', $documentoId);
        $stmtResolver->execute();
        $stmtResolver->close();
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmtLog = $conn->prepare("INSERT INTO logs (usuario_id, accion, descripcion, ip_address)
        VALUES (?, 'Versión final cargada por revisor', ?, ?)");
    $detalle = 'Documento ID ' . $documentoId . ' | Archivo: ' . $resultado['filename'];
    $stmtLog->bind_param('iss', $revisorId, $detalle, $ip);
    $stmtLog->execute();
    $stmtLog->close();
    $conn->commit();

    $_SESSION['csrf_version_revisor'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_success'] = 'Versión final cargada y aprobada. El publicador recibirá este archivo en lugar del original.';
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignorado) {}
    if ($rutaGuardada && is_file($rutaGuardada)) {
        unlink($rutaGuardada);
    }
    $_SESSION['mensaje_error'] = $e->getMessage();
}

header('Location: dashboard_revisor.php');
exit;

