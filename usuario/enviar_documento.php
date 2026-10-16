<?php
// Diagnóstico de cargas: escribe directamente en /carga_ta/error_log.
// Si no se registra "solicitud_recibida", la conexión fue interrumpida antes de ejecutar PHP.
$uploadLogFile = dirname(__DIR__) . '/error_log';
$uploadRequestId = date('YmdHis') . '-' . bin2hex(random_bytes(4));
$uploadStage = 'inicio';

function registrarDiagnosticoCarga($evento, array $datos = []) {
    global $uploadLogFile, $uploadRequestId, $uploadStage;

    $registro = array_merge([
        'fecha' => date('c'),
        'id' => $uploadRequestId,
        'evento' => $evento,
        'etapa' => $uploadStage,
    ], $datos);

    $linea = '[UPLOAD] ' . json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if (@error_log($linea, 3, $uploadLogFile) === false) {
        error_log(trim($linea));
    }
}

ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('error_log', $uploadLogFile);

register_shutdown_function(function () {
    global $uploadStage;
    $ultimoError = error_get_last();
    $datos = [
        'memoria_pico' => memory_get_peak_usage(true),
        'ultimo_error' => $ultimoError,
    ];
    registrarDiagnosticoCarga('fin_solicitud', $datos);
});

$contenidoDeclarado = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
registrarDiagnosticoCarga('solicitud_recibida', [
    'metodo' => $_SERVER['REQUEST_METHOD'] ?? '',
    'content_length' => $contenidoDeclarado,
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_input_time' => ini_get('max_input_time'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'archivo_presente' => isset($_FILES['archivo']),
    'archivo_error' => isset($_FILES['archivo']) ? (int)$_FILES['archivo']['error'] : null,
    'archivo_size' => isset($_FILES['archivo']) ? (int)$_FILES['archivo']['size'] : null,
]);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $contenidoDeclarado > 0 && empty($_POST) && empty($_FILES)) {
    registrarDiagnosticoCarga('post_vacio', [
        'detalle' => 'PHP recibió el cuerpo vacío; normalmente CONTENT_LENGTH supera post_max_size o falló el directorio temporal.',
    ]);
}

// PRIMERO: Procesar formulario ANTES de salida
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación manualmente
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Documento.php';
require_once '../classes/ItemPlazo.php';
require_once '../classes/Item.php';
require_once '../classes/PlazoCalculator.php';

$uploadStage = 'conexion_bd';
$user_id = $_SESSION['user_id'] ?? null;
$db = new Database();
$db_conn = $db->getConnection();

