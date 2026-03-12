<?php
/**
 * Página principal - Redirige al login o panel correspondiente
 */

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    switch ($_SESSION['user_rol']) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/');
            break;
        case 'girador':
            header('Location: ' . BASE_URL . '/girador/');
            break;
        case 'emisor':
            header('Location: ' . BASE_URL . '/emisor/');
            break;
    }
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
