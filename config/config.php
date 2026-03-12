<?php
/**
 * Configuración General
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria
date_default_timezone_set('America/Santiago');

// Rutas base
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/numeracion');

// Incluir conexión a BD
require_once BASE_PATH . '/config/database.php';

// Configuración del sistema
define('MAX_MODULOS', 10);
define('APP_NAME', 'Sistema de Numeración');
define('APP_VERSION', '1.0.0');

/**
 * Obtener configuración de la BD
 */
function getConfig($clave, $default = null) {
    $result = fetchOne("SELECT valor FROM configuracion WHERE clave = ?", [$clave]);
    return $result ? $result['valor'] : $default;
}

/**
 * Guardar configuración en la BD
 */
function setConfig($clave, $valor) {
    $exists = fetchOne("SELECT id FROM configuracion WHERE clave = ?", [$clave]);
    if ($exists) {
        update("UPDATE configuracion SET valor = ? WHERE clave = ?", [$valor, $clave]);
    } else {
        insert("INSERT INTO configuracion (clave, valor) VALUES (?, ?)", [$clave, $valor]);
    }
}

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verificar rol del usuario
 */
function hasRole($rol) {
    if (!isLoggedIn()) return false;
    if (is_array($rol)) {
        return in_array($_SESSION['user_rol'], $rol);
    }
    return $_SESSION['user_rol'] === $rol;
}

/**
 * Requerir login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Requerir rol específico
 */
function requireRole($rol) {
    requireLogin();
    if (!hasRole($rol)) {
        header('Location: ' . BASE_URL . '/login.php?error=unauthorized');
        exit;
    }
}

/**
 * Sanitizar entrada
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Respuesta JSON
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Mensaje flash
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
