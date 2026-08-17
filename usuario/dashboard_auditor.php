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
$direccionFueIndicada = array_key_exists('direccion', $_GET);
$direccionSeleccionada = isset($_GET['direccion']) ? (int)$_GET['direccion'] : 0;
if ($mesSeleccionado < 1 || $mesSeleccionado > 12) $mesSeleccionado = $mesCarga;
if ($anoSeleccionado < 2000 || $anoSeleccionado > 2100) $anoSeleccionado = $anoCarga;

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Direcciones disponibles para el filtro superior.
$direcciones = [];
$direccionesResult = $conn->query("SELECT id, nombre FROM direcciones WHERE activa = 1 ORDER BY nombre");
if ($direccionesResult) {
    while ($direccion = $direccionesResult->fetch_assoc()) {
        $direcciones[] = $direccion;
    }
}

// En la primera entrada, seleccionar la dirección asignada al auditor.
// Si la URL trae "direccion" (incluso 0), respetar la elección manual.
if (!$direccionFueIndicada) {
    $usuarioId = (int)($_SESSION['user_id'] ?? 0);
    if ($usuarioId > 0) {
        $stmtDireccionUsuario = $conn->prepare("SELECT direccion_id FROM usuarios WHERE id = ? LIMIT 1");
        if ($stmtDireccionUsuario) {
            $stmtDireccionUsuario->bind_param('i', $usuarioId);
            $stmtDireccionUsuario->execute();
            $direccionUsuario = $stmtDireccionUsuario->get_result()->fetch_assoc();
            $direccionSeleccionada = (int)($direccionUsuario['direccion_id'] ?? 0);
            $stmtDireccionUsuario->close();
        }
    }
}

// Si llega una dirección inexistente o inactiva, volver a la vista general.
if ($direccionSeleccionada > 0) {
    $direccionValida = false;
    foreach ($direcciones as $direccion) {
        if ((int)$direccion['id'] === $direccionSeleccionada) {
            $direccionValida = true;
            break;
        }
    }
    if (!$direccionValida) $direccionSeleccionada = 0;
}

$filtroDireccionSql = $direccionSeleccionada > 0
    ? ' AND i.direccion_id = ' . $direccionSeleccionada
    : '';

// Obtener TODOS los items activos con los responsables asignados
$query = "
    SELECT
        i.id, i.numeracion, i.nombre, i.periodicidad, i.mes_carga_anual,
        d.nombre AS direccion_nombre,
        CONCAT_WS(' ', dir.nombres, dir.apellidos) AS director_nombre,
        GROUP_CONCAT(DISTINCT u_asig.nombre ORDER BY u_asig.nombre SEPARATOR ', ') as responsables
    FROM items_transparencia i
    LEFT JOIN direcciones d ON i.direccion_id = d.id
    LEFT JOIN directores dir ON d.director_id = dir.id AND dir.activo = 1
    LEFT JOIN item_usuarios iu ON i.id = iu.item_id
    LEFT JOIN usuarios u_asig ON iu.usuario_id = u_asig.id
    WHERE i.activo = 1 {$filtroDireccionSql}
    GROUP BY i.id
    ORDER BY FIELD(i.periodicidad,'mensual','trimestral','semestral','anual'), i.numeracion";

$resultado = $conn->query($query);
$itemsPorPeriodicidad = ['mensual'=>[],'trimestral'=>[],'semestral'=>[],'anual'=>[]];
while ($row = $resultado->fetch_assoc()) {
    $p = $row['periodicidad'] ?? 'mensual';
    if (isset($itemsPorPeriodicidad[$p])) {
        $itemsPorPeriodicidad[$p][] = $row;
    }
}

// Filtrar items según reglas de mes seleccionado
$mesesTrimestral = [3, 6, 9, 12];
$mesesSemestral = [6, 12];
if (!in_array($mesSeleccionado, $mesesTrimestral)) {
    $itemsPorPeriodicidad['trimestral'] = [];
}
if (!in_array($mesSeleccionado, $mesesSemestral)) {
    $itemsPorPeriodicidad['semestral'] = [];
}
$itemsPorPeriodicidad['anual'] = array_filter($itemsPorPeriodicidad['anual'], function($item) use ($mesSeleccionado) {
    return intval($item['mes_carga_anual'] ?? 1) === $mesSeleccionado;
});
$itemsPorPeriodicidad['anual'] = array_values($itemsPorPeriodicidad['anual']);

