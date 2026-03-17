<?php
require_once '../includes/check_auth.php';
require_login();

// Solo auditor puede ver esta página
if ($current_profile !== 'auditor') {
    header('Location: ' . SITE_URL . 'usuario/dashboard.php');
    exit;
}

require_once '../includes/header.php';
require_once '../classes/Item.php';
require_once '../classes/ItemPlazo.php';
require_once '../classes/ItemConPlazo.php';
require_once '../classes/Documento.php';
require_once '../classes/Verificador.php';
require_once '../classes/PlazoCalculator.php';

$conn = $db->getConnection();
$itemPlazoClass   = new ItemPlazo($conn);
$itemConPlazoClass = new ItemConPlazo($conn);
$documentoClass   = new Documento($conn);
$verificadorClass = new Verificador($conn);

// Fechas
$mesActual  = (int)date('m');
$anoActual  = (int)date('Y');
$mesCarga   = $mesActual - 1;
$anoCarga   = $anoActual;
if ($mesCarga < 1) { $mesCarga = 12; $anoCarga--; }

$mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : $mesCarga;
$anoSeleccionado = isset($_GET['ano']) ? (int)$_GET['ano'] : $anoCarga;
if ($mesSeleccionado < 1 || $mesSeleccionado > 12) $mesSeleccionado = $mesCarga;
if ($anoSeleccionado < 2000 || $anoSeleccionado > 2100) $anoSeleccionado = $anoCarga;

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Obtener TODOS los items activos con los responsables asignados
$query = "
    SELECT
        i.id, i.numeracion, i.nombre, i.periodicidad,
        GROUP_CONCAT(DISTINCT u_asig.nombre ORDER BY u_asig.nombre SEPARATOR ', ') as responsables
    FROM items_transparencia i
    LEFT JOIN item_usuarios iu ON i.id = iu.item_id
    LEFT JOIN usuarios u_asig ON iu.usuario_id = u_asig.id
    WHERE i.activo = 1
    GROUP BY i.id
    ORDER BY FIELD(i.periodicidad,'mensual','trimestral','semestral','anual','ocurrencia'), i.numeracion";

$resultado = $conn->query($query);
$itemsPorPeriodicidad = ['mensual'=>[],'trimestral'=>[],'semestral'=>[],'anual'=>[],'ocurrencia'=>[]];
while ($row = $resultado->fetch_assoc()) {
    $p = $row['periodicidad'] ?? 'ocurrencia';
    if (isset($itemsPorPeriodicidad[$p])) {
        $itemsPorPeriodicidad[$p][] = $row;
    }
}

// Contadores de estado para badges de tabs
function contarEstados($items, $documentoClass, $verificadorClass, $mesS, $anoS, $periodicidad, $anoActual, $mesActual) {
    $rojo = $naranja = $verde = 0;
    foreach ($items as $item) {
        if ($periodicidad === 'anual') {
            $docsResult = $documentoClass->getByItemFollowUpAnual($item['id'], $anoActual);
        } elseif ($periodicidad === 'mensual') {
            $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesS, $anoS);
        } else {
            $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesActual, $anoActual);
        }
        $doc = $docsResult ? $docsResult->fetch_assoc() : null;
        if (!$doc) { $rojo++; continue; }
        $verif = $verificadorClass->getByDocumento($doc['id']);
        if ($verif) { $verde++; } else { $naranja++; }
    }
    return ['rojo'=>$rojo,'naranja'=>$naranja,'verde'=>$verde];
}

$estadosMensual    = contarEstados($itemsPorPeriodicidad['mensual'],   $documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'mensual',     $anoActual, $mesActual);
$estadosTrimestral = contarEstados($itemsPorPeriodicidad['trimestral'],$documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'trimestral',  $anoActual, $mesActual);
$estadosSemestral  = contarEstados($itemsPorPeriodicidad['semestral'], $documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'semestral',   $anoActual, $mesActual);
$estadosAnual      = contarEstados($itemsPorPeriodicidad['anual'],     $documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'anual',       $anoActual, $mesActual);
$estadosOcurrencia = contarEstados($itemsPorPeriodicidad['ocurrencia'],$documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'ocurrencia',  $anoActual, $mesActual);

