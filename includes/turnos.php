<?php
/**
 * Funciones de Turnos
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/categorias.php';
require_once __DIR__ . '/modulos.php';

/**
 * Emitir nuevo turno
 */
function emitirTurno($categoriaId) {
    $categoria = getCategoriaById($categoriaId);
    if (!$categoria || !$categoria['activa']) {
        return ['success' => false, 'message' => 'Categoría no válida o inactiva'];
    }
    
    $siguiente = getSiguienteNumero($categoriaId);
    
    $turnoId = insert(
        "INSERT INTO turnos (numero, numero_completo, categoria_id, fecha, hora_emision, estado) 
         VALUES (?, ?, ?, CURDATE(), CURTIME(), 'esperando')",
        [$siguiente['numero'], $siguiente['numero_completo'], $categoriaId]
    );
    
    return [
        'success' => true,
        'turno_id' => $turnoId,
        'numero' => $siguiente['numero'],
        'numero_completo' => $siguiente['numero_completo'],
        'categoria_nombre' => $siguiente['categoria_nombre'],
        'prefijo' => $siguiente['prefijo'],
        'hora' => date('H:i:s')
    ];
}

/**
 * Obtener siguiente turno en espera para un módulo
 */
function getSiguienteTurnoParaModulo($moduloId) {
    // Obtener categorías que atiende este módulo
    $categorias = getCategoriasModulo($moduloId);
    if (empty($categorias)) {
        return null;
    }
    
    $categoriasIds = array_column($categorias, 'id');
    $placeholders = implode(',', array_fill(0, count($categoriasIds), '?'));
    
    // Buscar el turno más antiguo en espera de esas categorías
    $turno = fetchOne(
        "SELECT t.*, c.nombre as categoria_nombre, c.prefijo, c.color as categoria_color
         FROM turnos t 
         INNER JOIN categorias c ON t.categoria_id = c.id
         WHERE t.estado = 'esperando' 
         AND t.categoria_id IN ($placeholders)
         AND t.fecha = CURDATE()
         ORDER BY t.id ASC
         LIMIT 1",
        $categoriasIds
    );
    
    return $turno;
}

/**
 * Llamar turno (desde módulo)
 */
function llamarTurno($moduloId, $turnoId = null, $numeroManual = null, $categoriaIdManual = null) {
    $modulo = getModuloById($moduloId);
    if (!$modulo || !$modulo['activo']) {
        return ['success' => false, 'message' => 'Módulo no válido o inactivo'];
    }
    
    // Obtener categorías del módulo separadas por tipo
    $categoriasModulo = getCategoriasModulo($moduloId);
    $categoriasAutomaticas = [];
    $categoriasManuales = [];
    
    foreach ($categoriasModulo as $cat) {
        if (($cat['tipo_numeracion'] ?? 'automatica') === 'manual') {
            $categoriasManuales[] = $cat;
        } else {
            $categoriasAutomaticas[] = $cat;
        }
    }
    
    $turno = null;
    
    // Primero intentar con categorías automáticas (cola de espera)
    if (!empty($categoriasAutomaticas)) {
        if ($turnoId === null) {
            $turno = getSiguienteTurnoParaModulo($moduloId);
        } else {
            $turno = fetchOne(
                "SELECT t.*, c.nombre as categoria_nombre, c.prefijo, c.color as categoria_color
                 FROM turnos t 
                 INNER JOIN categorias c ON t.categoria_id = c.id
                 WHERE t.id = ?",
                [$turnoId]
            );
        }
        
        if ($turno) {
            $turnoId = $turno['id'];
            // Actualizar turno existente
            update(
                "UPDATE turnos SET estado = 'llamado', modulo_id = ?, hora_llamado = CURTIME() WHERE id = ?",
                [$moduloId, $turnoId]
            );
        }
    }
    
    // Si no hay turno automático, usar categoría manual (correlativo con letras)
    if (!$turno && !empty($categoriasManuales)) {
        // Usar la primera categoría manual del módulo (o la especificada)
        $categoria = $categoriaIdManual ? getCategoriaById($categoriaIdManual) : $categoriasManuales[0];
        
        if (!$categoria) {
            return ['success' => false, 'message' => 'Categoría no válida'];
        }
        
        // Usar el sistema global de letras + números
        $siguiente = getSiguienteNumeroGlobal();
        
        $turnoId = insert(
            "INSERT INTO turnos (numero, numero_completo, categoria_id, modulo_id, fecha, hora_emision, hora_llamado, estado) 
             VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), CURTIME(), 'llamado')",
            [$siguiente['numero'], $siguiente['numero_completo'], $categoria['id'], $moduloId]
        );
        
        $turno = [
            'id' => $turnoId,
            'numero' => $siguiente['numero'],
            'numero_completo' => $siguiente['numero_completo'],
            'categoria_id' => $categoria['id'],
            'categoria_nombre' => $categoria['nombre'],
            'categoria_color' => $categoria['color'],
            'prefijo' => $categoria['prefijo'] ?? ''
        ];
    }
    
    // Si aún no hay turno, no hay nada que llamar
    if (!$turno) {
        return ['success' => false, 'message' => 'No hay turnos en espera'];
    }
    
    // Actualizar estado del módulo
    cambiarEstadoModulo($moduloId, 'ocupado');
    
    // Registrar en llamados actuales
    update("DELETE FROM llamados_actuales WHERE modulo_id = ?", [$moduloId]);
    insert(
        "INSERT INTO llamados_actuales (turno_id, modulo_id, categoria_id, numero_completo, nombre_funcionario) 
         VALUES (?, ?, ?, ?, ?)",
        [$turnoId, $moduloId, $turno['categoria_id'], $turno['numero_completo'], $modulo['nombre_funcionario']]
    );
    
    // Agregar al historial
    insert(
        "INSERT INTO historial_llamados (turno_id, modulo_id, categoria_id, numero_completo, nombre_funcionario) 
         VALUES (?, ?, ?, ?, ?)",
        [$turnoId, $moduloId, $turno['categoria_id'], $turno['numero_completo'], $modulo['nombre_funcionario']]
    );
    
    return [
        'success' => true,
        'turno_id' => $turnoId,
        'numero_completo' => $turno['numero_completo'],
        'categoria_nombre' => $turno['categoria_nombre'],
        'categoria_color' => $turno['categoria_color'],
        'modulo_numero' => $modulo['numero'],
        'funcionario_nombre' => $modulo['nombre_funcionario']
    ];
}

