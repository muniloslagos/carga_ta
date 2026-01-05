<?php
/**
 * Helper para verificación de autenticación y redirección
 * Este archivo debe incluirse ANTES de cualquier salida HTML
 */

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/Database.php';

// Inicializar base de datos
$db = new Database();

// Verificar si el usuario está autenticado
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $is_logged_in && isset($_SESSION['user']) ? $_SESSION['user'] : null;
$current_profile = $is_logged_in && isset($_SESSION['profile']) ? $_SESSION['profile'] : null;

/**
 * Función para requerir autenticación
 * Redirige a login si no está autenticado
 */
function require_login() {
    global $is_logged_in;
    if (!$is_logged_in) {
        header('Location: ' . SITE_URL . 'login.php');
        exit;
    }
}

/**
 * Función para requerir rol específico
 * Redirige a login si no tiene el rol requerido
 */
function require_role($role) {
    global $is_logged_in, $current_profile;
    
    if (!$is_logged_in) {
        header('Location: ' . SITE_URL . 'login.php');
        exit;
    }
    
    if ($current_profile !== $role) {
        header('Location: ' . SITE_URL . 'login.php');
        exit;
    }
}

/**
 * Función para redirigir si ya está autenticado
 * Usada en login.php para evitar que usuarios autenticados vean el formulario
 */
function redirect_if_logged_in() {
    global $is_logged_in, $current_profile;
    
    if ($is_logged_in && $current_profile) {
        if ($current_profile === 'administrativo') {
            header('Location: ' . SITE_URL . 'admin/index.php');
        } else {
            header('Location: ' . SITE_URL . 'usuario/dashboard.php');
        }
        exit;
    }
}

?>
