<?php
require_once 'includes/header.php';

// Si está autenticado, redirigir al dashboard correspondiente
if ($is_logged_in) {
    if ($current_profile === 'administrativo') {
        header('Location: admin/index.php');
    } else {
        header('Location: usuario/dashboard.php');
    }
    exit;
}
?>

<div class="container mt-5">
    <div class="row align-items-center min-vh-100">
        <div class="col-lg-6">
            <h1 class="display-4 mb-4">
                <i class="bi bi-shield-check" style="color: #3498db;"></i>
                Administración de Carga Unificada y Control de Transparencia
            </h1>
            <p class="lead text-muted mb-4">
                Una solución integral para la gestión y control de información de transparencia en instituciones públicas.
            </p>
            
            <div class="d-flex gap-2 mb-5">
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                </a>
                <a href="setup.php" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-gear"></i> Configurar BD
                </a>
            </div>

            <h5 class="mb-3">Características principales:</h5>
            <ul class="list-unstyled">
                <li class="mb-2">
                    <i class="bi bi-check-circle text-success"></i> 
                    Gestión de usuarios con 4 perfiles diferentes
                </li>
                <li class="mb-2">
                    <i class="bi bi-check-circle text-success"></i> 
                    Control de items de transparencia enumerados
                </li>
                <li class="mb-2">
                    <i class="bi bi-check-circle text-success"></i> 
                    Asignación de usuarios a items
                </li>
                <li class="mb-2">
                    <i class="bi bi-check-circle text-success"></i> 
                    Carga y gestión de documentos
                </li>
                <li class="mb-2">
                    <i class="bi bi-check-circle text-success"></i> 
                    Sistema de revisión y aprobación
                </li>
                <li class="mb-2">
                    <i class="bi bi-check-circle text-success"></i> 
                    Soporte para múltiples periodicidades
                </li>
            </ul>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h4 class="mb-0">Información de Acceso</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle"></i> Primera vez aquí?</strong>
                        <p class="mt-2 mb-0">Haga clic en "Configurar BD" para crear la base de datos e insertar el usuario administrativo por defecto.</p>
                    </div>

                    <h6 class="mt-4 mb-3">Credenciales de prueba (después de setup):</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td class="font-monospace">admin@cumplimiento.local</td>
                            </tr>
                            <tr>
                                <td><strong>Contraseña:</strong></td>
                                <td class="font-monospace">admin123</td>
                            </tr>
                        </table>
                    </div>

                    <hr>

                    <h6 class="mb-3">Perfiles de usuario:</h6>
                    <div class="mb-2">
                        <small><strong>👤 Administrativo</strong> - Acceso completo al sistema</small>
                    </div>
                    <div class="mb-2">
                        <small><strong>👥 Director Revisor</strong> - Revisión de documentos</small>
                    </div>
                    <div class="mb-2">
                        <small><strong>📤 Cargador de Información</strong> - Carga de documentos</small>
                    </div>
                    <div class="mb-2">
                        <small><strong>🌐 Publicador</strong> - Publicación de información</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