/**
 * Re-llamar último turno
 */
function rellamarTurno($moduloId) {
    $llamadoActual = fetchOne(
        "SELECT la.*, c.nombre as categoria_nombre, c.color as categoria_color, m.numero as modulo_numero
         FROM llamados_actuales la
         INNER JOIN categorias c ON la.categoria_id = c.id
         INNER JOIN modulos m ON la.modulo_id = m.id
         WHERE la.modulo_id = ? AND la.activo = 1
         ORDER BY la.id DESC LIMIT 1",
        [$moduloId]
    );
    
    if (!$llamadoActual) {
        return ['success' => false, 'message' => 'No hay turno para re-llamar'];
    }
    
    // Agregar al historial como re-llamado
    insert(
        "INSERT INTO historial_llamados (turno_id, modulo_id, categoria_id, numero_completo, nombre_funcionario) 
         VALUES (?, ?, ?, ?, ?)",
        [$llamadoActual['turno_id'], $moduloId, $llamadoActual['categoria_id'], 
         $llamadoActual['numero_completo'], $llamadoActual['nombre_funcionario']]
    );
    
    return [
        'success' => true,
        'turno_id' => $llamadoActual['turno_id'],
        'numero_completo' => $llamadoActual['numero_completo'],
        'categoria_nombre' => $llamadoActual['categoria_nombre'],
        'categoria_color' => $llamadoActual['categoria_color'],
        'modulo_numero' => $llamadoActual['modulo_numero'],
        'funcionario_nombre' => $llamadoActual['nombre_funcionario'],
        'es_rellamado' => true
    ];
}

/**
 * Finalizar atención de turno
 */
function finalizarTurno($moduloId, $estado = 'atendido') {
    $llamadoActual = fetchOne(
        "SELECT * FROM llamados_actuales WHERE modulo_id = ? AND activo = 1 ORDER BY id DESC LIMIT 1",
        [$moduloId]
    );
    
    if (!$llamadoActual) {
        return ['success' => false, 'message' => 'No hay turno activo'];
    }
    
    // Actualizar turno
    update(
        "UPDATE turnos SET estado = ?, hora_atencion_fin = CURTIME() WHERE id = ?",
        [$estado, $llamadoActual['turno_id']]
    );
    
    // Desactivar llamado actual
    update("UPDATE llamados_actuales SET activo = 0 WHERE id = ?", [$llamadoActual['id']]);
    
    // Cambiar estado del módulo a disponible
    cambiarEstadoModulo($moduloId, 'disponible');
    
    return ['success' => true];
}

/**
 * Obtener turnos en espera
 */
function getTurnosEnEspera($categoriaId = null) {
    $sql = "SELECT t.*, c.nombre as categoria_nombre, c.prefijo, c.color
            FROM turnos t 
            INNER JOIN categorias c ON t.categoria_id = c.id
            WHERE t.estado = 'esperando' AND t.fecha = CURDATE()";
    $params = [];
    
    if ($categoriaId) {
        $sql .= " AND t.categoria_id = ?";
        $params[] = $categoriaId;
    }
    
    $sql .= " ORDER BY t.id ASC";
    
    return fetchAll($sql, $params);
}

