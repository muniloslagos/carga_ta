<?php
/**
 * Eliminar Verificador - Permite retrotraer un documento a estado "Cargado"
 * Solo para publicadores que necesitan corregir una verificación
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/classes/Verificador.php';
require_once dirname(dirname(__DIR__)) . '/classes/Documento.php';
require_once dirname(dirname(__DIR__)) . '/classes/Historial.php';

// Validar autenticación del publicador
$publicador_id = $_SESSION['user_id'] ?? null;
$current_profile = $_SESSION['perfil'] ?? null;

// Solo publicadores y administrativos pueden eliminar verificadores
if (!$publicador_id || ($current_profile !== 'publicador' && $current_profile !== 'administrativo')) {
    http_response_code(403);
    $_SESSION['error'] = 'No tiene permisos para eliminar verificadores.';
    header('Location: index.php');
    exit;
}

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db_conn->connect_error) {
    http_response_code(500);
    $_SESSION['error'] = 'Error de conexión a la base de datos';
    header('Location: index.php');
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$verificador_id = (int)($_POST['verificador_id'] ?? 0);
$motivo = trim($_POST['motivo_eliminacion'] ?? 'Sin motivo especificado');

// Validaciones
if (!$verificador_id) {
    $_SESSION['error'] = 'ID de verificador no válido';
    header('Location: index.php');
    exit;
}

// Obtener información del verificador antes de eliminarlo
$verificadorClass = new Verificador($db_conn);
$verificador = $verificadorClass->getById($verificador_id);

if (!$verificador) {
    $_SESSION['error'] = 'Verificador no encontrado';
    header('Location: index.php');
    exit;
}

$documento_id = $verificador['documento_id'];
$archivo_eliminado = $verificador['archivo_verificador'];

// Eliminar verificador (esto también retrotraerá el documento a "Cargado")
if ($verificadorClass->delete($verificador_id)) {
    
    // Registrar en historial
    $historialClass = new Historial($db_conn);
    $historialClass->registrar([
        'documento_id' => $documento_id,
        'usuario_id' => $publicador_id,
        'tipo' => 'verificador_eliminado',
        'descripcion' => 'Verificador eliminado y documento retrotraído a "Cargado"',
        'detalle' => 'Archivo: ' . $archivo_eliminado . ' | Motivo: ' . $motivo
    ]);
    
    $_SESSION['success'] = 'Verificador eliminado correctamente. El documento ha sido retrotraído a estado "Cargado".';
} else {
    $_SESSION['error'] = 'Error al eliminar el verificador. Intente nuevamente.';
}

header('Location: index.php');
exit;
