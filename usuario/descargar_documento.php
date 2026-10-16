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
$perfil = isset($_SESSION['profile']) ? $_SESSION['profile'] : '';

if ($perfil === 'publicador' || $perfil === 'auditor' || $perfil === 'revisor' || $perfil === 'administrativo') {
    // Publicador, Auditor, Revisor y Administrativo pueden ver TODOS los documentos
    $sql = "SELECT d.*
            FROM documentos d
            WHERE d.id = ?";
    
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $doc_id);
} else {
    // Los cargadores pueden descargar documentos de cualquier usuario cuando
    // tienen permiso de carga sobre el mismo item. El autor conserva acceso
    // aunque su asignacion haya cambiado posteriormente.
    $sql = "SELECT d.*
            FROM documentos d
            WHERE d.id = ?
              AND (
                    d.usuario_id = ?
                    OR EXISTS (
                        SELECT 1
                        FROM item_usuarios iu
                        WHERE iu.item_id = d.item_id
                          AND iu.usuario_id = ?
                    )
              )";
    
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("iii", $doc_id, $_SESSION['user_id'], $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();
$documento = $result->fetch_assoc();
$stmt->close();

if (!$documento) {
    $_SESSION['error'] = 'Documento no encontrado o no tienes permiso';
    header('Location: dashboard.php');
    exit;
}

// El publicador recibe siempre la versión final vigente del revisor cuando
// existe. Si no existe (o la actualización aún no está instalada), recibe el
// archivo original de la unidad.
$nombreDescargaPreferido = null;
$solicitaVersionFinal = isset($_GET['version_final']) && $_GET['version_final'] === '1';
$puedeVerVersionFinal = in_array($perfil, ['publicador', 'revisor', 'auditor', 'administrativo'], true);
if ($puedeVerVersionFinal && ($perfil === 'publicador' || $solicitaVersionFinal)) {
    $checkVersiones = $db_conn->query("SHOW TABLES LIKE 'documentos_versiones_revisor'");
    if ($checkVersiones && $checkVersiones->num_rows > 0) {
        $stmtVersion = $db_conn->prepare("SELECT archivo, nombre_original
            FROM documentos_versiones_revisor
            WHERE documento_id = ? AND es_vigente = 1
            ORDER BY numero_version DESC, id DESC LIMIT 1");
        $stmtVersion->bind_param('i', $doc_id);
        $stmtVersion->execute();
        $versionFinal = $stmtVersion->get_result()->fetch_assoc();
        $stmtVersion->close();
        if ($versionFinal) {
            $documento['archivo'] = $versionFinal['archivo'];
            $nombreDescargaPreferido = $versionFinal['nombre_original'];
        }
    }
}

// Ruta segura del archivo
$rutaRelativa = str_replace('\\', '/', trim((string)$documento['archivo']));
$rutaRelativa = ltrim($rutaRelativa, '/');
if ($rutaRelativa === '' || preg_match('#(^|/)\.\.(/|$)#', $rutaRelativa)) {
    $_SESSION['error'] = 'Ruta de archivo no válida';
    header('Location: dashboard.php');
    exit;
}
$archivo = dirname(__DIR__) . '/uploads/' . $rutaRelativa;

if (!file_exists($archivo)) {
    $_SESSION['error'] = 'Archivo no encontrado en el servidor';
    header('Location: dashboard.php');
    exit;
}

// Descargar el archivo
header('Content-Type: application/octet-stream');
$nombreDescarga = $nombreDescargaPreferido ?: basename($rutaRelativa);
$nombreAlternativo = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreDescarga);
header('Content-Disposition: attachment; filename="' . $nombreAlternativo . '"; filename*=UTF-8\'\'' . rawurlencode($nombreDescarga));
header('Content-Length: ' . filesize($archivo));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($archivo);
$db_conn->close();
exit;
?>
