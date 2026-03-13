<?php
// Mostrar verificador
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/classes/Verificador.php';
require_once dirname(dirname(__DIR__)) . '/classes/Usuario.php';

$verif_id = (int)($_GET['verif_id'] ?? 0);

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$verificadorClass = new Verificador($db_conn);
$usuarioClass = new Usuario($db_conn);

$verificador = $verificadorClass->getById($verif_id);

if (!$verificador) {
    echo '<p class="text-danger">Verificador no encontrado</p>';
    exit;
}

$publicador = $usuarioClass->getById($verificador['publicador_id']);
$archivoPath = dirname(dirname(__DIR__)) . '/uploads/' . $verificador['archivo_verificador'];
$esImagen = in_array(strtolower(pathinfo($verificador['archivo_verificador'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
?>

<div class="row">
    <div class="col-12">
        <h6>Información del Verificador</h6>
        <p>
            <strong>Cargado por:</strong> <?php echo htmlspecialchars($publicador['nombre'] ?? 'Desconocido'); ?><br>
            <strong>Fecha Carga Portal:</strong> <?php echo date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal'])); ?><br>
            <strong>Comentarios:</strong> <?php echo htmlspecialchars($verificador['comentarios'] ?? '-'); ?>
        </p>
    </div>
</div>

<!-- MOSTRAR IMAGEN EN LÍNEA -->
<?php if ($esImagen): ?>
    <div class="row mt-3">
        <div class="col-12">
            <h6>Verificador</h6>
            <div style="text-align: center; margin-bottom: 1rem;">
                <img src="<?php echo SITE_URL; ?>uploads/<?php echo urlencode($verificador['archivo_verificador']); ?>" 
                     alt="Verificador" 
                     style="max-width: 100%; max-height: 400px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            </div>
            <div style="text-align: center;">
                <button type="button" class="btn btn-sm btn-info" 
                        onclick="verEnPantallaCompleta('<?php echo urlencode($verificador['archivo_verificador']); ?>')">
                    <i class="bi bi-fullscreen"></i> Ver en Pantalla Completa
                </button>
                <a href="<?php echo SITE_URL; ?>uploads/<?php echo urlencode($verificador['archivo_verificador']); ?>" 
                   class="btn btn-sm btn-success" target="_blank" download>
                    <i class="bi bi-download"></i> Descargar
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- PARA PDF U OTROS FORMATOS -->
    <div class="row mt-3">
        <div class="col-12">
            <h6>Archivo de Verificación</h6>
            <p class="text-muted">Este archivo no se puede mostrar en línea</p>
            <a href="<?php echo SITE_URL; ?>uploads/<?php echo urlencode($verificador['archivo_verificador']); ?>" 
               class="btn btn-success btn-sm" target="_blank" download>
                <i class="bi bi-download"></i> Descargar <?php echo strtoupper(pathinfo($verificador['archivo_verificador'], PATHINFO_EXTENSION)); ?>
            </a>
        </div>
    </div>
<?php endif; ?>
