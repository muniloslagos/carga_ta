<?php
/**
 * Endpoint AJAX para observar documentos (solo publicadores)
 */

header('Content-Type: application/json');

require_once '../includes/check_auth.php';
require_once '../classes/CorreoManager.php';

// Solo publicadores pueden observar documentos
if ($_SESSION['profile'] !== 'publicador') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'No tiene permisos para observar documentos'
    ]);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    $documento_id = intval($input['documento_id'] ?? 0);
    $observacion = trim($input['observacion'] ?? '');
    
    // Validaciones
    if ($documento_id <= 0) {
        throw new Exception('ID de documento inválido');
    }
    
    if (empty($observacion)) {
        throw new Exception('Debe ingresar una observación');
    }
    
    if (strlen($observacion) < 10) {
        throw new Exception('La observación debe tener al menos 10 caracteres');
    }
    
    // Obtener información del documento
    $stmt = $db->getConnection()->prepare("
        SELECT d.id, d.item_id, d.usuario_id, d.mes_carga, d.ano_carga, d.titulo, d.estado,
               i.nombre as item_nombre, i.numeracion,
               u.nombre as cargador_nombre, u.email as cargador_email
        FROM documentos d
        INNER JOIN items_transparencia i ON d.item_id = i.id
        INNER JOIN usuarios u ON d.usuario_id = u.id
        WHERE d.id = ?
    ");
    $stmt->bind_param('i', $documento_id);
    $stmt->execute();
    $documento = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$documento) {
        throw new Exception('Documento no encontrado');
    }
    
    // Validar que no sea un documento placeholder
    if (stripos($documento['titulo'], 'Sin Movimiento') !== false) {
        throw new Exception('No se pueden observar documentos de "Sin Movimiento"');
    }
    
    // Validar que el documento no esté ya rechazado/observado
    if ($documento['estado'] === 'rechazado') {
        throw new Exception('Este documento ya está observado');
    }
    
    // Iniciar transacción
    $db->getConnection()->begin_transaction();
    
    try {
        // 1. Cambiar estado del documento a 'rechazado'
        $stmt = $db->getConnection()->prepare("
            UPDATE documentos 
            SET estado = 'rechazado', 
                comentarios_revision = ?,
                revisado_por = ?,
                fecha_revision = NOW()
            WHERE id = ?
        ");
        $user_id = $_SESSION['user_id'];
        $stmt->bind_param('sii', $observacion, $user_id, $documento_id);
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar documento');
        }
        $stmt->close();
        
        // 2. Insertar registro en observaciones_documentos
        $stmt = $db->getConnection()->prepare("
            INSERT INTO observaciones_documentos 
            (documento_id, item_id, observado_por, cargador_id, observacion, mes, ano)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'iiiisii',
            $documento_id,
            $documento['item_id'],
            $user_id,
            $documento['usuario_id'],
            $observacion,
            $documento['mes_carga'],
            $documento['ano_carga']
        );
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar observación');
        }
        $observacion_id = $db->getConnection()->insert_id;
        $stmt->close();
        
        // 3. Registrar en logs
        $stmt = $db->getConnection()->prepare("
            INSERT INTO logs (usuario_id, accion, descripcion, ip_address)
            VALUES (?, 'observar_documento', ?, ?)
        ");
        $descripcion = "Observó documento ID {$documento_id} del ítem '{$documento['item_nombre']}' ({$documento['mes_carga']}/{$documento['ano_carga']})";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param('iss', $user_id, $descripcion, $ip);
        $stmt->execute();
        $stmt->close();
        
        // 4. Enviar correo al cargador
        $correo_manager = new CorreoManager();
        $correo_manager->enviarDocumentoObservado($observacion_id);
        
        // Commit de la transacción
        $db->getConnection()->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Documento observado correctamente. Se ha notificado al cargador por correo.',
            'observacion_id' => $observacion_id
        ]);
        
    } catch (Exception $e) {
        // Rollback en caso de error
        $db->getConnection()->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
