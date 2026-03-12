<?php
require_once dirname(dirname(__DIR__)) . '/includes/check_auth.php';

// Verificar permisos
if ($current_profile !== 'administrativo' && $current_profile !== 'auditor') {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

require_once dirname(dirname(__DIR__)) . '/includes/header.php';

// Obtener mes y año seleccionado
$mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anoSeleccionado = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

if ($mesSeleccionado < 1 || $mesSeleccionado > 12) $mesSeleccionado = (int)date('m');
if ($anoSeleccionado < 2000 || $anoSeleccionado > 2100) $anoSeleccionado = (int)date('Y');

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$conn = $db->getConnection();

// =====================================================
// FUNCIÓN: Calcular el 10° día hábil de un mes/año
// =====================================================
function decimoDiaHabil(int $mes, int $ano): string {
    $diasHabiles = 0;
    $dia = 1;
    while ($diasHabiles < 10) {
        $ts = mktime(0, 0, 0, $mes, $dia, $ano);
        $diaSemana = (int)date('N', $ts); // 1=lunes ... 7=domingo
        if ($diaSemana <= 5) { // lunes a viernes
            $diasHabiles++;
        }
        if ($diasHabiles < 10) $dia++;
    }
    return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
}

// El plazo legal es el 10° día hábil del MES SIGUIENTE al período seleccionado
$mesSiguiente = $mesSeleccionado == 12 ? 1 : $mesSeleccionado + 1;
$anoSiguiente  = $mesSeleccionado == 12 ? $anoSeleccionado + 1 : $anoSeleccionado;
$plazoLegal = decimoDiaHabil($mesSiguiente, $anoSiguiente);
$plazoLegalDisplay = date('d/m/Y', strtotime($plazoLegal));

// =====================================================
// QUERY: Todos los items con su estado en el período
// =====================================================
$query = "
    SELECT
        i.id as item_id,
        i.numeracion,
        i.nombre as item_nombre,
        i.periodicidad,
        d.id as doc_id,
        d.titulo,
        d.archivo,
        d.estado as doc_estado,
        d.fecha_subida,
        ds.mes,
        ds.ano,
        ds.fecha_envio,
        u.nombre as responsable,
        vp.id as verificador_id,
        vp.archivo_verificador,
        vp.fecha_carga_portal,
        pub.nombre as publicado_por
    FROM items_transparencia i
    LEFT JOIN documentos d ON i.id = d.item_id
        AND d.estado IN ('pendiente', 'aprobado', 'Publicado')
    LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN verificadores_publicador vp ON d.id = vp.documento_id
    LEFT JOIN usuarios pub ON vp.publicador_id = pub.id
    WHERE i.activo = 1
        AND (
            d.id IS NULL
            OR (i.periodicidad = 'mensual'     AND ds.mes = ? AND ds.ano = ?)
            OR (i.periodicidad = 'trimestral'  AND FLOOR((ds.mes-1)/3) = FLOOR((?-1)/3) AND ds.ano = ?)
            OR (i.periodicidad = 'semestral'   AND FLOOR((ds.mes-1)/6) = FLOOR((?-1)/6) AND ds.ano = ?)
            OR (i.periodicidad = 'anual'       AND ds.ano = ?)
            OR (i.periodicidad = 'ocurrencia'  AND ds.mes = ? AND ds.ano = ?)
        )
    ORDER BY
        FIELD(i.periodicidad, 'mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia'),
        i.numeracion";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiiiiiiii",
    $mesSeleccionado, $anoSeleccionado,
    $mesSeleccionado, $anoSeleccionado,
    $mesSeleccionado, $anoSeleccionado,
    $anoSeleccionado,
    $mesSeleccionado, $anoSeleccionado
);
$stmt->execute();
$resultado = $stmt->get_result();

$itemsPendientes = [];
$itemsPublicados = [];

while ($row = $resultado->fetch_assoc()) {
    if ($row['verificador_id']) {
        // Calcular "Publicado en plazo legal" solo para mensuales
        $row['en_plazo'] = null;
        if ($row['periodicidad'] === 'mensual' && $row['fecha_carga_portal']) {
            $fechaPub = date('Y-m-d', strtotime($row['fecha_carga_portal']));
            $row['en_plazo'] = ($fechaPub <= $plazoLegal);
        }
        $itemsPublicados[] = $row;
    } else {
        $itemsPendientes[] = $row;
    }
}

$totalPendientes = count($itemsPendientes);
$totalPublicados = count($itemsPublicados);
?>

