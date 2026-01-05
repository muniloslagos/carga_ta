<?php
// Cargar verificador (imagen)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/classes/Verificador.php';
require_once dirname(dirname(__DIR__)) . '/classes/Documento.php';

$publicador_id = $_SESSION['user_id'] ?? null;
$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db_conn->connect_error) {
    http_response_code(500);
    $_SESSION['error'] = 'Error de conexión a la base de datos';
    header('Location: index.php');
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento_id = (int)($_POST['documento_id'] ?? 0);
    $item_id = (int)($_POST['item_id'] ?? 0);
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    $comentarios = trim($_POST['comentarios'] ?? '');
    
    // Validaciones
    if (!$publicador_id || !$documento_id || !$item_id) {
        $_SESSION['error'] = 'Faltan datos requeridos';
        header('Location: index.php');
        exit;
    }
    
    if (!isset($_FILES['archivo_verificador'])) {
        $_SESSION['error'] = 'Debe cargar un archivo verificador';
        header('Location: index.php');
        exit;
    }
    
    // Procesar archivo
    $verificador = new Verificador($db_conn);
    $uploadResult = $verificador->uploadFile($_FILES['archivo_verificador']);
    
    if (isset($uploadResult['error'])) {
        $_SESSION['error'] = 'Error al cargar el verificador: ' . $uploadResult['error'];
        header('Location: index.php');
        exit;
    }
    
    // Obtener mes y año del documento
    $sql_doc = "SELECT ds.mes, ds.ano FROM documento_seguimiento ds 
                WHERE ds.documento_id = ? LIMIT 1";
    $stmt = $db_conn->prepare($sql_doc);
    $stmt->bind_param("i", $documento_id);
    $stmt->execute();
    $result_doc = $stmt->get_result();
    $doc_data = $result_doc->fetch_assoc();
    
    if (!$doc_data) {
        $_SESSION['error'] = 'No se encontró información del documento';
        header('Location: index.php');
        exit;
    }
    
    $mes = $doc_data['mes'];
    $ano = $doc_data['ano'];
    $fecha_carga_portal = date('Y-m-d H:i:s');
    
    // Crear verificador
    $resultado = $verificador->create([
        'documento_id' => $documento_id,
        'item_id' => $item_id,
        'usuario_id' => $usuario_id,
        'publicador_id' => $publicador_id,
        'archivo_verificador' => $uploadResult['filename'],
        'fecha_carga_portal' => $fecha_carga_portal,
        'comentarios' => $comentarios
    ]);
    
    if ($resultado) {
        // Actualizar fecha_carga_portal en documento_seguimiento
        $verificador->actualizarFechaCargaPortal($documento_id, $mes, $ano);
        
        $_SESSION['success'] = 'Verificador cargado exitosamente';
        header('Location: index.php?mes=' . $mes . '&ano=' . $ano);
        exit;
    } else {
        $_SESSION['error'] = 'Error al guardar el verificador';
        header('Location: index.php');
        exit;
    }
}

// Si no es POST, redirigir
header('Location: index.php');
exit;
?>
