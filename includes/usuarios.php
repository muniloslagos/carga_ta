<?php
/**
 * Funciones de Usuarios
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Obtener todos los usuarios
 */
function getUsuarios() {
    return fetchAll("SELECT id, username, nombre_completo, rol, activo, created_at FROM usuarios ORDER BY nombre_completo");
}

/**
 * Obtener usuario por ID
 */
function getUsuarioById($id) {
    return fetchOne("SELECT id, username, nombre_completo, rol, activo FROM usuarios WHERE id = ?", [$id]);
}

/**
 * Obtener usuario por username
 */
function getUsuarioByUsername($username) {
    return fetchOne("SELECT * FROM usuarios WHERE username = ?", [$username]);
}

/**
 * Crear usuario
 */
function crearUsuario($username, $password, $nombreCompleto, $rol = 'girador') {
    $existe = getUsuarioByUsername($username);
    if ($existe) {
        return ['success' => false, 'message' => 'El nombre de usuario ya existe'];
    }
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $id = insert(
        "INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES (?, ?, ?, ?)",
        [$username, $passwordHash, $nombreCompleto, $rol]
    );
    
    return ['success' => true, 'id' => $id];
}

/**
 * Actualizar usuario
 */
function actualizarUsuario($id, $nombreCompleto, $rol, $activo, $password = null) {
    if ($password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        update(
            "UPDATE usuarios SET nombre_completo = ?, rol = ?, activo = ?, password = ? WHERE id = ?",
            [$nombreCompleto, $rol, $activo, $passwordHash, $id]
        );
    } else {
        update(
            "UPDATE usuarios SET nombre_completo = ?, rol = ?, activo = ? WHERE id = ?",
            [$nombreCompleto, $rol, $activo, $id]
        );
    }
    return ['success' => true];
}

/**
 * Eliminar usuario
 */
function eliminarUsuario($id) {
    // Verificar que no sea el último admin
    $usuario = getUsuarioById($id);
    if ($usuario['rol'] === 'admin') {
        $admins = fetchOne("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin' AND id != ?", [$id]);
        if ($admins['total'] < 1) {
            return ['success' => false, 'message' => 'No puede eliminar el último administrador'];
        }
    }
    
    update("DELETE FROM usuarios WHERE id = ?", [$id]);
    return ['success' => true];
}

/**
 * Autenticar usuario
 */
function autenticar($username, $password) {
    $usuario = getUsuarioByUsername($username);
    
    if (!$usuario) {
        return ['success' => false, 'message' => 'Usuario no encontrado'];
    }
    
    if (!$usuario['activo']) {
        return ['success' => false, 'message' => 'Usuario desactivado'];
    }
    
    if (!password_verify($password, $usuario['password'])) {
        return ['success' => false, 'message' => 'Contraseña incorrecta'];
    }
    
    // Iniciar sesión
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_username'] = $usuario['username'];
    $_SESSION['user_nombre'] = $usuario['nombre_completo'];
    $_SESSION['user_rol'] = $usuario['rol'];
    
    return ['success' => true, 'usuario' => $usuario];
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    session_destroy();
    return ['success' => true];
}

/**
 * Cambiar contraseña
 */
function cambiarPassword($userId, $passwordActual, $passwordNueva) {
    $usuario = fetchOne("SELECT password FROM usuarios WHERE id = ?", [$userId]);
    
    if (!password_verify($passwordActual, $usuario['password'])) {
        return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
    }
    
    $passwordHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
    update("UPDATE usuarios SET password = ? WHERE id = ?", [$passwordHash, $userId]);
    
    return ['success' => true];
}