<!-- HEADER UNIFICADO -->
<div style="display: flex; align-items: center; gap: 0; margin-bottom: 1.75rem; border-bottom: 2px solid #e9ecef; padding-bottom: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; color: #6c757d; font-size: 0.9rem; white-space: nowrap;">
        <i class="bi bi-shield-check" style="color: #3498db; font-size: 1.1rem;"></i>
        <span style="font-weight: 500;">Control de Transparencia</span>
    </div>
    <div style="width: 1px; height: 36px; background: #ced4da; margin: 0 1.25rem; flex-shrink: 0;"></div>
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="bi bi-clipboard2-check-fill" style="font-size: 1.1rem; color: white;"></i>
        </div>
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #2c3e50; margin: 0; line-height: 1.2;">
                Auditoría de Transparencia Activa
            </h1>
            <p style="font-size: 0.8rem; color: #6c757d; margin: 0;">
                Revisión y control de cumplimiento de publicaciones
            </p>
        </div>
    </div>
</div>

<!-- CONTROLES Y ESTADÍSTICAS -->
<div class="row mb-4">
    <!-- Selector de período -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="bi bi-calendar3"></i> Período</h6>
                <form method="GET" class="d-flex gap-2">
                    <select name="mes" class="form-select" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo ($m === $mesSeleccionado) ? 'selected' : ''; ?>>
                                <?php echo $meses[$m]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="ano" class="form-select" onchange="this.form.submit()">
                        <?php for ($a = 2024; $a <= 2030; $a++): ?>
                            <option value="<?php echo $a; ?>" <?php echo ($a === $anoSeleccionado) ? 'selected' : ''; ?>>
                                <?php echo $a; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </form>
                <small class="text-muted mt-2 d-block">
                    <i class="bi bi-info-circle"></i>
                    Plazo legal publicación (10° día hábil de <?php echo $meses[$mesSiguiente] . ' ' . $anoSiguiente; ?>): <strong><?php echo $plazoLegalDisplay; ?></strong>
                </small>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-<?php echo $totalPendientes > 0 ? 'warning' : 'success'; ?> mb-2" style="border-left: 4px solid <?php echo $totalPendientes > 0 ? '#ff9800' : '#28a745'; ?>;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-<?php echo $totalPendientes > 0 ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?>" style="font-size: 1.5rem; margin-right: 0.75rem;"></i>
                        <div>
                            <strong>Pendientes de Publicar</strong>
                            <div><?php echo $totalPendientes; ?> item(s) sin verificador</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-success mb-2" style="border-left: 4px solid #28a745;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill" style="font-size: 1.5rem; margin-right: 0.75rem;"></i>
                        <div>
                            <strong>Ya Publicados</strong>
                            <div><?php echo $totalPublicados; ?> item(s) con verificador</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PESTAÑAS -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-pendientes" data-bs-toggle="tab" data-bs-target="#contenido-pendientes" type="button" role="tab">
            <i class="bi bi-clock-history"></i> Pendientes de Publicar
            <?php if ($totalPendientes > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $totalPendientes; ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-publicados" data-bs-toggle="tab" data-bs-target="#contenido-publicados" type="button" role="tab">
            <i class="bi bi-check-circle"></i> Ya Publicados
            <?php if ($totalPublicados > 0): ?>
                <span class="badge bg-success ms-1"><?php echo $totalPublicados; ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ============================================= -->
    <!-- TAB: PENDIENTES DE PUBLICAR -->
    <!-- ============================================= -->
    <div class="tab-pane fade show active" id="contenido-pendientes" role="tabpanel">
        <?php if (count($itemsPendientes) === 0): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> Todos los items del período están publicados.
            </div>
        <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-clock-history"></i> Items Pendientes de Publicar
                <span class="badge bg-dark ms-2"><?php echo count($itemsPendientes); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="6%">Núm.</th>
                            <th width="25%">Item</th>
                            <th width="10%">Periodicidad</th>
                            <th width="8%">Período</th>
                            <th width="15%">Responsable</th>
                            <th width="13%">Fecha Envío Resp.</th>
                            <th width="10%">Documento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemsPendientes as $item): ?>
                        <tr>
                            <td><small class="text-muted"><?php echo htmlspecialchars($item['numeracion']); ?></small></td>
                            <td><strong><?php echo htmlspecialchars($item['item_nombre']); ?></strong></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($item['periodicidad']); ?></span></td>
                            <td>
                                <small class="text-primary">
                                    <?php
                                    if (!$item['doc_id']) {
                                        echo '<span class="text-muted">Sin doc.</span>';
                                    } elseif ($item['periodicidad'] === 'anual') {
                                        echo $item['ano'];
                                    } else {
                                        echo $meses[$item['mes']] . ' ' . $item['ano'];
                                    }
                                    ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($item['responsable']): ?>
                                    <small><?php echo htmlspecialchars($item['responsable']); ?></small>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['fecha_envio']): ?>
                                    <small><?php echo date('d/m/Y', strtotime($item['fecha_envio'])); ?></small>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['doc_id']): ?>
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="verDocumento(<?php echo $item['doc_id']; ?>)">
                                        <i class="bi bi-file-earmark-text"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted">Sin doc.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================= -->
    <!-- TAB: YA PUBLICADOS -->
    <!-- ============================================= -->
    <div class="tab-pane fade" id="contenido-publicados" role="tabpanel">

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Los documentos de <strong><?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?></strong> debían publicarse antes del <strong><?php echo $plazoLegalDisplay; ?></strong> (10° día hábil de <?php echo $meses[$mesSiguiente] . ' ' . $anoSiguiente; ?>).
        </div>

        <?php if (count($itemsPublicados) === 0): ?>
            <div class="alert alert-warning">
                <i class="bi bi-inbox"></i> No hay documentos publicados en este período.
            </div>
        <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle"></i> Items Publicados
                <span class="badge bg-light text-dark ms-2"><?php echo count($itemsPublicados); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">Núm.</th>
                            <th width="20%">Item</th>
                            <th width="8%">Period.</th>
                            <th width="7%">Período</th>
                            <th width="12%">Responsable</th>
                            <th width="10%">Fecha Envío Resp.</th>
                            <th width="10%">Fecha Public. Gescal</th>
                            <th width="8%">En Plazo Legal</th>
                            <th width="5%">Doc.</th>
                            <th width="5%">Verif.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemsPublicados as $item): ?>
                        <?php
                            // Color de fila según plazo (solo mensuales)
                            $rowClass = '';
                            if ($item['periodicidad'] === 'mensual') {
                                $rowClass = $item['en_plazo'] ? 'table-success' : 'table-danger';
                            }
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><small class="text-muted"><?php echo htmlspecialchars($item['numeracion']); ?></small></td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['item_nombre']); ?></strong>
                                <?php if ($item['titulo'] && $item['titulo'] !== $item['item_nombre']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['titulo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($item['periodicidad']); ?></span></td>
                            <td>
                                <small class="text-primary">
                                    <?php
                                    if ($item['periodicidad'] === 'anual') {
                                        echo $item['ano'];
                                    } else {
                                        echo $meses[$item['mes']] . ' ' . $item['ano'];
                                    }
                                    ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($item['responsable']): ?>
                                    <small><?php echo htmlspecialchars($item['responsable']); ?></small>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['fecha_envio']): ?>
                                    <small><?php echo date('d/m/Y', strtotime($item['fecha_envio'])); ?></small>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['fecha_carga_portal']): ?>
                                    <small><?php echo date('d/m/Y H:i', strtotime($item['fecha_carga_portal'])); ?></small>
                                    <?php if ($item['publicado_por']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['publicado_por']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($item['periodicidad'] !== 'mensual'): ?>
                                    <span class="badge bg-light text-muted" title="Solo aplica para mensuales">N/A</span>
                                <?php elseif ($item['en_plazo'] === true): ?>
                                    <span class="badge bg-success fs-6">SI</span>
                                <?php elseif ($item['en_plazo'] === false): ?>
                                    <span class="badge bg-danger fs-6">NO</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['doc_id']): ?>
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="verDocumento(<?php echo $item['doc_id']; ?>)">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['verificador_id']): ?>
                                    <button class="btn btn-sm btn-outline-success"
                                        onclick="verVerificador(<?php echo $item['verificador_id']; ?>)">
                                        <i class="bi bi-image"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal Documento -->
<div class="modal fade" id="modalDocumento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Información del Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDocumento">
                <div class="text-center"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Verificador -->
<div class="modal fade" id="modalVerificador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-image"></i> Verificador de Publicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoVerificador">
                <div class="text-center"><div class="spinner-border text-success"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
function verDocumento(docId) {
    const modal = new bootstrap.Modal(document.getElementById('modalDocumento'));
    document.getElementById('contenidoDocumento').innerHTML =
        '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
    modal.show();
    fetch('<?php echo SITE_URL; ?>admin/publicador/get_documento.php?doc_id=' + docId)
        .then(r => r.text())
        .then(html => { document.getElementById('contenidoDocumento').innerHTML = html; });
}

function verVerificador(verifId) {
    const modal = new bootstrap.Modal(document.getElementById('modalVerificador'));
    document.getElementById('contenidoVerificador').innerHTML =
        '<div class="text-center"><div class="spinner-border text-success"></div></div>';
    modal.show();
    fetch('<?php echo SITE_URL; ?>admin/publicador/get_verificador.php?verif_id=' + verifId)
        .then(r => r.text())
        .then(html => { document.getElementById('contenidoVerificador').innerHTML = html; });
}
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
