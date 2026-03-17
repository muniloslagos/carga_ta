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
    <link href="<?php echo SITE_URL; ?>css/style.css?v=<?php echo filemtime(dirname(__DIR__).'/css/style.css'); ?>" rel="stylesheet">
</head>
<body>
    <!-- Navbar Mejorada -->
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo SITE_URL; ?>" style="font-size: 1rem; letter-spacing: 0.5px;">
                <img src="https://muniloslagos.cl/wp-content/uploads/2025/02/logo_blanco2025.png" alt="Logo Municipalidad" style="height: 45px; margin-right: 15px;">
                <div>
                    <i class="bi bi-shield-check" style="color: #3498db; font-size: 1.3rem;"></i> 
                    <span style="color: #ffffff;">Transparencia Activa</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Nav links según perfil -->
                <ul class="navbar-nav me-auto">
                    <?php if ($is_logged_in && $current_profile === 'administrativo'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-light" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Administración
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/"><i class="bi bi-speedometer2"></i> Panel Admin</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/items/"><i class="bi bi-file-text"></i> Items</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/usuarios/"><i class="bi bi-people"></i> Usuarios</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/direcciones/"><i class="bi bi-building"></i> Direcciones</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/documentos/"><i class="bi bi-file-earmark"></i> Documentos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/publicador/"><i class="bi bi-check-circle"></i> Publicador</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/auditor/"><i class="bi bi-search"></i> Auditor</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>usuario/dashboard.php">
                                <i class="bi bi-house"></i> Mi Panel
                            </a>
                        </li>
                    <?php elseif ($is_logged_in && $current_profile === 'publicador'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>usuario/dashboard.php">
                                <i class="bi bi-house"></i> Mi Panel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>admin/publicador/">
                                <i class="bi bi-check-circle"></i> Publicación
                            </a>
                        </li>
                    <?php elseif ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>usuario/dashboard.php">
                                <i class="bi bi-house"></i> Mi Panel
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <?php if ($is_logged_in && $current_user): ?>
                        <li class="nav-item">
                            <span class="nav-link text-light" style="cursor: default; font-size: 0.9rem;">
                                <i class="bi bi-person-circle" style="color: #3498db;"></i>
                                <strong><?php echo htmlspecialchars($current_user['nombre'] ?? 'Usuario'); ?></strong>
                                <span class="badge ms-1" style="background:#3d5a7a; font-size:0.75rem;"><?php echo $PROFILES[$current_profile] ?? $current_profile; ?></span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo SITE_URL; ?>logout.php">
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

    <div class="container-fluid">
