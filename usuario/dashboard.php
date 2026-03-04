<?php
require_once '../includes/check_auth.php';
require_login();
require_once '../includes/header.php';

$user_id = $current_user['id'];
$user_perfil = $current_user['perfil'];
$user_direccion_id = $current_user['direccion_id'] ?? null;
$conn = $db->getConnection();

// Fechas
$mesActual = (int)date('m');
$anoActual = (int)date('Y');
$mesCarga = $mesActual - 1;
$anoCarga = $anoActual;
if ($mesCarga < 1) {
    $mesCarga = 12;
    $anoCarga = $anoActual - 1;
}

// Selector de mes (para items mensuales)
$mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : $mesCarga;
$anoSeleccionado = isset($_GET['ano']) ? (int)$_GET['ano'] : $anoCarga;

if ($mesSeleccionado < 1 || $mesSeleccionado > 12) $mesSeleccionado = $mesCarga;
if ($anoSeleccionado < 2000 || $anoSeleccionado > 2100) $anoSeleccionado = $anoCarga;

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Query SQL optimizado: Obtener items con sus documentos, plazos y verificadores
// Todos los usuarios ven todos los items y documentos (para transparencia)
$params = [];
$types = '';

$query = "
    SELECT 
        i.id as item_id,
        i.numeracion,
        i.nombre as item_nombre,
        i.periodicidad,
        i.descripcion as item_descripcion,
        d.id as doc_id,
        d.titulo as doc_titulo,
        d.archivo as doc_archivo,
        d.estado as doc_estado,
        d.fecha_subida,
        d.usuario_id as doc_usuario_id,
        ds.fecha_envio,
        ds.mes as doc_mes,
        ds.ano as doc_ano,
        u.nombre as usuario_nombre,
        ip.plazo_interno,
        vp.id as verificador_id,
        vp.fecha_carga_portal
    FROM items_transparencia i
    LEFT JOIN item_usuarios iu ON i.id = iu.item_id
    LEFT JOIN usuarios u_asig ON iu.usuario_id = u_asig.id
    LEFT JOIN documentos d ON i.id = d.item_id
    LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN item_plazos ip ON i.id = ip.item_id 
        AND ip.ano = ? AND ip.mes = ?
    LEFT JOIN verificadores_publicador vp ON d.id = vp.documento_id
    WHERE i.activo = 1
    ORDER BY 
        FIELD(i.periodicidad, 'mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia'),
        i.numeracion";

$params[] = $anoSeleccionado;
$params[] = $mesSeleccionado;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...array_values($params));
$stmt->execute();
$resultado = $stmt->get_result();

// Agrupar por periodicidad y por item (un item puede tener múltiples documentos)
$itemsPorPeriodicidad = [
    'mensual' => [],
    'trimestral' => [],
    'semestral' => [],
    'anual' => [],
    'ocurrencia' => []
];

$contadores = [
    'mensual' => 0,
    'trimestral' => 0,
    'semestral' => 0,
    'anual' => 0,
    'ocurrencia' => 0
];

$itemsCache = [];

