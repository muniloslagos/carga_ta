<?php
session_start();
require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/config/config.php';

// Verificar autenticación básica
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$doc_id = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;

if (!$doc_id) {
    $_SESSION['error'] = 'Documento no válido';
    header('Location: dashboard.php');
    exit;
}

// Conectar a BD
$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar que el documento pertenece al usuario actual
$sql = "SELECT d.*, ds.estado 
        FROM documentos d
        LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
        WHERE d.id = ? AND d.usuario_id = ?";

$stmt = $db_conn->prepare($sql);
$stmt->bind_param("ii", $doc_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$documento = $result->fetch_assoc();
$stmt->close();

if (!$documento) {
    $_SESSION['error'] = 'Documento no encontrado o no tienes permiso';
    header('Location: dashboard.php');
    exit;
}

// Ruta segura del archivo
$archivo = dirname(__DIR__) . '/uploads/' . $documento['archivo'];

if (!file_exists($archivo)) {
    $_SESSION['error'] = 'Archivo no encontrado en el servidor';
    header('Location: dashboard.php');
    exit;
}

// Descargar el archivo
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($documento['archivo']) . '"');
header('Content-Length: ' . filesize($archivo));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($archivo);
$db_conn->close();
exit;
?>
