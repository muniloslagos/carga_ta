<?php
session_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/Database.php';

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

// Conectar a BD usando la clase Database del sistema
$db = new Database();
$db_conn = $db->getConnection();

// Verificar permisos según el perfil
$perfil = isset($_SESSION['perfil']) ? $_SESSION['perfil'] : '';

if ($perfil === 'publicador') {
    // Publicador puede ver TODOS los documentos
    $sql = "SELECT d.*
            FROM documentos d
            WHERE d.id = ?";
    
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $doc_id);
} else {
    // Otros perfiles solo pueden ver sus propios documentos
    $sql = "SELECT d.*
            FROM documentos d
            WHERE d.id = ? AND d.usuario_id = ?";
    
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("ii", $doc_id, $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();
$documento = $result->fetch_assoc();
$stmt->close();

if (!$documento) {
    // Debug: mostrar el error directamente
    die("ERROR: Documento no encontrado. doc_id=$doc_id, user_id=" . $_SESSION['user_id'] . ", perfil=" . $perfil);
}

// Ruta segura del archivo
$archivo = dirname(__DIR__) . '/uploads/' . $documento['archivo'];

if (!file_exists($archivo)) {
    // Debug: mostrar ruta del archivo
    die("ERROR: Archivo no encontrado. Ruta: $archivo");
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
