<?php
require_once dirname(__DIR__) . '/includes/check_auth.php';
require_login();

require_once dirname(__DIR__) . '/includes/transparencia_pasiva_csv.php';

$solicitudesPasivas = [];
$errorSolicitudesPasivas = null;
$solicitudesInformacionPendientes = 0;

try {
    $solicitudesPasivas = tp_obtener_solicitudes(__DIR__);
    foreach ($solicitudesPasivas as $solicitudPasiva) {
        if ($solicitudPasiva['estado'] === 'En Proceso') {
            $solicitudesInformacionPendientes++;
        }
    }
} catch (Throwable $error) {
    $errorSolicitudesPasivas = $error->getMessage();
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
    <div class="tp-header mb-4">
        <h1 class="h3 mb-1">Solicitudes de Información</h1>
        <p class="text-muted mb-0">
            Solicitudes internas de Transparencia Pasiva y su estado actual.
        </p>
    </div>

    <?php if ($errorSolicitudesPasivas !== null): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            No fue posible cargar las solicitudes. <?php echo htmlspecialchars($errorSolicitudesPasivas, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php else: ?>
        <div class="card tp-table-card">
            <div class="table-responsive">
                <table class="table table-hover table-striped tp-table mb-0">
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
                        <?php if ($solicitudesPasivas === []): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    No hay solicitudes para mostrar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($solicitudesPasivas as $indice => $solicitud): ?>
                                <tr>
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
