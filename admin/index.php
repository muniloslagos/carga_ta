<?php
/**
 * Panel Administrador - Dashboard
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/turnos.php';
require_once dirname(__DIR__) . '/includes/categorias.php';
require_once dirname(__DIR__) . '/includes/modulos.php';

requireRole('admin');

$stats = getEstadisticasDia();
$categorias = getCategorias(true);
$modulos = getModulos(true);
$turnosEspera = getTurnosEnEspera();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador - Sistema de Numeración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
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
</body>
</html>
