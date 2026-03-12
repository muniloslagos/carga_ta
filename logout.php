<?php
/**
 * Logout - Sistema de Numeración
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/usuarios.php';

cerrarSesion();
header('Location: ' . BASE_URL . '/login.php');
exit;
