<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/classes/Documento.php';
require_once dirname(dirname(__DIR__)) . '/classes/Usuario.php';

$doc_id = (int)($_GET['doc_id'] ?? 0);

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$documentoClass = new Documento($db_conn);
$usuarioClass = new Usuario($db_conn);

$documento = $documentoClass->getById($doc_id);

if (!$documento) {
    echo '<div class="alert alert-danger">Documento no encontrado</div>';
    exit;
}

$usuario = $usuarioClass->getById($documento['usuario_id']);
$extension = strtoupper(pathinfo($documento['archivo'], PATHINFO_EXTENSION));

// Obtener info del publicador (quien subió el verificador)
$publicador = null;
$fechaPublicacion = null;
$sqlVerif = "SELECT vp.fecha_carga_portal, vp.publicador_id, u.nombre as publicador_nombre
             FROM verificadores_publicador vp
             LEFT JOIN usuarios u ON vp.publicador_id = u.id
             WHERE vp.documento_id = ?
             ORDER BY vp.fecha_carga_portal DESC LIMIT 1";
$stmtVerif = $db_conn->prepare($sqlVerif);
$stmtVerif->bind_param('i', $doc_id);
$stmtVerif->execute();
$resVerif = $stmtVerif->get_result();
if ($rowVerif = $resVerif->fetch_assoc()) {
    $publicador = $rowVerif['publicador_nombre'];
    $fechaPublicacion = $rowVerif['fecha_carga_portal'];
}
?>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title text-info"><i class="bi bi-file-earmark-text"></i> Información del Documento</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td width="35%" class="text-muted"><strong>Título:</strong></td>
                <td><?php echo htmlspecialchars($documento['titulo']); ?></td>
            </tr>
            <tr>
                <td class="text-muted"><strong>Item:</strong></td>
                <td><?php echo htmlspecialchars($documento['item_nombre'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="text-muted"><strong>Cargado por:</strong></td>
                <td><?php echo htmlspecialchars($usuario['nombre'] ?? 'Desconocido'); ?></td>
            </tr>
            <tr>
                <td class="text-muted"><strong>Fecha de Carga:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($documento['fecha_subida'])); ?></td>
            </tr>
            <tr>
                <td class="text-muted"><strong>Estado:</strong></td>
                <td>
                    <?php if ($documento['estado'] === 'pendiente'): ?>
                        <span class="badge bg-warning text-dark">Pendiente</span>
                    <?php elseif ($documento['estado'] === 'aprobado'): ?>
                        <span class="badge bg-success">Aprobado</span>
                    <?php elseif ($documento['estado'] === 'Publicado'): ?>
                        <span class="badge bg-primary">Publicado</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><?php echo ucfirst($documento['estado']); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($publicador): ?>
            <tr>
                <td class="text-muted"><strong>Publicado por:</strong></td>
                <td><?php echo htmlspecialchars($publicador); ?></td>
            </tr>
            <tr>
                <td class="text-muted"><strong>Fecha Publicación:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($fechaPublicacion)); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($documento['descripcion']): ?>
            <tr>
                <td class="text-muted"><strong>Descripción:</strong></td>
                <td><?php echo nl2br(htmlspecialchars($documento['descripcion'])); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body text-center">
        <h6 class="card-title"><i class="bi bi-download"></i> Descargar Documento</h6>
        <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
            <i class="bi bi-file-earmark-<?php echo in_array(strtolower($extension), ['pdf']) ? 'pdf' : 'text'; ?>" style="font-size: 3rem; color: #0d6efd;"></i>
            <div class="text-start">
                <div class="fw-bold"><?php echo htmlspecialchars(basename($documento['archivo'])); ?></div>
                <small class="text-muted">Formato: <?php echo $extension; ?></small>
            </div>
        </div>
        <a href="<?php echo SITE_URL; ?>usuario/descargar_documento.php?doc_id=<?php echo $documento['id']; ?>" 
           class="btn btn-primary" target="_blank">
            <i class="bi bi-download"></i> Descargar Documento
        </a>
    </div>
</div>
