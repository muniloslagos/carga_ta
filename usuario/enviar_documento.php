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
require_once '../classes/Documento.php';
require_once '../classes/ItemPlazo.php';
require_once '../classes/Item.php';

$user_id = $_SESSION['user_id'] ?? null;
$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

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
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Obtener periodicidad del item
    $itemClass = new Item($db_conn);
    $item = $itemClass->getById($item_id);
    $periodicidad = $item['periodicidad'] ?? null;
    
    // Calcular año/mes DESPUÉS de obtener mes_carga del POST
    $ano_actual = (int)date('Y');
    $mes_carga_calc = $mes_carga;
    
    // Para ANUAL, usar mes=1 (enero)
    if ($periodicidad === 'anual') {
        $mes_carga_calc = 1;
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
        // Log del error para debugging en producción
        error_log("Error upload documento: " . $uploadResult['error'] . " - User: $user_id - Item: $item_id");
        $_SESSION['error'] = 'Error al cargar el documento: ' . $uploadResult['error'];
        header('Location: dashboard.php?mes=' . $mes_carga_calc . '&ano=' . $ano_actual);
        exit;
    }
    
    // Verificar que el archivo realmente se guardó
    $filepath = $uploadResult['filepath'] ?? '';
    if (!file_exists($filepath)) {
        error_log("Error: Archivo no existe después de upload - Path: $filepath - User: $user_id");
        $_SESSION['error'] = 'Error: El archivo no se pudo guardar en el servidor. Verifique permisos de la carpeta uploads/';
        header('Location: dashboard.php?mes=' . $mes_carga_calc . '&ano=' . $ano_actual);
        exit;
    }
    
    // Ahora crear el documento con el nombre de archivo
    $resultado = $documento->create([
        'usuario_id' => $user_id,
        'item_id' => $item_id,
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'archivo' => $uploadResult['filename']  // ✅ SOLO EL NOMBRE
    ]);
    
    if ($resultado) {
        // Registrar en documento_seguimiento con estado 'Cargado'
        $fecha_envio = date('Y-m-d H:i:s');
        
        $sql_seguimiento = "INSERT INTO documento_seguimiento 
                          (documento_id, item_id, usuario_id, ano, mes, fecha_envio, estado, fecha_creacion)
                          VALUES (?, ?, ?, ?, ?, ?, 'Cargado', NOW())
                          ON DUPLICATE KEY UPDATE 
                          fecha_envio = ?, estado = 'Cargado'";
        
        $stmt = $db_conn->prepare($sql_seguimiento);
        $stmt->bind_param("iiiiiss", $resultado, $item_id, $user_id, $ano_actual, $mes_carga_calc, $fecha_envio, $fecha_envio);
        $stmt->execute();
        $stmt->close();
        
        // AGREGAR: Registrar en historial
        $usuario_nombre = $_SESSION['user']['nombre'] ?? 'Usuario';
        $item_nombre = $item['nombre'] ?? "Item #$item_id";
        $descripcion_hist = "Documento '$titulo' cargado";
        $detalle_hist = "Archivo: " . $uploadResult['filename'] . " | Mes: $mes_carga_calc | Año: $ano_actual";
        
        $sql_historial = "INSERT INTO historial 
                         (item_id, documento_id, usuario_id, tipo, descripcion, detalle, fecha)
                         VALUES (?, ?, ?, 'documento_cargado', ?, ?, NOW())";
        
        $stmt_hist = $db_conn->prepare($sql_historial);
        $stmt_hist->bind_param("iiiss", $item_id, $resultado, $user_id, $descripcion_hist, $detalle_hist);
        $stmt_hist->execute();
        $stmt_hist->close();
        
        // Log exitoso para debugging
        error_log("Documento cargado exitosamente - Doc ID: $resultado - User: $user_id - Item: $item_id - File: " . $uploadResult['filename']);
        
        $_SESSION['success'] = 'Documento cargado exitosamente';
        // Redirigir con mes y año para mantener el contexto
        header('Location: dashboard.php?mes=' . $mes_carga_calc . '&ano=' . $ano_actual);
        exit;
    } else {
        // Log del error
        error_log("Error al crear documento en BD - User: $user_id - Item: $item_id - File: " . $uploadResult['filename']);
        
        // Si falló la BD, eliminar el archivo subido
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        $_SESSION['error'] = 'Error al cargar el documento en la base de datos. Intente nuevamente.';
        // Redirigir con mes y año para mantener el contexto
        header('Location: dashboard.php?mes=' . $mes_carga . '&ano=' . $ano_actual);
        exit;
    }
}

// Si no es POST, redirigir
header('Location: dashboard.php');
exit;
?>
