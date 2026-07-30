<?php
require_once dirname(__DIR__) . '/includes/check_auth.php';
require_login();

require_once dirname(__DIR__) . '/includes/transparencia_pasiva_csv.php';

if (!tp_esta_habilitada($db->getConnection())) {
    header('Location: ' . SITE_URL);
    exit;
}

$solicitudesPasivas = [];
$solicitudesFiltradas = [];
$errorSolicitudesPasivas = null;
$solicitudesInformacionPendientes = 0;
$vistaRespondidas = isset($_GET['vista']) && $_GET['vista'] === 'respondidas';

try {
    $solicitudesPasivas = tp_obtener_solicitudes(__DIR__);
    foreach ($solicitudesPasivas as $solicitudPasiva) {
        if (
            $solicitudPasiva['estado'] === 'En Proceso'
            && $solicitudPasiva['estado_actual'] === 'SOLICITUD INTERNA'
        ) {
            $solicitudesInformacionPendientes++;
        }
    }

    $solicitudesFiltradas = array_values(array_filter(
        $solicitudesPasivas,
        static function (array $solicitud) use ($vistaRespondidas): bool {
            if ($vistaRespondidas) {
                return $solicitud['estado_actual'] === 'RESPUESTA ENTREGADA';
            }

            return $solicitud['estado'] === 'En Proceso'
                && $solicitud['estado_actual'] === 'SOLICITUD INTERNA';
        }
    ));
} catch (Throwable $error) {
    $errorSolicitudesPasivas = $error->getMessage();
}

function tp_clase_estado_solicitud(array $solicitud): string
{
    $estado = strtoupper(trim((string)$solicitud['estado']));
    $estadoActual = strtoupper(trim((string)$solicitud['estado_actual']));

    if ($estadoActual === 'SOLICITUD INTERNA' && $estado === 'CONTESTADA') {
        return 'tp-row-internal-answered';
    }
    if ($estadoActual === 'RESPUESTA ENTREGADA') {
        return 'tp-row-delivered';
    }
    if ($estadoActual === 'SOLICITUD INTERNA') {
        return 'tp-row-internal';
    }

    return '';
}

$contadorSolicitudesInformacionPreparado = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<style>
    .tp-page {
        max-width: 1600px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .tp-header {
        border-left: 5px solid #3498db;
        padding-left: 1rem;
    }

    .tp-table-card {
        border: 0;
        border-radius: 0.85rem;
        box-shadow: 0 0.25rem 1rem rgba(44, 62, 80, 0.1);
        overflow: hidden;
    }

    .tp-table thead th {
        background: #2c3e50;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        padding: 0.9rem 0.75rem;
        vertical-align: middle;
        white-space: nowrap;
    }

    .tp-table tbody td {
        color: #34495e;
        font-size: 0.88rem;
        padding: 0.85rem 0.75rem;
        vertical-align: middle;
    }

    .tp-table tbody tr.tp-row-delivered > td {
        background-color: #d1e7dd;
    }

    .tp-table tbody tr.tp-row-delivered:hover > td {
        background-color: #badbcc;
    }

    .tp-table tbody tr.tp-row-internal > td {
        background-color: #f8d7da;
    }

    .tp-table tbody tr.tp-row-internal:hover > td {
        background-color: #f1bfc4;
    }

    .tp-table tbody tr.tp-row-internal-answered > td {
        background-color: #fff3cd;
    }

    .tp-table tbody tr.tp-row-internal-answered:hover > td {
        background-color: #ffe69c;
    }

    .tp-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem 1.25rem;
        color: #5d6d7e;
        font-size: 0.82rem;
    }

    .tp-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .tp-legend-color {
        width: 0.85rem;
        height: 0.85rem;
        border: 1px solid rgba(44, 62, 80, 0.14);
        border-radius: 0.2rem;
    }

    .tp-info-button {
        color: #1769aa;
        font-size: 0.88rem;
        text-align: left;
        text-decoration: none;
    }

    .tp-info-button:hover,
    .tp-info-button:focus {
        color: #0d4778;
        text-decoration: underline;
    }

    .tp-code {
        color: #22313f;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 600;
        white-space: nowrap;
    }

    .tp-state {
        display: inline-block;
        border: 1px solid #d8e2ea;
        border-radius: 50rem;
        background: #f6f8fa;
        color: #34495e;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.25rem 0.55rem;
        white-space: nowrap;
    }

    .tp-unit {
        min-width: 180px;
    }

    .tp-date {
        white-space: nowrap;
    }

    .tp-modal-text {
        line-height: 1.65;
        white-space: pre-wrap;
    }