if ($db_conn->connect_error) {
    http_response_code(500);
    $_SESSION['error'] = 'Error de conexión a la base de datos';
    header('Location: dashboard.php');
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadStage = 'validaciones';
    $item_id = (int)($_POST['item_id'] ?? 0);
    $mes_carga = (int)($_POST['mes_carga'] ?? 0);
    $ano_carga = (int)($_POST['ano_carga'] ?? date('Y'));
    $doc_id_reemplazar = (int)($_POST['doc_id_reemplazar'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Obtener periodicidad del item
    $itemClass = new Item($db_conn);
    $item = $itemClass->getById($item_id);
    $periodicidad = $item['periodicidad'] ?? null;
    
    // Validar que el usuario está asignado al item (solo para cargadores)
    if ($_SESSION['profile'] === 'cargador_informacion') {
        $checkAssignment = $db_conn->prepare("SELECT COUNT(*) as count FROM item_usuarios WHERE item_id = ? AND usuario_id = ?");
        $checkAssignment->bind_param('ii', $item_id, $user_id);
        $checkAssignment->execute();
        $result = $checkAssignment->get_result()->fetch_assoc();
        
        if ($result['count'] == 0) {
            $_SESSION['error'] = 'No tiene permisos para cargar documentos en este item';
            header('Location: dashboard.php');
            exit;
        }
    }
    
    // Calcular año/mes DESPUÉS de obtener mes_carga del POST
    $ano_actual = $ano_carga >= 2000 && $ano_carga <= 2100 ? $ano_carga : (int)date('Y');
    $mes_carga_calc = $mes_carga;
    
    // Para ANUAL, usar mes configurado del item
    if ($periodicidad === 'anual') {
        $mes_carga_calc = intval($item['mes_carga_anual'] ?? 1);
    } else if ($mes_carga == 0) {
        // Para otros, usar mes anterior
        $mes_carga_calc = (int)date('m') - 1;
        $ano_actual = (int)date('Y');
        if ($mes_carga_calc < 1) {
            $mes_carga_calc = 12;
            $ano_actual--;
        }
    }
    
    // Validaciones
    if (!$user_id) {
        $_SESSION['error'] = 'Error: Usuario no autenticado. Por favor inicia sesión nuevamente.';
        header('Location: ../login.php');
        exit;
    }
    
    if (!$item_id || !$titulo || !isset($_FILES['archivo'])) {
        registrarDiagnosticoCarga('datos_incompletos', [
            'item_id' => $item_id,
            'titulo_presente' => $titulo !== '',
            'archivo_presente' => isset($_FILES['archivo']),
        ]);
        $_SESSION['error'] = 'Faltan datos requeridos (item, título o archivo)';
        header('Location: dashboard.php');
        exit;
    }
    
    // Procesar archivo primero
    $uploadStage = 'guardar_archivo';
    registrarDiagnosticoCarga('procesando_archivo');
    $documento = new Documento($db_conn);
    $uploadResult = $documento->uploadFile($_FILES['archivo'], $item_id, $mes_carga_calc, $ano_actual);
    
    if (isset($uploadResult['error'])) {
        registrarDiagnosticoCarga('error_guardar_archivo', ['detalle' => $uploadResult['error']]);
        $_SESSION['error'] = 'Error al cargar el documento: ' . $uploadResult['error'];
        header('Location: dashboard.php?mes=' . $mes_carga_calc . '&ano=' . $ano_actual);
        exit;
    }
    
    // Ahora crear el documento con el nombre de archivo y mes/año de período
    $uploadStage = 'registrar_documento';
    $resultado = $documento->create([
        'usuario_id' => $user_id,
        'item_id' => $item_id,
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'archivo' => $uploadResult['filename'],
        'mes_carga' => $mes_carga_calc,
        'ano_carga' => $ano_actual
    ]);
    
    if ($resultado) {
        $uploadStage = 'documento_registrado';
        registrarDiagnosticoCarga('carga_exitosa', ['documento_id' => (int)$resultado]);
        // ── Calcular y guardar cumplimiento del plazo de envío ──────────────
        $itemPlazoClass = new ItemPlazo($db_conn);
        $plazoEnvio = $itemPlazoClass->getPlazoFinal($item_id, $ano_actual, $mes_carga_calc, $periodicidad);
        if ($plazoEnvio) {
            $cumple = (strtotime(date('Y-m-d')) <= strtotime($plazoEnvio)) ? 1 : 0;
            $upd = $db_conn->prepare("UPDATE documentos SET cumple_plazo_envio = ? WHERE id = ?");
            $upd->bind_param("ii", $cumple, $resultado);
            $upd->execute();
        }
        // ────────────────────────────────────────────────────────────────────
        
        // ── Resolver observaciones pendientes si las hay ────────────────────
        $observacionResuelta = false;
        $doc_id_observado = null;
        
        $checkTableObs = $db_conn->query("SHOW TABLES LIKE 'observaciones_documentos'");
        if ($checkTableObs && $checkTableObs->num_rows > 0) {
            $checkObs = $db_conn->prepare("
                SELECT id, documento_id FROM observaciones_documentos 
                WHERE item_id = ? AND mes = ? AND ano = ? AND cargador_id = ? AND resuelta = 0
            ");
            $checkObs->bind_param('iiii', $item_id, $mes_carga_calc, $ano_actual, $user_id);
            $checkObs->execute();
            $obsResult = $checkObs->get_result();
            
            if ($obsResult->num_rows > 0) {
                $obsData = $obsResult->fetch_assoc();
                $doc_id_observado = $obsData['documento_id'];
                
                // Marcar observaciones como resueltas
                $resolverObs = $db_conn->prepare("
                    UPDATE observaciones_documentos 
                    SET resuelta = 1, fecha_resolucion = NOW()
                    WHERE item_id = ? AND mes = ? AND ano = ? AND cargador_id = ? AND resuelta = 0
                ");
                $resolverObs->bind_param('iiii', $item_id, $mes_carga_calc, $ano_actual, $user_id);
                $resolverObs->execute();
                $observacionResuelta = true;
            }
            
            // Cambiar estado del documento observado a 'reemplazado' (NO eliminarlo, para mantener historial)
            if ($doc_id_observado) {
                $cambiarEstadoObs = $db_conn->prepare("
                    UPDATE documentos 
                    SET estado = 'reemplazado'
                    WHERE id = ?
                ");
                $cambiarEstadoObs->bind_param('i', $doc_id_observado);
                $cambiarEstadoObs->execute();
            }
            
            $checkObs->close();
        }
        // ────────────────────────────────────────────────────────────────────

        // Si es modificación (NO observación), conservar la versión anterior
        // para que continúe disponible en el historial del período.
        if ($doc_id_reemplazar > 0 && !$observacionResuelta) {
            $marcarAnterior = $db_conn->prepare("
                UPDATE documentos
                SET estado = 'reemplazado'
                WHERE id = ? AND item_id = ?
            ");
            $marcarAnterior->bind_param('ii', $doc_id_reemplazar, $item_id);
            $marcarAnterior->execute();
            $marcarAnterior->close();
        }
        
        // Mensaje de éxito
        if ($observacionResuelta) {
            $_SESSION['success'] = 'Documento corregido y enviado exitosamente. Observación resuelta';
        } else if ($doc_id_reemplazar > 0) {
            $_SESSION['success'] = 'Documento modificado exitosamente';
        } else {
            $_SESSION['success'] = 'Documento cargado exitosamente';
        }
        
        // Redirigir con mes y año para mantener el contexto
        header('Location: dashboard.php?mes=' . $mes_carga_calc . '&ano=' . $ano_actual);
        exit;
    } else {
        if (!empty($uploadResult['filepath']) && is_file($uploadResult['filepath'])) {
            unlink($uploadResult['filepath']);
        }
        registrarDiagnosticoCarga('error_registrar_documento');
        $_SESSION['error'] = 'Error al cargar el documento. Verifique que el formato sea correcto (PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG, ZIP, RAR, 7Z) y que no supere los 100 MB';
        // Redirigir con mes y año para mantener el contexto
        header('Location: dashboard.php?mes=' . $mes_carga . '&ano=' . $ano_actual);
        exit;
    }
}

// Si no es POST, redirigir
header('Location: dashboard.php');
exit;
?>
