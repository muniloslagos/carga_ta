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
require_once '../classes/CorreoManager.php';

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

    if (!Revisor::requiereRevision($documento_id, $conn)) {
        $_SESSION['mensaje_error'] = 'Este documento pertenece a un item por ocurrencia y no requiere revision previa.';
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
        
        $conn->begin_transaction();
        try {
            $stmtDocumento = $conn->prepare("
                SELECT d.item_id, d.usuario_id, d.mes_carga, d.ano_carga
                FROM documentos d
                WHERE d.id = ?
                LIMIT 1
            ");
            $stmtDocumento->bind_param('i', $documento_id);
            $stmtDocumento->execute();
            $documento = $stmtDocumento->get_result()->fetch_assoc();
            $stmtDocumento->close();

            if (!$documento) {
                throw new Exception('Documento no encontrado');
            }

            $resultado = $revisorClass->observar($documento_id, $revisor_id, $observaciones);
            if (!isset($resultado['success']) || !$resultado['success']) {
                throw new Exception($resultado['error'] ?? 'Error al observar el documento');
            }

            // Registrar la observacion en el flujo comun para que sea visible
            // en la bitacora, el panel del cargador y las correcciones.
            $stmtObservacion = $conn->prepare("
                INSERT INTO observaciones_documentos
                    (documento_id, item_id, observado_por, cargador_id, observacion, mes, ano)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtObservacion->bind_param(
                'iiiisii',
                $documento_id,
                $documento['item_id'],
                $revisor_id,
                $documento['usuario_id'],
                $observaciones,
                $documento['mes_carga'],
                $documento['ano_carga']
            );
            if (!$stmtObservacion->execute()) {
                throw new Exception('Error al registrar la observacion para el cargador');
            }
            $observacion_id = (int)$conn->insert_id;
            $stmtObservacion->close();

            $sql_log = "INSERT INTO logs (usuario_id, accion, ip_address) VALUES (?, 'Documento observado por revisor', ?)";
            $stmt_log = $conn->prepare($sql_log);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt_log->bind_param('is', $revisor_id, $ip);
            $stmt_log->execute();
            $stmt_log->close();

            $conn->commit();
            $_SESSION['mensaje_success'] = 'Documento observado exitosamente. El cargador debera corregirlo.';

            // La observacion queda guardada aunque el servidor de correo falle.
            try {
                $correoManager = new CorreoManager();
                $correoManager->enviarDocumentoObservado($observacion_id, 'revisor');
                $_SESSION['mensaje_success'] .= ' Se notifico al cargador por correo.';
            } catch (Throwable $correoError) {
                $_SESSION['mensaje_error'] = 'La observacion fue registrada, pero no se pudo enviar el correo al cargador: ' . $correoError->getMessage();
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $_SESSION['mensaje_error'] = $e->getMessage();
        }
        
    } else {
        $_SESSION['mensaje_error'] = 'Acción no válida';
    }
}

// Redirigir de vuelta al dashboard
header('Location: dashboard_revisor.php' . ($_GET['ano'] ?? '') . ($_GET['mes'] ?? '') . ($_GET['estado'] ?? ''));
exit;