// Función para renderizar la tabla de items de auditor
function renderTablaAuditor($items, $documentoClass, $verificadorClass, $itemPlazoClass, $mesS, $anoS, $periodicidad, $anoActual, $mesActual, $meses) {
    if (empty($items)) {
        echo '<tr><td colspan="9" class="text-center text-muted">No hay items</td></tr>';
        return;
    }
    foreach ($items as $item) {
        // Obtener documento
        if ($periodicidad === 'anual') {
            $docsResult = $documentoClass->getByItemFollowUpAnual($item['id'], $anoActual);
        } elseif ($periodicidad === 'mensual') {
            $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesS, $anoS);
        } else {
            $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesActual, $anoActual);
        }
        $doc = $docsResult ? $docsResult->fetch_assoc() : null;

        // Obtener verificador
        $verif = $doc ? $verificadorClass->getByDocumento($doc['id']) : null;

        // Color según estado
        if ($verif)        { $rowClass = 'table-success';  $dataEstado = 'verde'; }
        elseif ($doc)      { $rowClass = 'table-warning';  $dataEstado = 'naranja'; }
        else               { $rowClass = 'table-danger';   $dataEstado = 'rojo'; }

        // Datos a mostrar
        $cargador    = $doc   ? htmlspecialchars($doc['usuario_nombre'] ?? '—')       : '<span class="text-muted">Sin doc</span>';
        $publicador  = $verif ? htmlspecialchars($verif['publicador_nombre'] ?? '—') : '<span class="text-muted">—</span>';
        $fechaEnvio  = $doc   ? date('d/m/Y', strtotime($doc['fecha_subida']))        : '<span class="text-muted">—</span>';
        $fechaPortal = $verif ? date('d/m/Y', strtotime($verif['fecha_carga_portal'])): '<span class="text-muted">—</span>';
        $responsables = $item['responsables'] ? htmlspecialchars($item['responsables']) : '<span class="text-muted">Sin asignar</span>';

        // --- Plazos ---
        $plazoEnvioFinal   = $itemPlazoClass->getPlazoFinal($item['id'], $anoS, $mesS, $item['periodicidad']);
        $plazoPublicFinal  = $itemPlazoClass->getPlazoPublicacionFinal($item['id'], $anoS, $mesS, $item['periodicidad']);

        // Build plazo HTML — color based on actual upload date vs deadline (not today vs deadline)
        ob_start();
        if ($plazoEnvioFinal) {
            if ($doc) {
                $enPlazoE = date('Y-m-d', strtotime($doc['fecha_subida'])) <= $plazoEnvioFinal;
                $cE = $enPlazoE ? 'text-success' : 'text-danger';
                $iE = $enPlazoE ? '🟢 ' : '🔴 ';
            } else {
                $cE = 'text-muted'; $iE = '';
            }
            echo '<small><strong>Plazo Interno:</strong> <span class="'.$cE.'">'.$iE.date('d/m/Y', strtotime($plazoEnvioFinal)).'</span></small><br>';
        }
        if ($plazoPublicFinal) {
            if ($verif) {
                $enPlazoP = date('Y-m-d', strtotime($verif['fecha_carga_portal'])) <= $plazoPublicFinal;
                $cP = $enPlazoP ? 'text-success' : 'text-danger';
                $iP = $enPlazoP ? '🟢 ' : '🔴 ';
            } else {
                $cP = 'text-muted'; $iP = '';
            }
            echo '<small><strong>Plazo Ley:</strong> <span class="'.$cP.'">'.$iP.date('d/m/Y', strtotime($plazoPublicFinal)).'</span></small>';
        }
        if (!$plazoEnvioFinal && !$plazoPublicFinal) echo '<span class="text-muted small">—</span>';
        $plazoHtml = ob_get_clean();
        ?>
        <tr class="<?php echo $rowClass; ?>" data-estado="<?php echo $dataEstado; ?>">
            <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
            <td><?php echo htmlspecialchars($item['nombre']); ?></td>
            <td><small><?php echo $responsables; ?></small></td>
            <td><small><?php echo $cargador; ?></small></td>
            <td><small><?php echo $publicador; ?></small></td>
            <td><small><?php echo $fechaEnvio; ?></small></td>
            <td><small><?php echo $fechaPortal; ?></small></td>
            <td><?php echo $plazoHtml; ?></td>
            <td>
                <div class="d-flex gap-1 flex-wrap">
                    <?php if ($doc): ?>
                        <a href="descargar_documento.php?doc_id=<?php echo $doc['id']; ?>"
                           class="btn btn-sm btn-outline-primary" title="Ver documento" style="white-space:nowrap;">
                            <i class="bi bi-file-earmark-text"></i> Ver Doc
                        </a>
                    <?php endif; ?>
                    <?php if ($verif): ?>
                        <button type="button" class="btn btn-sm btn-success"
                                data-bs-toggle="modal" data-bs-target="#modalVerVerificador"
                                onclick="verVerificador(<?php echo $verif['id']; ?>)"
                                style="white-space:nowrap;">
                            <i class="bi bi-check-circle"></i> Ver Verif
                        </button>
                    <?php elseif ($doc): ?>
                        <span class="badge bg-warning text-dark">Sin verificador</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Sin documento</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
}
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-clipboard2-check" style="color:#6f42c1;"></i> Panel Auditor</h1>
            <small class="text-muted">Vista de seguimiento — solo lectura</small>
        </div>
        <div class="col-auto">
            <!-- Leyenda -->
            <span class="badge bg-danger me-1"><i class="bi bi-circle-fill"></i> Sin documento</span>
            <span class="badge bg-warning text-dark me-1"><i class="bi bi-circle-fill"></i> Sin verificador</span>
            <span class="badge bg-success"><i class="bi bi-circle-fill"></i> Publicado</span>
        </div>
    </div>