while ($row = $resultado->fetch_assoc()) {
    $itemId = $row['item_id'];
    $periodicidad = $row['periodicidad'];
    
    // Filtrar documentos por período según periodicidad
    $esDocumentoValido = false;
    $docMes = (int)$row['doc_mes'];
    $docAno = (int)$row['doc_ano'];
    
    if ($row['doc_id'] && $docMes > 0 && $docAno > 0) {
        if ($periodicidad === 'mensual') {
            // Para mensual: comparar con mes/año seleccionado (del selector o actual)
            $esDocumentoValido = ($docMes === $mesSeleccionado && $docAno === $anoSeleccionado);
        } elseif ($periodicidad === 'trimestral') {
            // Para trimestral: mismo trimestre del año actual
            $esDocumentoValido = ($docAno === $anoActual && (int)floor(($docMes - 1) / 3) === (int)floor(($mesActual - 1) / 3));
        } elseif ($periodicidad === 'semestral') {
            // Para semestral: mismo semestre del año actual
            $esDocumentoValido = ($docAno === $anoActual && (int)floor(($docMes - 1) / 6) === (int)floor(($mesActual - 1) / 6));
        } elseif ($periodicidad === 'anual') {
            // Para anual: mismo año actual
            $esDocumentoValido = ($docAno === $anoActual);
        } elseif ($periodicidad === 'ocurrencia') {
            // Para ocurrencia: mes/año actual
            $esDocumentoValido = ($docMes === $mesActual && $docAno === $anoActual);
        }
    }
    
    // Solo agregar si es documento válido o no hay documento
    if (!isset($itemsCache[$itemId])) {
        $itemsCache[$itemId] = [
            'item_id' => $row['item_id'],
            'numeracion' => $row['numeracion'],
            'item_nombre' => $row['item_nombre'],
            'periodicidad' => $periodicidad,
            'plazo_interno' => $row['plazo_interno'],
            'doc_id' => null,
            'doc_titulo' => null,
            'doc_estado' => null,
            'doc_archivo' => null,
            'fecha_envio' => null,
            'usuario_nombre' => null,
            'verificador_id' => null,
            'fecha_carga_portal' => null
        ];
        
        if (!$row['doc_id']) {
            $contadores[$periodicidad]++;
        }
    }
    
    // Actualizar con documento si es válido
    if ($esDocumentoValido && $row['doc_id']) {
        if (!$itemsCache[$itemId]['doc_id']) {
            $contadores[$periodicidad]--; // Ya no está pendiente
        }
        
        $itemsCache[$itemId]['doc_id'] = $row['doc_id'];
        $itemsCache[$itemId]['doc_titulo'] = $row['doc_titulo'];
        $itemsCache[$itemId]['doc_estado'] = $row['doc_estado'];
        $itemsCache[$itemId]['doc_archivo'] = $row['doc_archivo'];
        $itemsCache[$itemId]['fecha_envio'] = $row['fecha_envio'];
        $itemsCache[$itemId]['usuario_nombre'] = $row['usuario_nombre'];
        $itemsCache[$itemId]['verificador_id'] = $row['verificador_id'];
        $itemsCache[$itemId]['fecha_carga_portal'] = $row['fecha_carga_portal'];
    }
}

// Distribuir en arrays por periodicidad
foreach ($itemsCache as $item) {
    $itemsPorPeriodicidad[$item['periodicidad']][] = $item;
}

$documentosPendientes = $contadores;

// Split items en Pendientes (sin doc O sin fecha_envio) / Enviados (con doc Y fecha_envio)
$itemsPendientesPorPer = [];
$itemsEnviadosPorPer   = [];
foreach (['mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia'] as $_per) {
    $itemsPendientesPorPer[$_per] = array_values(array_filter(
        $itemsPorPeriodicidad[$_per],
        fn($i) => !($i['doc_id'] && $i['fecha_envio'])
    ));
    $itemsEnviadosPorPer[$_per] = array_values(array_filter(
        $itemsPorPeriodicidad[$_per],
        fn($i) => $i['doc_id'] && $i['fecha_envio']
    ));
}

$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
if ($success) unset($_SESSION['success']);
if ($error) unset($_SESSION['error']);
?>

<!-- HEADER -->
<div class="page-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; margin: -1rem -1rem 2rem -1rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-inbox-fill" style="font-size: 1.5rem; color: white;"></i>
        </div>
        <div>
            <h1 style="color: white; font-size: 1.5rem; font-weight: 600; margin: 0;">
                Mi Panel de Carga
            </h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 0.875rem; margin: 0;">
                Bienvenido <?php echo htmlspecialchars($current_user['nombre']); ?>, gestiona tus documentos de transparencia
            </p>
        </div>
    </div>
</div>