// Contadores de estado para badges de tabs
function contarEstados($items, $documentoClass, $verificadorClass, $mesS, $anoS, $periodicidad) {
    $rojo = $naranja = $verde = 0;
    foreach ($items as $item) {
        if ($periodicidad === 'anual') {
            $docsResult = $documentoClass->getByItemFollowUpAnual($item['id'], $anoS);
        } else {
            $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesS, $anoS);
        }
        $doc = $docsResult ? $docsResult->fetch_assoc() : null;
        if (!$doc) { $rojo++; continue; }
        $verif = $verificadorClass->getByDocumento($doc['id']);
        if ($verif) { $verde++; } else { $naranja++; }
    }
    return ['rojo'=>$rojo,'naranja'=>$naranja,'verde'=>$verde];
}

$estadosMensual    = contarEstados($itemsPorPeriodicidad['mensual'],   $documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'mensual');
$estadosTrimestral = contarEstados($itemsPorPeriodicidad['trimestral'],$documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'trimestral');
$estadosSemestral  = contarEstados($itemsPorPeriodicidad['semestral'], $documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'semestral');
$estadosAnual      = contarEstados($itemsPorPeriodicidad['anual'],     $documentoClass, $verificadorClass, $mesSeleccionado, $anoSeleccionado, 'anual');

