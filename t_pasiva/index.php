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

function tp_convertir_fecha(string $valor): ?DateTimeImmutable
{
    $valor = trim($valor);
    if ($valor === '') {
        return null;
    }

    // Los reportes SAI normalmente entregan ISO con o sin hora/fracciones.
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valor, $partes)) {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $partes[1] . '-' . $partes[2] . '-' . $partes[3]);
        return $fecha ?: null;
    }

    foreach (['!d/m/Y', '!d-m-Y'] as $formato) {
        $fecha = DateTimeImmutable::createFromFormat($formato, $valor);
        if ($fecha !== false) {
            return $fecha;
        }
    }

    return null;
}

function tp_formatear_fecha(string $valor): string
{
    $fecha = tp_convertir_fecha($valor);
    return $fecha ? $fecha->format('d/m/Y') : trim($valor);
}

function tp_semaforo_caducidad(string $valor): array
{
    $fecha = tp_convertir_fecha($valor);
    if (!$fecha) {
        return ['clase' => 'secondary', 'texto' => 'Fecha no disponible'];
    }

    $hoy = new DateTimeImmutable('today');
    $dias = (int)$hoy->diff($fecha)->format('%r%a');
    if ($dias < 0) {
        return ['clase' => 'danger', 'texto' => 'Vencida'];
    }
    if ($dias <= 5) {
        return ['clase' => 'warning', 'texto' => $dias === 0 ? 'Vence hoy' : 'Por vencer en ' . $dias . ' día(s)'];
    }

    return ['clase' => 'success', 'texto' => 'Vigente'];
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
        border: 1px solid #d9e0e7;
        border-radius: 0.85rem;
        box-shadow: 0 0.35rem 1.2rem rgba(44, 62, 80, 0.08);
        overflow: hidden;
        background: #fff;
    }

    .tp-table {
        --bs-table-bg: #fff;
        --bs-table-hover-bg: #f6f8fa;
        border-collapse: separate;
        border-spacing: 0;
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
        border: 0;
        border-right: 1px solid rgba(255, 255, 255, 0.13);
        border-bottom: 1px solid #243442;
    }

    .tp-table thead th:last-child {
        border-right: 0;
    }

    .tp-sort-button {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 0;
        padding: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: inherit;
        white-space: nowrap;
        cursor: pointer;
    }

    .tp-sort-button:hover,
    .tp-sort-button:focus-visible {
        color: #dbeafe;
    }

    .tp-sort-button:focus-visible {
        outline: 2px solid #93c5fd;
        outline-offset: 3px;
        border-radius: 0.15rem;
    }

    .tp-sort-button i {
        font-size: 0.78rem;
        opacity: 0.85;
    }

    .tp-table tbody td {
        background-color: #fff;
        color: #34495e;
        font-size: 0.88rem;
        padding: 0.85rem 0.75rem;
        vertical-align: middle;
        border: 0;
        border-right: 1px solid #e1e6eb;
        border-bottom: 1px solid #d9e0e7;
    }

    .tp-table tbody td:last-child {
        border-right: 0;
    }

    .tp-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .tp-table tbody tr:hover > td,
    .tp-table tbody tr.tp-row-delivered:hover > td,
    .tp-table tbody tr.tp-row-internal:hover > td,
    .tp-table tbody tr.tp-row-internal-answered:hover > td {
        background-color: #f6f8fa;
    }

    .tp-table tbody tr.tp-row-delivered > td:first-child {
        box-shadow: inset 4px 0 0 #198754;
    }

    .tp-table tbody tr.tp-row-internal > td:first-child {
        box-shadow: inset 4px 0 0 #dc3545;
    }

    .tp-table tbody tr.tp-row-internal-answered > td:first-child {
        box-shadow: inset 4px 0 0 #ffc107;
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
        width: 0.28rem;
        height: 1rem;
        border: 0;
        border-radius: 50rem;
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
        border: 1px solid #d3dce5;
        border-radius: 50rem;
        background: #f8fafc;
        color: #2c3e50;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.25rem 0.55rem;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(44, 62, 80, 0.04);
    }

    .tp-unit {
        min-width: 180px;
    }

    .tp-date {
        white-space: nowrap;
    }

    .tp-table tbody td.tp-deadline {
        background-color: #fff !important;
    }

    .tp-table tbody tr:hover td.tp-deadline {
        background-color: #f6f8fa !important;
    }

    .tp-deadline-content {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .tp-deadline-dot {
        width: 0.72rem;
        height: 0.72rem;
        border-radius: 50%;
        flex: 0 0 auto;
        box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.08);
    }

    .tp-deadline-dot.danger { background-color: #dc3545; }
    .tp-deadline-dot.warning { background-color: #ffc107; }
    .tp-deadline-dot.success { background-color: #198754; }
    .tp-deadline-dot.secondary { background-color: #6c757d; }

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
                    <span class="tp-legend-color" style="background:#198754;"></span>
                    Respuesta entregada
                </span>
                <?php if (!$vistaRespondidas): ?>
                    <span class="tp-legend-item">
                        <span class="tp-legend-color" style="background:#dc3545;"></span>
                        Solicitud interna
                    </span>
                    <span class="tp-legend-item">
                        <span class="tp-legend-color" style="background:#ffc107;"></span>
                        Solicitud interna contestada
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card tp-table-card">
            <div class="table-responsive">
                <table class="table table-hover tp-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Código</th>
                            <th scope="col">Información solicitada</th>
                            <?php if (!$vistaRespondidas): ?>
                                <th scope="col">Estado de solicitud interna</th>
                            <?php endif; ?>
                            <th scope="col">Estado actual</th>
                            <th scope="col" aria-sort="none">
                                <button type="button" class="tp-sort-button" data-sort-key="direccion">
                                    Dirección <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col" aria-sort="none">
                                <button type="button" class="tp-sort-button" data-sort-key="fecha">
                                    Fecha ingreso <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
                                </button>
                            </th>
                            <th scope="col">Fecha vencimiento</th>
                            <th scope="col">Prórroga</th>
                        </tr>
                    </thead>
                    <tbody id="tpSolicitudesBody">
                        <?php if ($solicitudesFiltradas === []): ?>
                            <tr>
                                <td colspan="<?php echo $vistaRespondidas ? 7 : 8; ?>" class="text-center text-muted py-5">
                                    <?php echo $vistaRespondidas
                                        ? 'No hay SAI respondidas para mostrar.'
                                        : 'No hay solicitudes internas en proceso.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($solicitudesFiltradas as $indice => $solicitud): ?>
                                <?php $semaforoCaducidad = !$vistaRespondidas
                                    ? tp_semaforo_caducidad((string)$solicitud['fecha_caducidad'])
                                    : null; ?>
                                <?php
                                    $direccionOrden = trim((string)$solicitud['unidad']);
                                    if (function_exists('mb_strtolower')) {
                                        $direccionOrden = mb_strtolower($direccionOrden, 'UTF-8');
                                    } else {
                                        $direccionOrden = strtolower($direccionOrden);
                                    }
                                    $fechaIngresoOrden = tp_convertir_fecha((string)$solicitud['fecha_ingreso']);
                                ?>
                                <tr class="<?php echo tp_clase_estado_solicitud($solicitud); ?>"
                                    data-solicitud-row
                                    data-direccion="<?php echo htmlspecialchars($direccionOrden, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-fecha="<?php echo $fechaIngresoOrden ? $fechaIngresoOrden->format('Ymd') : ''; ?>">
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
                                    <?php if (!$vistaRespondidas): ?>
                                        <td>
                                            <span class="tp-state">
                                                <?php echo htmlspecialchars($solicitud['estado'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="tp-state">
                                            <?php echo htmlspecialchars($solicitud['estado_actual'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="tp-unit">
                                        <?php echo htmlspecialchars($solicitud['unidad'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="tp-date">
                                        <?php echo htmlspecialchars(tp_formatear_fecha((string)$solicitud['fecha_ingreso']), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="tp-date<?php echo $vistaRespondidas ? '' : ' tp-deadline'; ?>">
                                        <?php if ($vistaRespondidas): ?>
                                            <?php echo htmlspecialchars(tp_formatear_fecha((string)$solicitud['fecha_caducidad']), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php else: ?>
                                            <span class="tp-deadline-content" title="<?php echo htmlspecialchars($semaforoCaducidad['texto'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <span class="tp-deadline-dot <?php echo $semaforoCaducidad['clase']; ?>" aria-hidden="true"></span>
                                                <span><?php echo htmlspecialchars(tp_formatear_fecha((string)$solicitud['fecha_caducidad']), ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="visually-hidden"><?php echo htmlspecialchars($semaforoCaducidad['texto'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            </span>
                                        <?php endif; ?>
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
    if (modal) {
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
    }

    const tbody = document.getElementById('tpSolicitudesBody');
    const sortButtons = document.querySelectorAll('.tp-sort-button');
    if (!tbody || sortButtons.length === 0) {
        return;
    }

    sortButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const key = button.dataset.sortKey;
            const nextDirection = button.dataset.direction === 'asc' ? 'desc' : 'asc';
            const rows = Array.from(tbody.querySelectorAll('tr[data-solicitud-row]'));

            rows.sort(function (rowA, rowB) {
                const valueA = rowA.dataset[key] || '';
                const valueB = rowB.dataset[key] || '';
                let comparison;
                if (key === 'fecha') {
                    comparison = valueA.localeCompare(valueB);
                } else {
                    comparison = valueA.localeCompare(valueB, 'es', { sensitivity: 'base' });
                }
                return nextDirection === 'asc' ? comparison : -comparison;
            });

            rows.forEach(function (row) {
                tbody.appendChild(row);
            });

            sortButtons.forEach(function (otherButton) {
                otherButton.dataset.direction = '';
                otherButton.closest('th').setAttribute('aria-sort', 'none');
                otherButton.querySelector('i').className = 'bi bi-arrow-down-up';
            });

            button.dataset.direction = nextDirection;
            button.closest('th').setAttribute('aria-sort', nextDirection === 'asc' ? 'ascending' : 'descending');
            button.querySelector('i').className = nextDirection === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
        });
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
