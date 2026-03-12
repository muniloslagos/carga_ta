<?php
/**
 * Funciones de Módulos
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Obtener todos los módulos
 */
function getModulos($soloActivos = false, $usuarioId = null) {
    $params = [];
    $sql = "SELECT m.*, u.nombre_completo as usuario_nombre 
            FROM modulos m 
            LEFT JOIN usuarios u ON m.usuario_id = u.id";
    
    $conditions = [];
    if ($soloActivos) {
        $conditions[] = "m.activo = 1";
    }
    if ($usuarioId !== null) {
        $conditions[] = "m.usuario_id = ?";
        $params[] = $usuarioId;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY m.numero ASC";
    return fetchAll($sql, $params);
}

/**
 * Verificar si un usuario tiene permiso sobre un módulo
 */
function usuarioTienePermisoModulo($usuarioId, $moduloId) {
    $modulo = fetchOne(
        "SELECT id, usuario_id FROM modulos WHERE id = ?",
        [$moduloId]
    );
    
    if (!$modulo) {
        return false;
    }
    
    // Si el módulo no tiene usuario asignado, cualquier girador puede usarlo
    if ($modulo['usuario_id'] === null) {
        return true;
    }
    
    // Verificar que el usuario coincida
    return $modulo['usuario_id'] == $usuarioId;
}

/**
 * Obtener módulo por ID
 */
function getModuloById($id) {
    return fetchOne(
        "SELECT m.*, u.nombre_completo as usuario_nombre 
         FROM modulos m 
         LEFT JOIN usuarios u ON m.usuario_id = u.id 
         WHERE m.id = ?", 
        [$id]
    );
}

/**
 * Obtener módulo por número
 */
function getModuloByNumero($numero) {
    return fetchOne("SELECT * FROM modulos WHERE numero = ?", [$numero]);
}

/**
 * Actualizar módulo
 */
function actualizarModulo($id, $nombreFuncionario, $activo, $usuarioId = null) {
    return update(
        "UPDATE modulos SET nombre_funcionario = ?, activo = ?, usuario_id = ? WHERE id = ?",
        [$nombreFuncionario, $activo, $usuarioId, $id]
    );
}

/**
 * Cambiar estado del módulo
 */
function cambiarEstadoModulo($id, $estado) {
    $estados = ['disponible', 'ocupado', 'pausado', 'inactivo'];
    if (!in_array($estado, $estados)) {
        return ['success' => false, 'message' => 'Estado no válido'];
    }
    update("UPDATE modulos SET estado = ? WHERE id = ?", [$estado, $id]);
    return ['success' => true];
}

/**
 * Obtener categorías de un módulo
 */
function getCategoriasModulo($moduloId) {
    return fetchAll(
        "SELECT c.* FROM categorias c 
         INNER JOIN modulo_categorias mc ON c.id = mc.categoria_id 
         WHERE mc.modulo_id = ? AND c.activa = 1
         ORDER BY c.orden ASC",
        [$moduloId]
    );
}

/**
 * Asignar categorías a un módulo
 */
function asignarCategoriasModulo($moduloId, $categoriasIds) {
    // Eliminar asignaciones anteriores
    update("DELETE FROM modulo_categorias WHERE modulo_id = ?", [$moduloId]);
    
    // Insertar nuevas asignaciones
    foreach ($categoriasIds as $catId) {
        insert(
            "INSERT INTO modulo_categorias (modulo_id, categoria_id) VALUES (?, ?)",
            [$moduloId, $catId]
        );
    }
    return ['success' => true];
}

/**
 * Obtener módulos que atienden una categoría
 */
function getModulosPorCategoria($categoriaId) {
    return fetchAll(
        "SELECT m.* FROM modulos m 
         INNER JOIN modulo_categorias mc ON m.id = mc.modulo_id 
         WHERE mc.categoria_id = ? AND m.activo = 1 AND m.estado = 'disponible'
         ORDER BY m.numero ASC",
        [$categoriaId]
    );
}
