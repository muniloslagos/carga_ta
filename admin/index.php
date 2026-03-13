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

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
                <p class="text-muted">Resumen del día: <?= date('d/m/Y') ?></p>
            </div>
        </div>
        
        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Turnos Emitidos</h6>
                                <h2 class="card-title mb-0"><?= $stats['total_emitidos'] ?></h2>
                            </div>
                            <i class="bi bi-ticket-perforated display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Turnos Atendidos</h6>
                                <h2 class="card-title mb-0"><?= $stats['total_atendidos'] ?></h2>
                            </div>
                            <i class="bi bi-check-circle display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">En Espera</h6>
                                <h2 class="card-title mb-0"><?= $stats['total_esperando'] ?></h2>
                            </div>
                            <i class="bi bi-hourglass-split display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Por Categoría y Módulos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Por Categoría</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th class="text-center">Emitidos</th>
                                        <th class="text-center">Atendidos</th>
                                        <th class="text-center">Esperando</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['por_categoria'] as $cat): ?>
                                    <tr>
                                        <td>
                                            <span class="badge" style="background-color: <?= $cat['color'] ?>">&nbsp;</span>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </td>
                                        <td class="text-center"><?= $cat['total'] ?? 0 ?></td>
                                        <td class="text-center"><?= $cat['atendidos'] ?? 0 ?></td>
                                        <td class="text-center"><?= $cat['esperando'] ?? 0 ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pc-display me-2"></i>Por Módulo</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Módulo</th>
                                        <th>Funcionario</th>
                                        <th class="text-center">Atendidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['por_modulo'] as $mod): ?>
                                    <tr>
                                        <td>Módulo <?= $mod['numero'] ?></td>
                                        <td><?= htmlspecialchars($mod['nombre_funcionario'] ?? '-') ?></td>
                                        <td class="text-center"><?= $mod['total_atendidos'] ?? 0 ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cola de Espera -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ol me-2"></i>Cola de Espera Actual</h5>
                        <span class="badge bg-secondary"><?= count($turnosEspera) ?> en espera</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($turnosEspera)): ?>
                            <p class="text-muted text-center mb-0">No hay turnos en espera</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($turnosEspera as $turno): ?>
                                <div class="col-auto mb-2">
                                    <span class="badge fs-6 py-2 px-3" style="background-color: <?= $turno['color'] ?>">
                                        <?= $turno['numero_completo'] ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(() => location.reload(), 30000);
    </script>

<?php require_once '../includes/footer.php'; ?>
