<?php
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
    $item_id = (int)($_POST['item_id'] ?? 0);
    $mes_carga = (int)($_POST['mes_carga'] ?? 0);
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
    $ano_actual = (int)date('Y');
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
        $_SESSION['error'] = 'Faltan datos requeridos (item, título o archivo)';
        header('Location: dashboard.php');
        exit;
    }
    
    // Procesar archivo primero
    $documento = new Documento($db_conn);
    $uploadResult = $documento->uploadFile($_FILES['archivo']);
    
    if (isset($uploadResult['error'])) {
        $_SESSION['error'] = 'Error al cargar el documento: ' . $uploadResult['error'];
        header('Location: dashboard.php?mes=' . $mes_carga_calc . '&ano=' . $ano_actual);
        exit;
    }
    
    // Ahora crear el documento con el nombre de archivo y mes/año de período
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
        $checkObs = $db_conn->prepare("
            SELECT id FROM observaciones_documentos 
            WHERE item_id = ? AND mes = ? AND ano = ? AND cargador_id = ? AND resuelta = 0
        ");
        $checkObs->bind_param('iiii', $item_id, $mes_carga_calc, $ano_actual, $user_id);
        $checkObs->execute();
        $obsResult = $checkObs->get_result();
        $observacionResuelta = false;
        
        if ($obsResult->num_rows > 0) {
            // Hay observaciones pendientes - marcarlas como resueltas
            $resolverObs = $db_conn->prepare("
                UPDATE observaciones_documentos 
                SET resuelta = 1, fecha_resolucion = NOW()
                WHERE item_id = ? AND mes = ? AND ano = ? AND cargador_id = ? AND resuelta = 0
            ");
            $resolverObs->bind_param('iiii', $item_id, $mes_carga_calc, $ano_actual, $user_id);
            $resolverObs->execute();
            $observacionResuelta = true;
        }
        $checkObs->close();
        // ────────────────────────────────────────────────────────────────────

        // Si es modificación, eliminar el documento anterior
        if ($doc_id_reemplazar > 0) {
            $docAnterior = new Documento($db_conn);
            $docAnterior->delete($doc_id_reemplazar);
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
        $_SESSION['error'] = 'Error al cargar el documento. Verifique que el formato sea correcto (PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG)';
        // Redirigir con mes y año para mantener el contexto
        header('Location: dashboard.php?mes=' . $mes_carga . '&ano=' . $ano_actual);
        exit;
    }
}

// Si no es POST, redirigir
header('Location: dashboard.php');
exit;
?>
