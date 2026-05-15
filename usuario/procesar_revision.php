<?php
/**
 * Procesar Revisión de Documentos
 * Maneja las acciones de aprobar y observar documentos por el revisor
 */

require_once '../includes/check_auth.php';
require_login();

// Solo revisor puede acceder
if ($current_profile !== 'revisor') {
    header('Location: ' . SITE_URL . 'usuario/dashboard.php');
    exit;
}

require_once '../classes/Revisor.php';

$conn = $db->getConnection();
$revisorClass = new Revisor($conn);

// Verificar que la revisión esté activada
if (!Revisor::estaActivado($conn)) {
    $_SESSION['mensaje_error'] = 'La funcionalidad de revisión no está activada';
    header('Location: dashboard_revisor.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $documento_id = (int)($_POST['documento_id'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');
    $revisor_id = $_SESSION['user_id'];
    
    if (!$documento_id) {
        $_SESSION['mensaje_error'] = 'ID de documento inválido';
        header('Location: dashboard_revisor.php');
        exit;
    }
    
    // Procesar según acción
    if ($accion === 'aprobar') {
        $resultado = $revisorClass->aprobar($documento_id, $revisor_id, $observaciones);
        
        if ($resultado) {
            $_SESSION['mensaje_success'] = 'Documento aprobado exitosamente';
            
            // Registrar en logs
            $sql_log = "INSERT INTO logs (usuario_id, accion, ip_address) VALUES (?, 'Documento aprobado por revisor', ?)";
            $stmt_log = $conn->prepare($sql_log);
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt_log->bind_param('is', $revisor_id, $ip);
            $stmt_log->execute();
        } else {
            $_SESSION['mensaje_error'] = 'Error al aprobar el documento';
        }
        
    } elseif ($accion === 'observar') {
        if (empty($observaciones)) {
            $_SESSION['mensaje_error'] = 'Las observaciones son obligatorias';
            header('Location: dashboard_revisor.php');
            exit;
        }
        
        $resultado = $revisorClass->observar($documento_id, $revisor_id, $observaciones);
        
        if (isset($resultado['success']) && $resultado['success']) {
            $_SESSION['mensaje_success'] = 'Documento observado exitosamente. El cargador deberá corregirlo.';
            
            // Registrar en logs
            $sql_log = "INSERT INTO logs (usuario_id, accion, ip_address) VALUES (?, 'Documento observado por revisor', ?)";
            $stmt_log = $conn->prepare($sql_log);
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt_log->bind_param('is', $revisor_id, $ip);
            $stmt_log->execute();
        } else {
            $_SESSION['mensaje_error'] = $resultado['error'] ?? 'Error al observar el documento';
        }
        
    } else {
        $_SESSION['mensaje_error'] = 'Acción no válida';
    }
}

// Redirigir de vuelta al dashboard
header('Location: dashboard_revisor.php' . ($_GET['ano'] ?? '') . ($_GET['mes'] ?? '') . ($_GET['estado'] ?? ''));
exit;