// Función para renderizar la tabla de items de auditor
function renderTablaAuditor($items, $documentoClass, $verificadorClass, $itemPlazoClass, $mesS, $anoS, $periodicidad, $meses) {
    if (empty($items)) {
        echo '<tr><td colspan="9" class="text-center text-muted">No hay items para los filtros seleccionados</td></tr>';
        return;
    }
    foreach ($items as $item) {
        // Obtener documento
        if ($periodicidad === 'anual') {
            $docsResult = $documentoClass->getByItemFollowUpAnual($item['id'], $anoS);
        } else {
            $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesS, $anoS);
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
        // --- Plazos ---
        $plazoEnvioFinal   = $itemPlazoClass->getPlazoFinal($item['id'], $anoS, $mesS, $item['periodicidad']);
        $plazoPublicFinal  = $itemPlazoClass->getPlazoPublicacionFinal($item['id'], $anoS, $mesS, $item['periodicidad']);

        // Fecha Envío con icono de cumplimiento
        if ($doc) {
            if ($plazoEnvioFinal) {
                $icoE = date('Y-m-d', strtotime($doc['fecha_subida'])) <= $plazoEnvioFinal ? '🟢 ' : '🔴 ';
            } else { $icoE = ''; }
            $fechaEnvio = $icoE . date('d/m/Y', strtotime($doc['fecha_subida']));
        } else {
            $fechaEnvio = '<span class="text-muted">—</span>';
        }
        // Fecha Portal con icono de cumplimiento
        if ($verif) {
            if ($plazoPublicFinal) {
                $icoP = date('Y-m-d', strtotime($verif['fecha_carga_portal'])) <= $plazoPublicFinal ? '🟢 ' : '🔴 ';
            } else { $icoP = ''; }
            $fechaPortal = $icoP . date('d/m/Y', strtotime($verif['fecha_carga_portal']));
        } else {
            $fechaPortal = '<span class="text-muted">—</span>';
        }
        $directorNombre = trim($item['director_nombre'] ?? '') ?: 'Sin director asignado';
        $responsablesNombres = trim($item['responsables'] ?? '');
        ?>
        <tr class="<?php echo $rowClass; ?>" data-estado="<?php echo $dataEstado; ?>">
            <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
            <td><?php echo htmlspecialchars($item['nombre']); ?></td>
            <td><small><?php echo htmlspecialchars($item['direccion_nombre'] ?? 'Sin dirección'); ?></small></td>
            <td>
                <button type="button" class="btn btn-sm btn-link p-0"
                        data-bs-toggle="modal" data-bs-target="#modalResponsablesAuditor"
                        data-item="<?php echo htmlspecialchars($item['nombre'], ENT_QUOTES); ?>"
                        data-director="<?php echo htmlspecialchars($directorNombre, ENT_QUOTES); ?>"
                        data-responsables="<?php echo htmlspecialchars($responsablesNombres, ENT_QUOTES); ?>"
                        onclick="verResponsablesAuditor(this)">
                    <i class="bi bi-people"></i> Ver responsables
                </button>
            </td>
            <td><small><?php echo $cargador; ?></small></td>
            <td><small><?php echo $publicador; ?></small></td>
            <td><small><?php echo $fechaEnvio; ?></small></td>
            <td><small><?php echo $fechaPortal; ?></small></td>
            <td>
                <div class="d-flex gap-1 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#modalHistorialAuditor"
                            data-item-id="<?php echo (int)$item['id']; ?>"
                            data-item-nombre="<?php echo htmlspecialchars($item['nombre'], ENT_QUOTES); ?>"
                            data-mes="<?php echo (int)$mesS; ?>"
                            data-ano="<?php echo (int)$anoS; ?>"
                            onclick="mostrarHistorialAuditor(this)"
                            title="Ver bitácora e historial de movimientos">
                        <i class="bi bi-clock-history"></i> Historial
                    </button>
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

<!-- Selector global de mes -->
<div class="card mb-3 border-primary">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <label class="fw-bold mb-0"><i class="bi bi-calendar3"></i> Mes a revisar:</label>
            <select name="mes" class="form-select form-select-sm" style="width:140px;" onchange="this.form.submit()">
                <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m==$mesSeleccionado?'selected':''; ?>><?php echo $meses[$m]; ?></option>
                <?php endfor; ?>
            </select>
            <select name="ano" class="form-select form-select-sm" style="width:90px;" onchange="this.form.submit()">
                <?php for ($a=$anoActual-2;$a<=$anoActual+1;$a++): ?>
                    <option value="<?php echo $a; ?>" <?php echo $a==$anoSeleccionado?'selected':''; ?>><?php echo $a; ?></option>
                <?php endfor; ?>
            </select>
            <label class="fw-bold mb-0 ms-md-2"><i class="bi bi-building"></i> Dirección:</label>
            <select name="direccion" class="form-select form-select-sm" style="min-width:260px;" onchange="this.form.submit()">
                <option value="0">Todas las direcciones</option>
                <?php foreach ($direcciones as $direccion): ?>
                    <option value="<?php echo (int)$direccion['id']; ?>" <?php echo (int)$direccion['id'] === $direccionSeleccionada ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($direccion['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="text-muted ms-2"><?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?></span>
        </form>
    </div>
</div>

<?php
// Determinar primera pestaña activa
$primeraActiva = null;
foreach (['mensual','trimestral','semestral','anual'] as $p) {
    if (!empty($itemsPorPeriodicidad[$p])) { $primeraActiva = $p; break; }
}
?>

<!-- TABS -->
<ul class="nav nav-tabs mb-0" role="tablist">
    <?php if (count($itemsPorPeriodicidad['mensual']) > 0): ?>
    <li class="nav-item">
        <button class="nav-link <?php echo ($primeraActiva === 'mensual') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#tab-mensual-aud" type="button">
            <i class="bi bi-calendar-month"></i> Mensual
            <?php if ($estadosMensual['rojo'] > 0): ?>
                <span class="badge bg-danger ms-1"><?php echo $estadosMensual['rojo']; ?></span>
            <?php endif; ?>
            <?php if ($estadosMensual['naranja'] > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $estadosMensual['naranja']; ?></span>
            <?php endif; ?>
        </button>
    </li>
    <?php endif; ?>
    <?php if (count($itemsPorPeriodicidad['trimestral']) > 0): ?>
    <li class="nav-item">
        <button class="nav-link <?php echo ($primeraActiva === 'trimestral') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#tab-trimestral-aud" type="button">
            <i class="bi bi-calendar-week"></i> Trimestral
            <?php if ($estadosTrimestral['rojo']+$estadosTrimestral['naranja'] > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $estadosTrimestral['rojo']+$estadosTrimestral['naranja']; ?></span>
            <?php endif; ?>
        </button>
    </li>
    <?php endif; ?>
    <?php if (count($itemsPorPeriodicidad['semestral']) > 0): ?>
    <li class="nav-item">
        <button class="nav-link <?php echo ($primeraActiva === 'semestral') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#tab-semestral-aud" type="button">
            <i class="bi bi-calendar2-range"></i> Semestral
        </button>
    </li>
    <?php endif; ?>
    <?php if (count($itemsPorPeriodicidad['anual']) > 0): ?>
    <li class="nav-item">
        <button class="nav-link <?php echo ($primeraActiva === 'anual') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#tab-anual-aud" type="button">
            <i class="bi bi-calendar-check"></i> Anual
        </button>
    </li>
    <?php endif; ?>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3 bg-white">

    <!-- TAB MENSUAL -->
    <?php if (count($itemsPorPeriodicidad['mensual']) > 0): ?>
    <div class="tab-pane fade <?php echo ($primeraActiva === 'mensual') ? 'show active' : ''; ?>" id="tab-mensual-aud" role="tabpanel">
        <?php
            $primerItemAM = !empty($itemsPorPeriodicidad['mensual']) ? $itemsPorPeriodicidad['mensual'][0] : null;
            $plazoTituloEAM = $primerItemAM ? $itemPlazoClass->getPlazoFinal($primerItemAM['id'], $anoSeleccionado, $mesSeleccionado, $primerItemAM['periodicidad']) : null;
            $plazoTituloPAM = $primerItemAM ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemAM['id'], $anoSeleccionado, $mesSeleccionado, $primerItemAM['periodicidad']) : null;
        ?>
        <div class="row align-items-center mb-3">
            <div class="col">
                <h5>Items Mensuales &mdash; <?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?>
                    <?php if ($plazoTituloEAM || $plazoTituloPAM): ?>
                    <small class="text-muted fw-normal ms-2">
                        <?php if ($plazoTituloEAM): ?>Plazo Interno: <?php echo date('d/m/Y', strtotime($plazoTituloEAM)); ?><?php endif; ?>
                        <?php if ($plazoTituloEAM && $plazoTituloPAM): ?> &nbsp;|&nbsp; <?php endif; ?>
                        <?php if ($plazoTituloPAM): ?>Plazo Ley: <?php echo date('d/m/Y', strtotime($plazoTituloPAM)); ?><?php endif; ?>
                    </small>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-auto">
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="mes" value="<?php echo $mesSeleccionado; ?>">
                    <input type="hidden" name="ano" value="<?php echo $anoSeleccionado; ?>">
                    <input type="hidden" name="direccion" value="<?php echo $direccionSeleccionada; ?>">
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
                    <th>Num.</th><th>Nombre Item</th><th>Dirección</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['mensual'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'mensual', $meses); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB TRIMESTRAL -->
    <?php if (count($itemsPorPeriodicidad['trimestral']) > 0): ?>
    <div class="tab-pane fade <?php echo ($primeraActiva === 'trimestral') ? 'show active' : ''; ?>" id="tab-trimestral-aud" role="tabpanel">
        <?php
            $primerItemAT = !empty($itemsPorPeriodicidad['trimestral']) ? $itemsPorPeriodicidad['trimestral'][0] : null;
            $plazoTituloEAT = $primerItemAT ? $itemPlazoClass->getPlazoFinal($primerItemAT['id'], $anoSeleccionado, $mesSeleccionado, $primerItemAT['periodicidad']) : null;
            $plazoTituloPAT = $primerItemAT ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemAT['id'], $anoSeleccionado, $mesSeleccionado, $primerItemAT['periodicidad']) : null;
        ?>
        <div class="mb-3">
            <h5>Items Trimestrales
                <?php if ($plazoTituloEAT || $plazoTituloPAT): ?>
                <small class="text-muted fw-normal ms-2">
                    <?php if ($plazoTituloEAT): ?>Plazo Interno: <?php echo date('d/m/Y', strtotime($plazoTituloEAT)); ?><?php endif; ?>
                    <?php if ($plazoTituloEAT && $plazoTituloPAT): ?> &nbsp;|&nbsp; <?php endif; ?>
                    <?php if ($plazoTituloPAT): ?>Plazo Ley: <?php echo date('d/m/Y', strtotime($plazoTituloPAT)); ?><?php endif; ?>
                </small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Dirección</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['trimestral'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'trimestral', $meses); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB SEMESTRAL -->
    <?php if (count($itemsPorPeriodicidad['semestral']) > 0): ?>
    <div class="tab-pane fade <?php echo ($primeraActiva === 'semestral') ? 'show active' : ''; ?>" id="tab-semestral-aud" role="tabpanel">
        <?php
            $primerItemAS = !empty($itemsPorPeriodicidad['semestral']) ? $itemsPorPeriodicidad['semestral'][0] : null;
            $plazoTituloEAS = $primerItemAS ? $itemPlazoClass->getPlazoFinal($primerItemAS['id'], $anoSeleccionado, $mesSeleccionado, $primerItemAS['periodicidad']) : null;
            $plazoTituloPAS = $primerItemAS ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemAS['id'], $anoSeleccionado, $mesSeleccionado, $primerItemAS['periodicidad']) : null;
        ?>
        <div class="mb-3">
            <h5>Items Semestrales
                <?php if ($plazoTituloEAS || $plazoTituloPAS): ?>
                <small class="text-muted fw-normal ms-2">
                    <?php if ($plazoTituloEAS): ?>Plazo Interno: <?php echo date('d/m/Y', strtotime($plazoTituloEAS)); ?><?php endif; ?>
                    <?php if ($plazoTituloEAS && $plazoTituloPAS): ?> &nbsp;|&nbsp; <?php endif; ?>
                    <?php if ($plazoTituloPAS): ?>Plazo Ley: <?php echo date('d/m/Y', strtotime($plazoTituloPAS)); ?><?php endif; ?>
                </small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Dirección</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['semestral'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'semestral', $meses); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB ANUAL -->
    <?php if (count($itemsPorPeriodicidad['anual']) > 0): ?>
    <div class="tab-pane fade <?php echo ($primeraActiva === 'anual') ? 'show active' : ''; ?>" id="tab-anual-aud" role="tabpanel">
        <?php
            $primerItemAA = !empty($itemsPorPeriodicidad['anual']) ? $itemsPorPeriodicidad['anual'][0] : null;
            $mesAnualHeader = $primerItemAA ? intval($primerItemAA['mes_carga_anual'] ?? 1) : $mesSeleccionado;
            $plazoTituloEAA = $primerItemAA ? $itemPlazoClass->getPlazoFinal($primerItemAA['id'], $anoSeleccionado, $mesAnualHeader, $primerItemAA['periodicidad']) : null;
            $plazoTituloPAA = $primerItemAA ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemAA['id'], $anoSeleccionado, $mesAnualHeader, $primerItemAA['periodicidad']) : null;
        ?>
        <div class="mb-3">
            <h5>Items Anuales &mdash; <?php echo $anoSeleccionado; ?>
                <?php if ($plazoTituloEAA || $plazoTituloPAA): ?>
                <small class="text-muted fw-normal ms-2">
                    <?php if ($plazoTituloEAA): ?>Plazo Interno: <?php echo date('d/m/Y', strtotime($plazoTituloEAA)); ?><?php endif; ?>
                    <?php if ($plazoTituloEAA && $plazoTituloPAA): ?> &nbsp;|&nbsp; <?php endif; ?>
                    <?php if ($plazoTituloPAA): ?>Plazo Ley: <?php echo date('d/m/Y', strtotime($plazoTituloPAA)); ?><?php endif; ?>
                </small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light"><tr>
                    <th>Num.</th><th>Nombre Item</th><th>Dirección</th><th>Responsable(s)</th>
                    <th>Cargó</th><th>Publicó</th><th>Fecha Envío</th><th>Fecha Portal</th><th>Acciones</th>
                </tr></thead>
                <tbody>
                    <?php renderTablaAuditor($itemsPorPeriodicidad['anual'], $documentoClass, $verificadorClass, $itemPlazoClass, $mesSeleccionado, $anoSeleccionado, 'anual', $meses); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Modal de bitácora e historial (solo lectura) -->
<div class="modal fade" id="modalHistorialAuditor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHistorialAuditorTitle">Historial de Movimientos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">Tipo</th>
                                <th width="30%">Movimiento</th>
                                <th width="25%">Usuario</th>
                                <th width="40%">Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody id="historialAuditorBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de responsables del ítem -->
<div class="modal fade" id="modalResponsablesAuditor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-people"></i> Responsables del ítem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="responsablesItemNombre"></p>
                <div class="mb-3">
                    <strong><i class="bi bi-person-badge"></i> Director/a</strong>
                    <div id="responsablesDirector" class="mt-1"></div>
                </div>
                <div>
                    <strong><i class="bi bi-person-check"></i> Usuarios asignados al ítem</strong>
                    <ul id="responsablesUsuarios" class="mb-0 mt-2"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
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
function escaparHtmlAuditor(valor) {
    const elemento = document.createElement('div');
    elemento.textContent = valor == null ? '' : String(valor);
    return elemento.innerHTML;
}

function mostrarHistorialAuditor(button) {
    const itemId = button.dataset.itemId;
    const itemNombre = button.dataset.itemNombre || '';
    const mes = button.dataset.mes;
    const ano = button.dataset.ano;
    const cuerpo = document.getElementById('historialAuditorBody');

    document.getElementById('modalHistorialAuditorTitle').textContent = `Historial: ${itemNombre}`;
    cuerpo.innerHTML = '<tr><td colspan="4" class="text-center text-muted"><span class="spinner-border spinner-border-sm"></span> Cargando...</td></tr>';

    fetch(`get_historial.php?item_id=${encodeURIComponent(itemId)}&mes=${encodeURIComponent(mes)}&ano=${encodeURIComponent(ano)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.error || 'No se pudo cargar el historial');
            if (!data.historial || data.historial.length === 0) {
                cuerpo.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay registros de movimientos</td></tr>';
                return;
            }

            cuerpo.innerHTML = data.historial.map(movimiento => {
                let icono = '<i class="bi bi-question-circle"></i>';
                let clase = 'bg-dark';
                let tipo = movimiento.tipo || 'Movimiento';
                if (movimiento.tipo === 'documento_cargado') {
                    icono = '<i class="bi bi-file-earmark-text"></i>'; clase = 'bg-primary'; tipo = 'Documento Cargado';
                } else if (movimiento.tipo === 'verificador_agregado') {
                    icono = '<i class="bi bi-check-circle"></i>'; clase = 'bg-success'; tipo = 'Verificador Agregado';
                } else if (movimiento.tipo === 'sin_movimiento') {
                    icono = '<i class="bi bi-dash-circle"></i>'; clase = 'bg-secondary'; tipo = 'Sin Movimiento';
                } else if (movimiento.tipo === 'documento_observado') {
                    icono = '<i class="bi bi-x-circle"></i>'; clase = 'bg-danger'; tipo = 'Documento Observado';
                }

                const fechaObjeto = new Date(movimiento.fecha);
                const fecha = Number.isNaN(fechaObjeto.getTime()) ? '—' : fechaObjeto.toLocaleString('es-CL');
                return `<tr>
                    <td><span class="badge ${clase}">${icono}</span></td>
                    <td><strong>${escaparHtmlAuditor(tipo)}</strong></td>
                    <td>${escaparHtmlAuditor(movimiento.usuario || '—')}</td>
                    <td>${escaparHtmlAuditor(fecha)}</td>
                </tr>
                <tr class="table-light"><td colspan="4"><small>
                    <strong>Descripción:</strong> ${escaparHtmlAuditor(movimiento.descripcion || '—')}<br>
                    <strong>Detalle:</strong> ${escaparHtmlAuditor(movimiento.detalle || '—')}
                </small></td></tr>`;
            }).join('');
        })
        .catch(error => {
            cuerpo.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${escaparHtmlAuditor(error.message)}</td></tr>`;
        });
}

function verResponsablesAuditor(button) {
    const itemNombre = button.dataset.item || '';
    const director = button.dataset.director || 'Sin director asignado';
    const responsables = button.dataset.responsables || '';
    const lista = document.getElementById('responsablesUsuarios');

    document.getElementById('responsablesItemNombre').textContent = itemNombre;
    document.getElementById('responsablesDirector').textContent = director;
    lista.replaceChildren();

    if (!responsables) {
        const elemento = document.createElement('li');
        elemento.className = 'text-muted';
        elemento.textContent = 'Sin usuarios asignados';
        lista.appendChild(elemento);
        return;
    }

    responsables.split(', ').forEach(nombre => {
        const elemento = document.createElement('li');
        elemento.textContent = nombre;
        lista.appendChild(elemento);
    });
}

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
