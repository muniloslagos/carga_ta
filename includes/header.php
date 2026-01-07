<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración si no está disponible
if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Inicializar base de datos si no existe
if (!isset($db)) {
    require_once dirname(__DIR__) . '/config/Database.php';
    $db = new Database();
}

// Verificar si el usuario está autenticado
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $is_logged_in && isset($_SESSION['user']) ? $_SESSION['user'] : null;
$current_profile = $is_logged_in && isset($_SESSION['profile']) ? $_SESSION['profile'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <!-- Navbar Mejorada -->
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>" style="font-size: 0.95rem; letter-spacing: 0.3px;">
                <i class="bi bi-shield-check" style="color: #3498db; font-size: 1.2rem;"></i> 
                <span style="color: #ffffff;">Control de Transparencia</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if ($is_logged_in && $current_profile === 'administrativo'): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>admin/index.php">
                                <i class="bi bi-house-door"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>admin/usuarios/index.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>admin/direcciones/index.php">
                                <i class="bi bi-building"></i> Direcciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>admin/items/index.php">
                                <i class="bi bi-list-check"></i> Items
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>admin/documentos/index.php">
                                <i class="bi bi-file-earmark-text"></i> Documentos
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <?php if ($is_logged_in && $current_user): ?>
                        <li class="nav-item">
                            <span class="nav-link text-light" style="cursor: default;">
                                <i class="bi bi-person-circle" style="font-size: 1.2rem; color: #3498db;"></i> 
                                <strong><?php echo htmlspecialchars($current_user['nombre'] ?? 'Usuario'); ?></strong>
                                <br>
                                <small style="color: #95a5a6; font-size: 0.85rem;"><?php echo $PROFILES[$current_profile] ?? $current_profile; ?></small>
                            </span>
                        </li>
                        <li class="nav-item">
                            <div class="vr" style="height: 2rem; opacity: 0.3;"></div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>logout.php" style="transition: all 0.3s;">
                                <i class="bi bi-box-arrow-right" style="color: #e74c3c;"></i> Salir
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Línea separadora -->
    <div style="border-bottom: 2px solid rgba(255, 255, 255, 0.2); margin: 0;"></div>

    <div class="container-fluid">
