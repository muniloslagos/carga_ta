<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/classes/Verificador.php';
require_once dirname(dirname(__DIR__)) . '/classes/Usuario.php';

$verif_id = (int)($_GET['verif_id'] ?? 0);

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$verificadorClass = new Verificador($db_conn);
$usuarioClass = new Usuario($db_conn);

$verificador = $verificadorClass->getById($verif_id);

if (!$verificador) {
    echo '<div class="alert alert-danger">Verificador no encontrado</div>';
    exit;
}

$publicador = $usuarioClass->getById($verificador['publicador_id']);
$archivoPath = dirname(dirname(__DIR__)) . '/uploads/' . $verificador['archivo_verificador'];
$esImagen = in_array(strtolower(pathinfo($verificador['archivo_verificador'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
?>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title text-success"><i class="bi bi-info-circle"></i> Información del Verificador</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td width="40%" class="text-muted"><strong>Publicado por:</strong></td>
                <td><?php echo htmlspecialchars($publicador['nombre'] ?? 'Desconocido'); ?></td>
            </tr>
            <tr>
                <td class="text-muted"><strong>Fecha de Publicación:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal'])); ?></td>
            </tr>
            <tr>
                <td class="text-muted"><strong>Comentarios:</strong></td>
                <td><?php echo htmlspecialchars($verificador['comentarios'] ?? '-'); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if ($esImagen): ?>
<div class="card">
    <div class="card-body text-center">
        <h6 class="card-title"><i class="bi bi-image"></i> Imagen de Verificación</h6>
        <div class="my-3">
            <img src="<?php echo SITE_URL; ?>uploads/<?php echo urlencode($verificador['archivo_verificador']); ?>" 
                 alt="Verificador" 
                 class="img-thumbnail" 
                 style="max-width: 100%; max-height: 400px; cursor: pointer;"
                 onclick="verEnPantallaCompleta('<?php echo urlencode($verificador['archivo_verificador']); ?>')">
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-info btn-sm" 
                    onclick="verEnPantallaCompleta('<?php echo urlencode($verificador['archivo_verificador']); ?>')">
                <i class="bi bi-arrows-fullscreen"></i> Ver en Pantalla Completa
            </button>
            <a href="<?php echo SITE_URL; ?>uploads/<?php echo urlencode($verificador['archivo_verificador']); ?>" 
               class="btn btn-success btn-sm" download>
                <i class="bi bi-download"></i> Descargar
            </a>
        </div>
    </div>
</div>

<script>
function verEnPantallaCompleta(archivo) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 10000; cursor: pointer;';
    
    const img = document.createElement('img');
    img.src = '<?php echo SITE_URL; ?>uploads/' + decodeURIComponent(archivo);
    img.style.cssText = 'max-width: 95%; max-height: 95%; object-fit: contain; box-shadow: 0 4px 20px rgba(255,255,255,0.2);';
    img.alt = 'Verificador en pantalla completa';
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<i class="bi bi-x-lg"></i>';
    btnCerrar.className = 'btn btn-light btn-lg';
    btnCerrar.style.cssText = 'position: absolute; top: 20px; right: 20px; z-index: 10001; border-radius: 50%; width: 50px; height: 50px;';
    btnCerrar.title = 'Cerrar (ESC)';
    btnCerrar.onclick = (e) => {
        e.stopPropagation();
        overlay.remove();
    };
    
    overlay.appendChild(img);
    overlay.appendChild(btnCerrar);
    document.body.appendChild(overlay);
    
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.remove();
    });
    
    const handleEsc = (e) => {
        if (e.key === 'Escape') {
            overlay.remove();
            document.removeEventListener('keydown', handleEsc);
        }
    };
    document.addEventListener('keydown', handleEsc);
}
</script>

<?php else: ?>
<div class="card">
    <div class="card-body text-center">
        <h6 class="card-title"><i class="bi bi-file-earmark-pdf"></i> Archivo de Verificación</h6>
        <p class="text-muted">Este archivo no se puede mostrar en línea</p>
        <a href="<?php echo SITE_URL; ?>uploads/<?php echo urlencode($verificador['archivo_verificador']); ?>" 
           class="btn btn-success" download>
            <i class="bi bi-download"></i> Descargar <?php echo strtoupper(pathinfo($verificador['archivo_verificador'], PATHINFO_EXTENSION)); ?>
        </a>
    </div>
</div>
<?php endif; ?>
