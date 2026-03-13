<?php
// Mostrar información del documento
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/classes/Documento.php';
require_once dirname(dirname(__DIR__)) . '/classes/Usuario.php';

$doc_id = (int)($_GET['doc_id'] ?? 0);

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$documentoClass = new Documento($db_conn);
$usuarioClass = new Usuario($db_conn);

$documento = $documentoClass->getById($doc_id);

if (!$documento) {
    echo '<p class="text-danger">Documento no encontrado</p>';
    exit;
}

$usuario = $usuarioClass->getById($documento['usuario_id']);
?>

<div class="row">
    <div class="col-md-6">
        <h6>Información del Documento</h6>
        <p><strong>Título:</strong> <?php echo htmlspecialchars($documento['titulo']); ?></p>
        <p><strong>Cargado por:</strong> <?php echo htmlspecialchars($usuario['nombre'] ?? 'Desconocido'); ?></p>
        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($documento['descripcion'] ?? '-'); ?></p>
        <p><strong>Fecha Carga:</strong> <?php echo date('d/m/Y H:i', strtotime($documento['fecha_subida'])); ?></p>
    </div>
    <div class="col-md-6">
        <h6>Descargar Documento</h6>
        <a href="../usuario/descargar_documento.php?doc_id=<?php echo $documento['id']; ?>" class="btn btn-primary btn-sm" target="_blank">
            <i class="bi bi-download"></i> Descargar <?php echo pathinfo($documento['archivo'], PATHINFO_EXTENSION); ?>
        </a>
    </div>
</div>