</style>

<main class="tp-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div class="tp-header">
            <h1 class="h3 mb-1">Solicitudes de Información</h1>
            <p class="text-muted mb-0">
                <?php echo $vistaRespondidas
                    ? 'Solicitudes de acceso a la información respondidas.'
                    : 'Solicitudes realizadas a las direcciones que están pendientes de respuesta.'; ?>
            </p>
        </div>
        <div class="flex-shrink-0">
            <?php if ($vistaRespondidas): ?>
                <a href="<?php echo SITE_URL; ?>t_pasiva/" class="btn btn-outline-primary">
                    <i class="bi bi-hourglass-split me-1"></i> Ver SAI en proceso
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>t_pasiva/?vista=respondidas" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i> Ver SAI respondidas
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($errorSolicitudesPasivas !== null): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            No fue posible cargar las solicitudes. <?php echo htmlspecialchars($errorSolicitudesPasivas, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
            <div class="text-muted small">
                <?php if ($vistaRespondidas): ?>
                    Mostrando <?php echo count($solicitudesFiltradas); ?> SAI respondidas.
                <?php else: ?>
                    Mostrando <?php echo count($solicitudesFiltradas); ?> SAI en proceso.
                <?php endif; ?>
            </div>
            <div class="tp-legend" aria-label="Significado de los colores">
                <span class="tp-legend-item">
                    <span class="tp-legend-color" style="background:#d1e7dd;"></span>
                    Respuesta entregada
                </span>
                <span class="tp-legend-item">
                    <span class="tp-legend-color" style="background:#f8d7da;"></span>
                    Solicitud interna
                </span>
                <span class="tp-legend-item">
                    <span class="tp-legend-color" style="background:#fff3cd;"></span>
                    Solicitud interna contestada
                </span>
            </div>
        </div>

        <div class="card tp-table-card">
            <div class="table-responsive">
                <table class="table table-hover tp-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Código</th>
                            <th scope="col">Información solicitada</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Estado actual</th>
                            <th scope="col">Unidad</th>
                            <th scope="col">Fecha ingreso</th>
                            <th scope="col">Fecha caducidad</th>
                            <th scope="col">Prórroga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($solicitudesFiltradas === []): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <?php echo $vistaRespondidas
                                        ? 'No hay SAI respondidas para mostrar.'
                                        : 'No hay solicitudes internas en proceso.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($solicitudesFiltradas as $indice => $solicitud): ?>
                                <tr class="<?php echo tp_clase_estado_solicitud($solicitud); ?>">
                                    <td class="tp-code">
                                        <?php echo htmlspecialchars($solicitud['codigo'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-link tp-info-button p-0"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalInformacionSolicitud"
                                            data-codigo="<?php echo htmlspecialchars($solicitud['codigo'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-informacion="<?php echo htmlspecialchars($solicitud['informacion'], ENT_QUOTES, 'UTF-8'); ?>"
                                            aria-label="Ver información completa de la solicitud <?php echo htmlspecialchars($solicitud['codigo'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <?php echo htmlspecialchars(tp_resumir_texto($solicitud['informacion']), ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
                                    </td>
                                    <td>
                                        <span class="tp-state">
                                            <?php echo htmlspecialchars($solicitud['estado'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="tp-state">
                                            <?php echo htmlspecialchars($solicitud['estado_actual'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="tp-unit">
                                        <?php echo htmlspecialchars($solicitud['unidad'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="tp-date">
                                        <?php echo htmlspecialchars($solicitud['fecha_ingreso'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="tp-date">
                                        <?php echo htmlspecialchars($solicitud['fecha_caducidad'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($solicitud['prorroga'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<div class="modal fade" id="modalInformacionSolicitud" tabindex="-1" aria-labelledby="modalInformacionTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="modalInformacionTitulo">Información solicitada</h2>
                    <div class="small text-muted" id="modalInformacionCodigo"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="tp-modal-text mb-0" id="modalInformacionTexto"></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalInformacionSolicitud');
    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        document.getElementById('modalInformacionCodigo').textContent =
            button.getAttribute('data-codigo') || '';
        document.getElementById('modalInformacionTexto').textContent =
            button.getAttribute('data-informacion') || '';
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