</div>

<!-- TABS -->
<ul class="nav nav-tabs mb-0" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-mensual-aud" type="button">
            <i class="bi bi-calendar-month"></i> Mensual
            <?php if ($estadosMensual['rojo'] > 0): ?>
                <span class="badge bg-danger ms-1"><?php echo $estadosMensual['rojo']; ?></span>
            <?php endif; ?>
            <?php if ($estadosMensual['naranja'] > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $estadosMensual['naranja']; ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-trimestral-aud" type="button">
            <i class="bi bi-calendar-week"></i> Trimestral
            <?php if ($estadosTrimestral['rojo']+$estadosTrimestral['naranja'] > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $estadosTrimestral['rojo']+$estadosTrimestral['naranja']; ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-semestral-aud" type="button">
            <i class="bi bi-calendar2-range"></i> Semestral
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-anual-aud" type="button">
            <i class="bi bi-calendar-check"></i> Anual
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ocurrencia-aud" type="button">
            <i class="bi bi-lightning"></i> Ocurrencia
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3 bg-white">

    <!-- TAB MENSUAL -->
    <div class="tab-pane fade show active" id="tab-mensual-aud" role="tabpanel">
        <div class="row align-items-center mb-3">
            <div class="col"><h5>Items Mensuales — <?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?></h5></div>
            <div class="col-auto">
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <select name="mes" class="form-select form-select-sm" style="width:130px;" onchange="this.form.submit()">
                        <?php for ($m=1;$m<=12;$m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m==$mesSeleccionado?'selected':''; ?>><?php echo $meses[$m]; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="ano" class="form-select form-select-sm" style="width:90px;" onchange="this.form.submit()">
                        <?php for ($a=$anoActual-2;$a<=$anoActual+1;$a++): ?>
                            <option value="<?php echo $a; ?>" <?php echo $a==$anoSeleccionado?'selected':''; ?>><?php echo $a; ?></option>
                        <?php endfor; ?>
                    </select>
                    <!-- Filtros de estado -->
                    <div class="btn-group btn-group-sm ms-2" id="filtroEstado">
                        <button type="button" class="btn btn-outline-secondary active" data-estado="todos" onclick="filtrarAuditor('todos',this)">Todos</button>
                        <button type="button" class="btn btn-outline-danger"           data-estado="rojo"  onclick="filtrarAuditor('rojo',this)"><i class="bi bi-circle-fill"></i></button>
                        <button type="button" class="btn btn-outline-warning"          data-estado="naranja" onclick="filtrarAuditor('naranja',this)"><i class="bi bi-circle-fill"></i></button>
                        <button type="button" class="btn btn-outline-success"          data-estado="verde" onclick="filtrarAuditor('verde',this)"><i class="bi bi-circle-fill"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm" id="tablaMensualAud">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Plazos <small class="text-muted">(Envío / Public.)</small></th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['mensual'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'mensual', $anoActual, $mesActual, $meses); ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB TRIMESTRAL -->
    <div class="tab-pane fade" id="tab-trimestral-aud" role="tabpanel">
        <div class="mb-3"><h5>Items Trimestrales</h5></div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Plazos <small class="text-muted">(Envío / Public.)</small></th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['trimestral'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'trimestral', $anoActual, $mesActual, $meses); ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB SEMESTRAL -->
    <div class="tab-pane fade" id="tab-semestral-aud" role="tabpanel">
        <div class="mb-3"><h5>Items Semestrales</h5></div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Plazos <small class="text-muted">(Envío / Public.)</small></th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['semestral'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'semestral', $anoActual, $mesActual, $meses); ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB ANUAL -->
    <div class="tab-pane fade" id="tab-anual-aud" role="tabpanel">
        <div class="mb-3"><h5>Items Anuales — <?php echo $anoActual; ?></h5></div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Plazos <small class="text-muted">(Envío / Public.)</small></th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['anual'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'anual', $anoActual, $mesActual, $meses); ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB OCURRENCIA -->
    <div class="tab-pane fade" id="tab-ocurrencia-aud" role="tabpanel">
        <div class="mb-3"><h5>Items por Ocurrencia</h5></div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Plazos <small class="text-muted">(Envío / Public.)</small></th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['ocurrencia'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'ocurrencia', $anoActual, $mesActual, $meses); ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Ver Verificador (solo lectura) -->
<div class="modal fade" id="modalVerVerificador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle"></i> Verificador de Publicación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="verificadorContent">
                <div class="text-center text-muted">Cargando...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verVerificador(verificadorId) {
    const content = document.getElementById('verificadorContent');
    content.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-success"></div></div>';
    fetch('../admin/get_verificador.php?id=' + verificadorId)
        .then(r => r.json())
        .then(data => {
            if (!data || data.error) { content.innerHTML = '<div class="alert alert-danger">Error al cargar el verificador.</div>'; return; }
            let imgHtml = '';
            const ext = data.archivo_verificador ? data.archivo_verificador.split('.').pop().toLowerCase() : '';
            if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                imgHtml = `<img src="../uploads/${data.archivo_verificador}" class="img-fluid rounded border" style="max-height:400px;" alt="Verificador">`;
            } else if (ext === 'pdf') {
                imgHtml = `<embed src="../uploads/${data.archivo_verificador}" type="application/pdf" width="100%" height="400px">`;
            }
            content.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Publicado por:</strong><br>${data.publicador_nombre || '—'}</div>
                    <div class="col-md-6"><strong>Fecha en portal:</strong><br>${data.fecha_carga_portal || '—'}</div>
                </div>
                ${data.comentarios ? `<div class="mb-3"><strong>Comentarios:</strong><br><p class="text-muted">${data.comentarios}</p></div>` : ''}
                ${imgHtml ? `<div class="text-center mt-2">${imgHtml}</div>` : ''}
                <div class="mt-3 text-center">
                    <a href="../uploads/${data.archivo_verificador}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download"></i> Descargar archivo
                    </a>
                </div>`;
        })
        .catch(() => { content.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>'; });
}

function filtrarAuditor(estado, btn) {
    document.querySelectorAll('#filtroEstado .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#tablaMensualAud tbody tr').forEach(row => {
        row.style.display = (estado === 'todos' || row.dataset.estado === estado) ? '' : 'none';
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
