<?php
/**
 * Funciones de Categorías
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Obtener todas las categorías
 */
function getCategorias($soloActivas = false, $tipoNumeracion = null) {
    $sql = "SELECT * FROM categorias WHERE 1=1";
    if ($soloActivas) {
        $sql .= " AND activa = 1";
    }
    if ($tipoNumeracion) {
        $sql .= " AND tipo_numeracion = '" . $tipoNumeracion . "'";
    }
    $sql .= " ORDER BY orden ASC, id ASC";
    return fetchAll($sql);
}

/**
 * Obtener categoría por ID
 */
function getCategoriaById($id) {
    return fetchOne("SELECT * FROM categorias WHERE id = ?", [$id]);
}

/**
 * Crear categoría
 */
function crearCategoria($nombre, $prefijo, $descripcion = '', $color = '#007bff', $tipoNumeracion = 'automatica') {
    $orden = fetchOne("SELECT MAX(orden) as max_orden FROM categorias")['max_orden'] + 1;
    
    // Si hay prefijo, convertir a mayúsculas, si no, dejar vacío
    $prefijo = !empty($prefijo) ? strtoupper($prefijo) : '';
    
    return insert(
        "INSERT INTO categorias (nombre, prefijo, descripcion, color, tipo_numeracion, orden) VALUES (?, ?, ?, ?, ?, ?)",
        [$nombre, $prefijo, $descripcion, $color, $tipoNumeracion, $orden]
    );
}

/**
 * Actualizar categoría
 */
function actualizarCategoria($id, $nombre, $prefijo, $descripcion, $color, $tipoNumeracion, $activa) {
    // Si hay prefijo, convertir a mayúsculas, si no, dejar vacío
    $prefijo = !empty($prefijo) ? strtoupper($prefijo) : '';
    
    return update(
        "UPDATE categorias SET nombre = ?, prefijo = ?, descripcion = ?, color = ?, tipo_numeracion = ?, activa = ? WHERE id = ?",
        [$nombre, $prefijo, $descripcion, $color, $tipoNumeracion, $activa, $id]
    );
}

/**
 * Activar/Desactivar categoría
 */
function toggleCategoria($id, $activa) {
    // Verificar que quede al menos una activa
    if (!$activa) {
        $activas = fetchOne("SELECT COUNT(*) as total FROM categorias WHERE activa = 1 AND id != ?", [$id]);
        if ($activas['total'] < 1) {
            return ['success' => false, 'message' => 'Debe haber al menos una categoría activa'];
        }
    }
    update("UPDATE categorias SET activa = ? WHERE id = ?", [$activa, $id]);
    return ['success' => true];
}

/**
 * Eliminar categoría
 */
function eliminarCategoria($id) {
    // Verificar que quede al menos una activa
    $activas = fetchOne("SELECT COUNT(*) as total FROM categorias WHERE activa = 1 AND id != ?", [$id]);
    $categoria = getCategoriaById($id);
    
    if ($categoria['activa'] && $activas['total'] < 1) {
        return ['success' => false, 'message' => 'No puede eliminar la única categoría activa'];
    }
    
    update("DELETE FROM categorias WHERE id = ?", [$id]);
    return ['success' => true];
}

/**
 * Resetear numeración de una categoría
 */
function resetearNumeracionCategoria($id) {
    update("UPDATE categorias SET numero_actual = 0 WHERE id = ?", [$id]);
    return ['success' => true];
}

/**
 * Resetear todas las numeraciones
 */
function resetearTodasNumeraciones() {
    update("UPDATE categorias SET numero_actual = 0");
    update("UPDATE turnos SET estado = 'cancelado' WHERE estado IN ('esperando', 'llamado')");
    update("DELETE FROM llamados_actuales");
    update("DELETE FROM historial_llamados");
    return ['success' => true];
}

/**
 * Obtener siguiente número global con sistema de letras
 */
function getSiguienteNumeroGlobal() {
    // Obtener configuración
    $letraMaxima = fetchOne("SELECT valor FROM configuracion WHERE clave = 'letra_maxima'")['valor'] ?? 'A';
    $numeroMaximo = (int)(fetchOne("SELECT valor FROM configuracion WHERE clave = 'numero_maximo'")['valor'] ?? 99);
    
    // Obtener estado actual
    $estado = fetchOne("SELECT * FROM numeracion_global ORDER BY id DESC LIMIT 1");
    if (!$estado) {
        // Crear registro inicial si no existe
        insert("INSERT INTO numeracion_global (letra_actual, numero_actual) VALUES ('A', 0)");
        $estado = ['letra_actual' => 'A', 'numero_actual' => 0];
    }
    
    $letraActual = $estado['letra_actual'];
    $numeroActual = $estado['numero_actual'];
    
    // Incrementar número
    $numeroActual++;
    
    // Si supera el máximo, pasar a la siguiente letra
    if ($numeroActual > $numeroMaximo) {
        $numeroActual = 1;
        $letraActual = chr(ord($letraActual) + 1);
        
        // Si supera la letra máxima, resetear a A
        if (ord($letraActual) > ord($letraMaxima)) {
            $letraActual = 'A';
        }
    }
    
    // Actualizar estado
    update("UPDATE numeracion_global SET letra_actual = ?, numero_actual = ? WHERE id = ?", 
           [$letraActual, $numeroActual, $estado['id']]);
    
    $numeroCompleto = $letraActual . $numeroActual;
    
    return [
        'numero' => $numeroActual,
        'letra' => $letraActual,
        'numero_completo' => $numeroCompleto
    ];
}

/**
 * Obtener siguiente número de una categoría
 */
function getSiguienteNumero($categoriaId) {
    $categoria = getCategoriaById($categoriaId);
    if (!$categoria) return null;
    
    // Usar sistema global de letras + números
    $siguiente = getSiguienteNumeroGlobal();
    
    return [
        'numero' => $siguiente['numero'],
        'letra' => $siguiente['letra'],
        'numero_completo' => $siguiente['numero_completo'],
        'prefijo' => $categoria['prefijo'],
        'categoria_nombre' => $categoria['nombre']
    ];
}
