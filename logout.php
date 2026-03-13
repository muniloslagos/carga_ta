<?php
/**
 * Logout - Sistema de Cumplimiento
 */

session_start();
require_once __DIR__ . '/config/config.php';

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: ' . SITE_URL . 'login.php');
exit;
