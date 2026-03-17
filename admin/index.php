<?php
require_once dirname(__DIR__) . '/includes/check_auth.php';

// Verificar permisos - Solo administrativo
if ($current_profile !== 'administrativo') {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

require_once dirname(__DIR__) . '/includes/header.php';

$conn = $db->getConnection();

// Obtener estadísticas básicas
$totalUsuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1")->fetch_assoc()['total'];
$totalItems = $conn->query("SELECT COUNT(*) as total FROM items_transparencia WHERE activo = 1")->fetch_assoc()['total'];
$totalDirecciones = $conn->query("SELECT COUNT(*) as total FROM direcciones WHERE activa = 1")->fetch_assoc()['total'];
$totalDocumentos = $conn->query("SELECT COUNT(*) as total FROM documentos WHERE MONTH(fecha_subida) = MONTH(CURRENT_DATE) AND YEAR(fecha_subida) = YEAR(CURRENT_DATE)")->fetch_assoc()['total'];
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-speedometer2"></i> Panel Administrativo</h2>
            <p class="text-muted">Sistema de Transparencia Activa</p>
            <hr>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Usuarios Activos</h6>
                            <h2><?= $totalUsuarios ?></h2>
                        </div>
                        <i class="bi bi-people display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Items Activos</h6>
                            <h2><?= $totalItems ?></h2>
                        </div>
                        <i class="bi bi-list-check display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Direcciones</h6>
                            <h2><?= $totalDirecciones ?></h2>
                        </div>
                        <i class="bi bi-building display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Docs Este Mes</h6>
                            <h2><?= $totalDocumentos ?></h2>
                        </div>
                        <i class="bi bi-file-earmark-text display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menú de Opciones -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people display-1 text-primary"></i>
                    <h5 class="card-title mt-3">Gestión de Usuarios</h5>
                    <p class="card-text">Administrar usuarios del sistema</p>
                    <a href="usuarios/" class="btn btn-primary">Ir a Usuarios</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-list-check display-1 text-success"></i>
                    <h5 class="card-title mt-3">Gestión de Items</h5>
                    <p class="card-text">Administrar items de transparencia</p>
                    <a href="items/" class="btn btn-success">Ir a Items</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-building display-1 text-info"></i>
                    <h5 class="card-title mt-3">Direcciones</h5>
                    <p class="card-text">Administrar direcciones municipales</p>
                    <a href="direcciones/" class="btn btn-info">Ir a Direcciones</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-text display-1 text-warning"></i>
                    <h5 class="card-title mt-3">Documentos</h5>
                    <p class="card-text">Ver todos los documentos cargados</p>
                    <a href="documentos/" class="btn btn-warning">Ir a Documentos</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cloud-upload display-1 text-primary"></i>
                    <h5 class="card-title mt-3">Publicador</h5>
                    <p class="card-text">Publicar documentos en portal</p>
                    <a href="publicador/" class="btn btn-primary">Ir a Publicador</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clipboard-check display-1 text-success"></i>
                    <h5 class="card-title mt-3">Auditor</h5>
                    <p class="card-text">Auditoría de documentos</p>
                    <a href="auditor/" class="btn btn-success">Ir a Auditor</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