<!-- ALERTAS -->
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- TABS PERIODICIDAD -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-mensual" data-bs-toggle="tab" data-bs-target="#mensual" type="button" role="tab">
                    <i class="bi bi-calendar-month"></i> Mensual
                    <?php if ($documentosPendientes['mensual'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['mensual']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-trimestral" data-bs-toggle="tab" data-bs-target="#trimestral" type="button" role="tab">
                    <i class="bi bi-calendar3"></i> Trimestral
                    <?php if ($documentosPendientes['trimestral'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['trimestral']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-semestral" data-bs-toggle="tab" data-bs-target="#semestral" type="button" role="tab">
                    <i class="bi bi-calendar2-range"></i> Semestral
                    <?php if ($documentosPendientes['semestral'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['semestral']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-anual" data-bs-toggle="tab" data-bs-target="#anual" type="button" role="tab">
                    <i class="bi bi-calendar-event"></i> Anual
                    <?php if ($documentosPendientes['anual'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['anual']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-ocurrencia" data-bs-toggle="tab" data-bs-target="#ocurrencia" type="button" role="tab">
                    <i class="bi bi-calendar-check"></i> Ocurrencia
                    <?php if ($documentosPendientes['ocurrencia'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['ocurrencia']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <!-- TAB MENSUAL -->
            <div class="tab-pane fade show active" id="mensual" role="tabpanel">
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5><i class="bi bi-calendar-month text-primary"></i> Items Mensuales</h5>
                    </div>
                    <div class="col-auto">
                        <form method="GET" class="d-flex gap-2">
                            <select name="mes" class="form-select form-select-sm" style="max-width: 140px;" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($m === $mesSeleccionado) ? 'selected' : ''; ?>>
                                        <?php echo $meses[$m]; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="ano" class="form-select form-select-sm" style="max-width: 90px;" onchange="this.form.submit()">
                                <?php for ($a = $anoActual - 2; $a <= $anoActual; $a++): ?>
                                    <option value="<?php echo $a; ?>" <?php echo ($a === $anoSeleccionado) ? 'selected' : ''; ?>>
                                        <?php echo $a; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <?php $penMen = $itemsPendientesPorPer['mensual']; $envMen = $itemsEnviadosPorPer['mensual']; ?>
                <!-- Sub-tabs Pendientes / Enviados -->
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-men-pend" type="button">
                            <i class="bi bi-clock-history"></i> Documentos Pendientes
                            <?php if (count($penMen) > 0): ?><span class="badge bg-danger ms-1"><?php echo count($penMen); ?></span><?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-men-env" type="button">
                            <i class="bi bi-check-circle"></i> Documentos Enviados
                            <?php if (count($envMen) > 0): ?><span class="badge bg-success ms-1"><?php echo count($envMen); ?></span><?php endif; ?>
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Pendientes mensual -->
                    <div class="tab-pane fade show active" id="pane-men-pend">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="6%">Núm.</th>
                                        <th width="22%">Item</th>
                                        <th width="10%">Mes Carga</th>
                                        <th width="10%">Plazo Interno</th>
                                        <th width="10%">Estado</th>
                                        <th width="12%">Fecha Envío</th>
                                        <th width="30%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($penMen)): ?>
                                        <tr><td colspan="7" class="text-center text-success"><i class="bi bi-check-circle"></i> Todos los documentos de este período han sido enviados.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($penMen as $item):
                                            $plazoTexto = $item['plazo_interno'] ? date('d/m/Y', strtotime($item['plazo_interno'])) : '<span class="text-muted">-</span>';
                                            $fechaEnvio = $item['fecha_envio'] ? date('d/m/Y H:i', strtotime($item['fecha_envio'])) : '<span class="text-muted">-</span>';
                                        ?>
                                        <tr class="table-warning">
                                            <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($item['item_nombre']); ?></div>
                                                <button class="btn btn-xs btn-outline-secondary mt-1"
                                                        data-bs-toggle="modal" data-bs-target="#modalHistorial"
                                                        onclick="mostrarHistorial(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesSeleccionado; ?>, <?php echo $anoSeleccionado; ?>)">
                                                    <i class="bi bi-clock-history"></i> Historial
                                                </button>
                                            </td>
                                            <td><?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?></td>
                                            <td><?php echo $plazoTexto; ?></td>
                                            <td><span class="badge bg-secondary">Sin Cargar</span></td>
                                            <td><?php echo $fechaEnvio; ?></td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <button class="btn btn-sm btn-primary"
                                                            data-bs-toggle="modal" data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesSeleccionado; ?>, <?php echo $anoSeleccionado; ?>)">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Docto
                                                    </button>
                                                    <?php if ($user_perfil === 'cargador_informacion'): ?>
                                                    <button class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal" data-bs-target="#modalSinMovimiento"
                                                            onclick="seleccionarSinMovimiento(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesSeleccionado; ?>, <?php echo $anoSeleccionado; ?>)">
                                                        <i class="bi bi-slash-circle"></i> Sin Movimiento
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Enviados mensual -->
                    <div class="tab-pane fade" id="pane-men-env">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="6%">Núm.</th>
                                        <th width="20%">Item</th>
                                        <th width="9%">Mes Carga</th>
                                        <th width="9%">Plazo Interno</th>
                                        <th width="10%">Estado</th>
                                        <th width="11%">Fecha Envío</th>
                                        <th width="11%">Carga Portal</th>
                                        <th width="24%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($envMen)): ?>
                                        <tr><td colspan="8" class="text-center text-muted">No hay documentos enviados en este período.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($envMen as $item):
                                            $esSM = ($item['doc_archivo'] === 'sin_movimiento');
                                            $plazoTexto  = $item['plazo_interno']      ? date('d/m/Y', strtotime($item['plazo_interno']))           : '<span class="text-muted">-</span>';
                                            $fechaEnvio  = $item['fecha_envio']         ? date('d/m/Y H:i', strtotime($item['fecha_envio']))          : '<span class="text-muted">-</span>';
                                            $cargaPortal = $item['fecha_carga_portal']  ? date('d/m/Y H:i', strtotime($item['fecha_carga_portal']))   : '<span class="text-muted">Pendiente</span>';
                                            if ($esSM) {
                                                $estadoBadge = '<span class="badge bg-secondary"><i class="bi bi-slash-circle"></i> Sin Movimiento</span>';
                                            } elseif ($item['doc_estado'] === 'aprobado') {
                                                $estadoBadge = '<span class="badge bg-success">Aprobado</span>';
                                            } elseif ($item['doc_estado'] === 'rechazado') {
                                                $estadoBadge = '<span class="badge bg-danger">Rechazado</span>';
                                            } else {
                                                $estadoBadge = '<span class="badge bg-warning text-dark">Pendiente</span>';
                                            }
                                            if (!$esSM && $item['usuario_nombre']) {
                                                $estadoBadge .= '<br><small class="text-muted">Por: ' . htmlspecialchars($item['usuario_nombre']) . '</small>';
                                            }
                                        ?>
                                        <tr class="table-success">
                                            <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($item['item_nombre']); ?></div>
                                                <button class="btn btn-xs btn-outline-secondary mt-1"
                                                        data-bs-toggle="modal" data-bs-target="#modalHistorial"
                                                        onclick="mostrarHistorial(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesSeleccionado; ?>, <?php echo $anoSeleccionado; ?>)">
                                                    <i class="bi bi-clock-history"></i> Historial
                                                </button>
                                            </td>
                                            <td><?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?></td>
                                            <td><?php echo $plazoTexto; ?></td>
                                            <td><?php echo $estadoBadge; ?></td>
                                            <td><?php echo $fechaEnvio; ?></td>
                                            <td><?php echo $cargaPortal; ?></td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <?php if (!$esSM): ?>
                                                        <a href="descargar_documento.php?doc_id=<?php echo $item['doc_id']; ?>"
                                                           class="btn btn-sm btn-success" target="_blank">
                                                            <i class="bi bi-file-earmark-check"></i> Ver Doc
                                                        </a>
                                                        <?php if ($item['verificador_id']): ?>
                                                            <button class="btn btn-sm btn-info"
                                                                    data-bs-toggle="modal" data-bs-target="#modalVerVerificador"
                                                                    onclick="verVerificador(<?php echo $item['verificador_id']; ?>)">
                                                                <i class="bi bi-patch-check"></i> Ver Verif
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><i class="bi bi-slash-circle"></i> Sin Movimiento declarado</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- /inner tab-content mensual -->
            </div><!-- /mensual tab-pane -->

            <!-- TAB TRIMESTRAL -->
            <?php
            $penTrim = $itemsPendientesPorPer['trimestral']; $envTrim = $itemsEnviadosPorPer['trimestral'];
            $penSem  = $itemsPendientesPorPer['semestral'];  $envSem  = $itemsEnviadosPorPer['semestral'];
            $penAnu  = $itemsPendientesPorPer['anual'];      $envAnu  = $itemsEnviadosPorPer['anual'];
            $penOcu  = $itemsPendientesPorPer['ocurrencia']; $envOcu  = $itemsEnviadosPorPer['ocurrencia'];
            $tabsOtros = [
                'trimestral' => ['id'=>'trimestral','icon'=>'bi-calendar3','color'=>'text-success','label'=>'Trimestrales','pen'=>$penTrim,'env'=>$envTrim,'prefix'=>'trim'],
                'semestral'  => ['id'=>'semestral', 'icon'=>'bi-calendar2-range','color'=>'text-info',   'label'=>'Semestrales', 'pen'=>$penSem, 'env'=>$envSem, 'prefix'=>'sem'],
                'anual'      => ['id'=>'anual',     'icon'=>'bi-calendar-event', 'color'=>'text-warning','label'=>'Anuales',     'pen'=>$penAnu, 'env'=>$envAnu, 'prefix'=>'anu'],
                'ocurrencia' => ['id'=>'ocurrencia','icon'=>'bi-calendar-check', 'color'=>'text-danger', 'label'=>'Ocurrencia',  'pen'=>$penOcu, 'env'=>$envOcu, 'prefix'=>'ocu'],
            ];
            foreach ($tabsOtros as $tConf): ?>
            <div class="tab-pane fade" id="<?php echo $tConf['id']; ?>" role="tabpanel">
                <h5 class="mb-3"><i class="bi <?php echo $tConf['icon'].' '.$tConf['color']; ?>"></i> Items <?php echo $tConf['label']; ?></h5>
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-<?php echo $tConf['prefix']; ?>-pend" type="button">
                        <i class="bi bi-clock-history"></i> Documentos Pendientes
                        <?php if (count($tConf['pen']) > 0): ?><span class="badge bg-danger ms-1"><?php echo count($tConf['pen']); ?></span><?php endif; ?>
                    </button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-<?php echo $tConf['prefix']; ?>-env" type="button">
                        <i class="bi bi-check-circle"></i> Documentos Enviados
                        <?php if (count($tConf['env']) > 0): ?><span class="badge bg-success ms-1"><?php echo count($tConf['env']); ?></span><?php endif; ?>
                    </button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-<?php echo $tConf['prefix']; ?>-pend">
                        <div class="table-responsive"><table class="table table-hover table-bordered">
                            <thead class="table-light"><tr>
                                <th width="8%">Núm.</th><th width="28%">Item</th><th width="12%">Plazo Interno</th>
                                <th width="12%">Estado</th><th width="13%">Fecha Envío</th><th width="27%">Acciones</th>
                            </tr></thead>
                            <tbody>
                            <?php if (empty($tConf['pen'])): ?>
                                <tr><td colspan="6" class="text-center text-success"><i class="bi bi-check-circle"></i> Todos los documentos de este período han sido enviados.</td></tr>
                            <?php else: foreach ($tConf['pen'] as $item):
                                $plazoTexto = $item['plazo_interno'] ? date('d/m/Y', strtotime($item['plazo_interno'])) : '<span class="text-muted">-</span>';
                                $fechaEnvio = $item['fecha_envio']   ? date('d/m/Y', strtotime($item['fecha_envio']))   : '<span class="text-muted">-</span>';
                            ?>
                            <tr class="table-warning">
                                <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['item_nombre']); ?></td>
                                <td><?php echo $plazoTexto; ?></td>
                                <td><span class="badge bg-secondary">Sin Cargar</span></td>
                                <td><?php echo $fechaEnvio; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargar"
                                            onclick="seleccionarItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>')">
                                        <i class="bi bi-upload"></i> Cargar Docto
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table></div>
                    </div>
                    <div class="tab-pane fade" id="pane-<?php echo $tConf['prefix']; ?>-env">
                        <div class="table-responsive"><table class="table table-hover table-bordered">
                            <thead class="table-light"><tr>
                                <th width="8%">Núm.</th><th width="25%">Item</th><th width="11%">Plazo Interno</th>
                                <th width="11%">Estado</th><th width="12%">Fecha Envío</th><th width="12%">Carga Portal</th><th width="21%">Acciones</th>
                            </tr></thead>
                            <tbody>
                            <?php if (empty($tConf['env'])): ?>
                                <tr><td colspan="7" class="text-center text-muted">No hay documentos enviados aún.</td></tr>
                            <?php else: foreach ($tConf['env'] as $item):
                                $esSM = ($item['doc_archivo'] === 'sin_movimiento');
                                $plazoTexto  = $item['plazo_interno']     ? date('d/m/Y', strtotime($item['plazo_interno']))         : '<span class="text-muted">-</span>';
                                $fechaEnvio  = $item['fecha_envio']        ? date('d/m/Y', strtotime($item['fecha_envio']))           : '<span class="text-muted">-</span>';
                                $cargaPortal = $item['fecha_carga_portal'] ? date('d/m/Y', strtotime($item['fecha_carga_portal']))    : '<span class="text-muted">-</span>';
                                $estadoBadge = $esSM ? '<span class="badge bg-secondary"><i class="bi bi-slash-circle"></i> Sin Movimiento</span>'
                                    : ($item['doc_estado'] === 'aprobado' ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>');
                            ?>
                            <tr class="table-success">
                                <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['item_nombre']); ?></td>
                                <td><?php echo $plazoTexto; ?></td>
                                <td><?php echo $estadoBadge; ?></td>
                                <td><?php echo $fechaEnvio; ?></td>
                                <td><?php echo $cargaPortal; ?></td>
                                <td>
                                    <?php if (!$esSM): ?>
                                        <a href="descargar_documento.php?doc_id=<?php echo $item['doc_id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                            <i class="bi bi-file-check"></i> Ver Doc
                                        </a>
                                        <?php if ($item['verificador_id']): ?>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerVerificador"
                                                    onclick="verVerificador(<?php echo $item['verificador_id']; ?>)">
                                                <i class="bi bi-patch-check"></i> Ver Verif
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="bi bi-slash-circle"></i> Sin Movimiento</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table></div>
                    </div>
                </div>
            </div>
            <?php endforeach; /* tabsOtros */ ?>

        </div><!-- /tab-content periodicidad -->
    </div>
</div>

<!-- MODAL SIN MOVIMIENTO -->
<div class="modal fade" id="modalSinMovimiento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-slash-circle"></i> Confirmar Sin Movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="sin_movimiento.php">
                <div class="modal-body">
                    <input type="hidden" name="item_id"   id="smItemId">
                    <input type="hidden" name="mes_carga" id="smMes">
                    <input type="hidden" name="ano_carga" id="smAno">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>¿Confirma que no hay movimiento para este período?</strong>
                    </div>
                    <p>Item: <strong id="smItemNombre">—</strong></p>
                    <p>Período: <strong id="smPeriodoTexto">—</strong></p>
                    <p class="text-muted small">Esta acción registrará "Sin Movimiento" para el período seleccionado y moverá el item a la pestaña <em>Documentos Enviados</em>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-slash-circle"></i> Confirmar Sin Movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL CARGAR DOCUMENTO -->
<div class="modal fade" id="modalCargar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title mb-2">Cargar Documento</h5>
                    <small class="text-muted">
                        Está cargando el item: <strong id="itemNombreModal">-</strong>
                        <span id="mesPeriodo"></span>
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="enviar_documento.php">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="itemIdInput">
                    <input type="hidden" name="mes_carga" id="mesCargaInput">

                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título del Documento <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required placeholder="Ej: Remuneraciones Enero 2024">
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Comentario (Opcional)</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Información adicional y/o observaciones del documento..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="archivo" class="form-label">Seleccionar Archivo <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="archivo" name="archivo" required accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png">
                        <small class="text-muted d-block mt-2">✓ Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG (máximo 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Enviar Documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

function seleccionarItem(itemId, itemNombre, mesCarga = null) {
    document.getElementById('itemIdInput').value = itemId;
    document.getElementById('itemNombreModal').textContent = itemNombre;
    
    // Rellenar automáticamente el título con: Item Name + Mes
    let titulo = itemNombre;
    if (mesCarga) {
        const anoActual = new Date().getFullYear();
        titulo = itemNombre + ' - ' + meses[mesCarga] + ' ' + anoActual;
    }
    document.getElementById('titulo').value = titulo;
    
    // Mostrar mes/período
    let periodoTexto = '';
    if (mesCarga) {
        document.getElementById('mesCargaInput').value = mesCarga;
        const anoActual = new Date().getFullYear();
        periodoTexto = ' para el mes de <strong>' + meses[mesCarga] + ' ' + anoActual + '</strong>';
    }
    
    const mesPeriodoSpan = document.getElementById('mesPeriodo');
    if (periodoTexto) {
        mesPeriodoSpan.innerHTML = periodoTexto;
    } else {
        mesPeriodoSpan.innerHTML = '';
    }
}

function seleccionarSinMovimiento(itemId, itemNombre, mes, ano) {
    document.getElementById('smItemId').value = itemId;
    document.getElementById('smMes').value = mes;
    document.getElementById('smAno').value = ano;
    document.getElementById('smItemNombre').textContent = itemNombre;
    document.getElementById('smPeriodoTexto').textContent = meses[mes] + ' ' + ano;
}

// Función para mostrar historial
function mostrarHistorial(itemId, itemNombre, mes, ano) {
    // Cargar historial via AJAX
    fetch(`get_historial.php?item_id=${itemId}&mes=${mes}&ano=${ano}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const historialBody = document.getElementById('historialBody');
                historialBody.innerHTML = '';
                
                if (data.historial.length === 0) {
                    historialBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay registros de movimientos</td></tr>';
                } else {
                    data.historial.forEach((mov, index) => {
                        const icono = mov.tipo === 'documento_cargado' ? '<i class="bi bi-file-earmark-text"></i>' : '<i class="bi bi-check-circle"></i>';
                        const iconoClass = mov.tipo === 'documento_cargado' ? 'bg-primary' : 'bg-success';
                        const fecha = new Date(mov.fecha).toLocaleString('es-CL');
                        
                        const row = `
                            <tr>
                                <td><span class="badge ${iconoClass}">${icono}</span></td>
                                <td><strong>${mov.tipo === 'documento_cargado' ? 'Documento Cargado' : 'Verificador Agregado'}</strong></td>
                                <td>${mov.usuario}</td>
                                <td>${fecha}</td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="4">
                                    <small>
                                        <strong>Descripción:</strong> ${mov.descripcion}<br>
                                        <strong>Detalle:</strong> ${mov.detalle}
                                    </small>
                                </td>
                            </tr>
                        `;
                        historialBody.innerHTML += row;
                    });
                }
                
                // Actualizar título del modal
                document.getElementById('modalHistorialTitle').textContent = `Historial: ${itemNombre}`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar el historial');
        });
}

function verVerificador(verifId) {
    fetch(`get_verificador.php?verif_id=${verifId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalVerVerificadorBody').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalVerVerificadorBody').innerHTML = '<p class="text-danger">Error al cargar el verificador</p>';
        });
}

function verEnPantallaCompleta(imageSrc) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;z-index:10000;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position:relative;max-height:90vh;overflow:auto;';
    
    const img = document.createElement('img');
    img.src = imageSrc;
    img.style.cssText = 'max-width:100%;max-height:90vh;display:block;';
    
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = 'position:absolute;top:10px;right:20px;background:white;border:none;font-size:30px;cursor:pointer;padding:0;width:40px;height:40px;';
    closeBtn.onclick = () => overlay.remove();
    
    container.appendChild(img);
    container.appendChild(closeBtn);
    overlay.appendChild(container);
    document.body.appendChild(overlay);
    
    overlay.addEventListener('click', (e) => {
        if(e.target === overlay) overlay.remove();
    });
    
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape') overlay.remove();
    });
}
</script>

<!-- MODAL: HISTORIAL -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHistorialTitle">Historial de Movimientos</h5>
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
                        <tbody id="historialBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Ver Verificador -->
<div class="modal fade" id="modalVerVerificador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-check"></i> Verificador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVerVerificadorBody">
                Cargando...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

