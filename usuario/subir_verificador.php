<?php
/**
 * Subir Verificador de Portal - Solo para publicadores
 */
require_once '../includes/check_auth.php';
require_login();

// Solo el publicador puede subir verificadores
if ($current_profile !== 'publicador' && $current_profile !== 'administrativo') {
    $_SESSION['error'] = 'No tiene permisos para subir verificadores.';
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

require_once '../classes/Verificador.php';

$conn = $db->getConnection();
$verificadorClass = new Verificador($conn);

$item_id      = intval($_POST['item_id'] ?? 0);
$documento_id = intval($_POST['documento_id'] ?? 0);
$fecha_carga  = trim($_POST['fecha_carga_portal'] ?? '');
$comentarios  = trim($_POST['comentarios'] ?? '');
$publicador_id = $current_user['id'];

// Validar campos
if (!$item_id || !$documento_id || !$fecha_carga) {
    $_SESSION['error'] = 'Faltan datos requeridos para subir el verificador.';
    header('Location: dashboard.php');
    exit;
}

// Validar que el documento exista
$checkDoc = $conn->prepare("SELECT id, usuario_id FROM documentos WHERE id = ? AND item_id = ?");
$checkDoc->bind_param("ii", $documento_id, $item_id);
$checkDoc->execute();
$doc = $checkDoc->get_result()->fetch_assoc();

if (!$doc) {
    $_SESSION['error'] = 'Documento no encontrado.';
    header('Location: dashboard.php');
    exit;
}

// Manejar archivo
if (empty($_FILES['archivo_verificador']['name'])) {
    $_SESSION['error'] = 'Debe seleccionar un archivo verificador.';
    header('Location: dashboard.php');
    exit;
}

$archivo = $_FILES['archivo_verificador'];
$extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $extensionesPermitidas)) {
    $_SESSION['error'] = 'Formato de archivo no permitido. Use: PDF, JPG, PNG.';
    header('Location: dashboard.php');
    exit;
}

if ($archivo['size'] > 10 * 1024 * 1024) {
    $_SESSION['error'] = 'El archivo supera el límite de 10MB.';
    header('Location: dashboard.php');
    exit;
}

// Guardar archivo
$uploadDir = dirname(__DIR__) . '/uploads/verificadores/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$nombreArchivo = 'verif_' . $item_id . '_' . $documento_id . '_' . time() . '.' . $ext;
$rutaDestino   = $uploadDir . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    $_SESSION['error'] = 'Error al guardar el archivo. Verifique permisos de la carpeta uploads/verificadores/.';
    header('Location: dashboard.php');
    exit;
}

// Crear verificador en BD
$data = [
    'documento_id'      => $documento_id,
    'item_id'           => $item_id,
    'usuario_id'        => $doc['usuario_id'],
    'publicador_id'     => $publicador_id,
    'archivo_verificador' => 'verificadores/' . $nombreArchivo,
    'fecha_carga_portal' => $fecha_carga,
    'comentarios'       => $comentarios
];

$verificadorId = $verificadorClass->create($data);

if ($verificadorId) {
    $_SESSION['success'] = 'Verificador subido correctamente. El documento ha sido marcado como Publicado.';

    // Registrar en historial si existe la clase y el método
    if (file_exists(dirname(__DIR__) . '/classes/Historial.php')) {
        require_once '../classes/Historial.php';
        $historial = new Historial($conn);
        if (method_exists($historial, 'registrar')) {
            $historial->registrar([
                'item_id'    => $item_id,
                'usuario_id' => $publicador_id,
                'tipo'       => 'verificador_subido',
                'descripcion'=> 'Verificador de portal subido',
                'detalle'    => 'Publicado el: ' . $fecha_carga . ($comentarios ? ' - ' . $comentarios : '')
            ]);
        }
    }
} else {
    // Borrar archivo si falló la BD
    @unlink($rutaDestino);
    $_SESSION['error'] = 'Error al registrar el verificador en la base de datos.';
}

header('Location: dashboard.php');
exit;
