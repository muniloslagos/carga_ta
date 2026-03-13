<?php
/**
 * Página principal - Redirige al login o panel correspondiente
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    // Usuario autenticado: redirigir según perfil
    $perfil = $_SESSION['profile'] ?? '';
    if ($perfil === 'administrativo' || $perfil === 'publicador') {
        header('Location: ' . SITE_URL . 'admin/index.php');
    } else {
        header('Location: ' . SITE_URL . 'usuario/dashboard.php');
    }
} else {
    // No autenticado: ir al login
    header('Location: ' . SITE_URL . 'login.php');
}
exit;
