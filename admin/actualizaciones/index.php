<?php
require_once '../../includes/check_auth.php';
require_login();
require_role('administrativo');
require_once '../../classes/SystemUpdater.php';
require_once '../../config/version.php';

$conn = $db->getConnection();
$updater = new SystemUpdater($conn);
$mensaje = '';
$error = '';

if (empty($_SESSION['csrf_actualizaciones'])) {
    $_SESSION['csrf_actualizaciones'] = bin2hex(random_bytes(32));
}

try {
    // La infraestructura base se crea únicamente al visitar este módulo.
    $updater->reconcileExisting();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar_actualizacion'])) {
        if (!hash_equals($_SESSION['csrf_actualizaciones'], (string)($_POST['csrf_token'] ?? ''))) {
            throw new Exception('La sesión expiró. Recargue la página e inténtelo nuevamente.');
        }
        $codigo = trim((string)($_POST['codigo'] ?? ''));
        $mensaje = $updater->install($codigo, (int)$_SESSION['user_id']);
        $_SESSION['csrf_actualizaciones'] = bin2hex(random_bytes(32));
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$updates = $updater->getUpdates();
$pendientes = $updater->pendingCount();

require_once '../../includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-arrow-repeat"></i> Actualizaciones del Sistema</h1>
    <p class="text-white-50 mb-0">Versión de archivos: <?php echo htmlspecialchars(APP_VERSION); ?></p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="alert <?php echo $pendientes ? 'alert-warning' : 'alert-success'; ?>">
    <?php if ($pendientes): ?>
        <strong><i class="bi bi-bell-fill"></i> <?php echo $pendientes; ?> actualización(es) pendiente(s).</strong>
        Estas actualizaciones llegaron con los archivos desplegados y requieren aplicar cambios en la base de datos.
    <?php else: ?>
        <strong><i class="bi bi-check-circle-fill"></i> El sistema está actualizado.</strong>
        No existen cambios de base de datos pendientes para esta versión.
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Actualizaciones incluidas en esta versión</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Actualización</th>
                        <th>Versión</th>
                        <th>Base de datos</th>
                        <th>Estado</th>
                        <th>Instalación</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($updates as $update):
                    $aplicada = $updater->isApplied($update);
                    $registro = $updater->getRecord($update['codigo']);
                ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($update['nombre']); ?></strong>
                            <div class="small text-muted"><?php echo htmlspecialchars($update['descripcion']); ?></div>
                            <code class="small"><?php echo htmlspecialchars($update['codigo']); ?></code>
                        </td>
                        <td><?php echo htmlspecialchars($update['version']); ?></td>
                        <td><span class="badge bg-warning text-dark">Requerida</span></td>
                        <td>
                            <?php if ($aplicada): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Instalada</span>
                            <?php elseif ($registro && $registro['estado'] === 'error'): ?>
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Requiere reparación</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass"></i> Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if ($registro): ?>
                                <?php echo date('d/m/Y H:i', strtotime($registro['fecha_instalacion'])); ?><br>
                                <?php echo htmlspecialchars($registro['instalado_por_nombre'] ?? 'Detectada automáticamente'); ?>
                                <?php if (!empty($registro['detalle'])): ?><div class="text-muted"><?php echo htmlspecialchars($registro['detalle']); ?></div><?php endif; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if (!$aplicada): ?>
                                <form method="POST" onsubmit="return confirm('¿Instalar esta actualización? Se modificará la estructura de la base de datos. Se recomienda contar con un respaldo reciente.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_actualizaciones']); ?>">
                                    <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($update['codigo']); ?>">
                                    <button type="submit" name="instalar_actualizacion" class="btn btn-primary btn-sm">
                                        <i class="bi bi-database-add"></i> Instalar
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-outline-success btn-sm" disabled><i class="bi bi-check"></i> Aplicada</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($updates)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Esta versión no contiene actualizaciones de base de datos.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <strong>Importante:</strong> este módulo funciona igual si los archivos se instalaron mediante Git, ZIP, FTP o el Administrador de archivos de cPanel. No ejecuta <code>git pull</code> ni elimina archivos de <code>uploads/</code>.
</div>

<?php require_once '../../includes/footer.php'; ?>