/**
 * Obtener llamados actuales (para pantalla)
 */
function getLlamadosActuales($categoriasIds = null) {
    $sql = "SELECT la.*, c.nombre as categoria_nombre, c.color as categoria_color, m.numero as modulo_numero
            FROM llamados_actuales la
            INNER JOIN categorias c ON la.categoria_id = c.id
            INNER JOIN modulos m ON la.modulo_id = m.id
            WHERE la.activo = 1";
    $params = [];
    
    if ($categoriasIds && !empty($categoriasIds)) {
        $placeholders = implode(',', array_fill(0, count($categoriasIds), '?'));
        $sql .= " AND la.categoria_id IN ($placeholders)";
        $params = $categoriasIds;
    }
    
    $sql .= " ORDER BY la.created_at DESC";
    
    return fetchAll($sql, $params);
}

/**
 * Obtener historial de llamados (para pantalla)
 */
function getHistorialLlamados($limite = 4, $categoriasIds = null) {
    $sql = "SELECT hl.*, c.nombre as categoria_nombre, c.color as categoria_color, m.numero as modulo_numero
            FROM historial_llamados hl
            INNER JOIN categorias c ON hl.categoria_id = c.id
            INNER JOIN modulos m ON hl.modulo_id = m.id
            WHERE 1=1";
    $params = [];
    
    if ($categoriasIds && !empty($categoriasIds)) {
        $placeholders = implode(',', array_fill(0, count($categoriasIds), '?'));
        $sql .= " AND hl.categoria_id IN ($placeholders)";
        $params = $categoriasIds;
    }
    
    $sql .= " ORDER BY hl.id DESC LIMIT ?";
    $params[] = (int)$limite;
    
    return fetchAll($sql, $params);
}

/**
 * Obtener último llamado (para detectar cambios en pantalla)
 */
function getUltimoLlamado($categoriasIds = null) {
    $sql = "SELECT hl.*, c.nombre as categoria_nombre, c.color as categoria_color, m.numero as modulo_numero
            FROM historial_llamados hl
            INNER JOIN categorias c ON hl.categoria_id = c.id
            INNER JOIN modulos m ON hl.modulo_id = m.id
            WHERE 1=1";
    $params = [];
    
    if ($categoriasIds && !empty($categoriasIds)) {
        $placeholders = implode(',', array_fill(0, count($categoriasIds), '?'));
        $sql .= " AND hl.categoria_id IN ($placeholders)";
        $params = $categoriasIds;
    }
    
    $sql .= " ORDER BY hl.id DESC LIMIT 1";
    
    return fetchOne($sql, $params);
}

/**
 * Estadísticas del día
 */
function getEstadisticasDia($fecha = null) {
    $fecha = $fecha ?: date('Y-m-d');
    
    $stats = [
        'total_emitidos' => 0,
        'total_atendidos' => 0,
        'total_esperando' => 0,
        'por_categoria' => [],
        'por_modulo' => []
    ];
    
    // Totales
    $totales = fetchOne(
        "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'atendido' THEN 1 ELSE 0 END) as atendidos,
            SUM(CASE WHEN estado = 'esperando' THEN 1 ELSE 0 END) as esperando
         FROM turnos WHERE fecha = ?",
        [$fecha]
    );
    
    $stats['total_emitidos'] = $totales['total'] ?? 0;
    $stats['total_atendidos'] = $totales['atendidos'] ?? 0;
    $stats['total_esperando'] = $totales['esperando'] ?? 0;
    
    // Por categoría
    $stats['por_categoria'] = fetchAll(
        "SELECT c.nombre, c.color, COUNT(t.id) as total,
                SUM(CASE WHEN t.estado = 'atendido' THEN 1 ELSE 0 END) as atendidos,
                SUM(CASE WHEN t.estado = 'esperando' THEN 1 ELSE 0 END) as esperando
         FROM categorias c
         LEFT JOIN turnos t ON c.id = t.categoria_id AND t.fecha = ?
         WHERE c.activa = 1
         GROUP BY c.id
         ORDER BY c.orden",
        [$fecha]
    );
    
    // Por módulo
    $stats['por_modulo'] = fetchAll(
        "SELECT m.numero, m.nombre_funcionario, COUNT(t.id) as total_atendidos
         FROM modulos m
         LEFT JOIN turnos t ON m.id = t.modulo_id AND t.fecha = ? AND t.estado = 'atendido'
         WHERE m.activo = 1
         GROUP BY m.id
         ORDER BY m.numero",
        [$fecha]
    );
    
    return $stats;
}
