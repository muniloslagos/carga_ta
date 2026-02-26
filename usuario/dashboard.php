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
// Filtrar por usuario si es cargador_informacion
$whereUsuario = '';
$params = [];
$types = '';

if ($user_perfil === 'cargador_informacion') {
    // Cargador solo ve items de su dirección
    $whereUsuario = 'AND u_asig.id = ?';
    $params[] = $user_id;
    $types .= 'i';
}

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
    LEFT JOIN usuarios u_asig ON iu.usuario_id = u_asig.id $whereUsuario
    LEFT JOIN documentos d ON i.id = d.item_id
    LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN item_plazos ip ON i.id = ip.item_id 
        AND ip.ano = ? AND ip.mes = ?
    LEFT JOIN verificadores_publicador vp ON d.id = vp.documento_id
    WHERE (u_asig.id IS NOT NULL OR ? = 'publicador')
    ORDER BY 
        FIELD(i.periodicidad, 'mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia'),
        i.numeracion";

$params[] = $anoSeleccionado;
$params[] = $mesSeleccionado;
$params[] = $user_perfil;
$types .= 'iis';

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
    
    if ($row['doc_id']) {
        if ($periodicidad === 'mensual' && $docMes === $mesSeleccionado && $docAno === $anoSeleccionado) {
            $esDocumentoValido = true;
        } elseif ($periodicidad === 'trimestral' && $docAno === $anoActual && (int)floor(($docMes - 1) / 3) === (int)floor(($mesActual - 1) / 3)) {
            $esDocumentoValido = true;
        } elseif ($periodicidad === 'semestral' && $docAno === $anoActual && (int)floor(($docMes - 1) / 6) === (int)floor(($mesActual - 1) / 6)) {
            $esDocumentoValido = true;
        } elseif ($periodicidad === 'anual' && $docAno === $anoActual) {
            $esDocumentoValido = true;
        } elseif ($periodicidad === 'ocurrencia' && $docMes === $mesActual && $docAno === $anoActual) {
            $esDocumentoValido = true;
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

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="6%">Núm.</th>
                                <th width="20%">Item</th>
                                <th width="10%">Mes Carga</th>
                                <th width="10%">Plazo Interno</th>
                                <th width="10%">Estado</th>
                                <th width="12%">Fecha Envío</th>
                                <th width="12%">Carga Portal</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itemsPorPeriodicidad['mensual'])): ?>
                                <tr><td colspan="8" class="text-center text-muted">No tienes items mensuales asignados</td></tr>
                            <?php else: ?>
                                <?php foreach ($itemsPorPeriodicidad['mensual'] as $item): 
                                    $rowClass = $item['doc_id'] ? 'table-success' : 'table-warning';
                                    $plazoTexto = $item['plazo_interno'] ? date('d/m/Y', strtotime($item['plazo_interno'])) : '<span class="text-muted">-</span>';
                                    $fechaEnvio = $item['fecha_envio'] ? date('d/m/Y H:i', strtotime($item['fecha_envio'])) : '<span class="text-muted">-</span>';
                                    $cargaPortal = $item['fecha_carga_portal'] ? date('d/m/Y H:i', strtotime($item['fecha_carga_portal'])) : '<span class="text-muted">Pendiente</span>';
                                    
                                    $estadoBadge = '<span class="badge bg-secondary">Sin Cargar</span>';
                                    if ($item['doc_id']) {
                                        if ($item['doc_estado'] === 'aprobado') {
                                            $estadoBadge = '<span class="badge bg-success">Aprobado</span>';
                                        } elseif ($item['doc_estado'] === 'rechazado') {
                                            $estadoBadge = '<span class="badge bg-danger">Rechazado</span>';
                                        } else {
                                            $estadoBadge = '<span class="badge bg-warning text-dark">Pendiente</span>';
                                        }
                                    }
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($item['item_nombre']); ?></div>
                                        <button class="btn btn-xs btn-outline-secondary mt-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalHistorial"
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
                                            <?php if ($item['doc_id']): ?>
                                                <a href="descargar_documento.php?doc_id=<?php echo $item['doc_id']; ?>" 
                                                   class="btn btn-sm btn-success" target="_blank">
                                                    <i class="bi bi-file-earmark-check"></i> Ver Doc
                                                </a>
                                                <?php if ($item['verificador_id']): ?>
                                                    <button class="btn btn-sm btn-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $item['verificador_id']; ?>)">
                                                        <i class="bi bi-patch-check"></i> Ver Verif
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalCargar"
                                                        onclick="seleccionarItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesSeleccionado; ?>, <?php echo $anoSeleccionado; ?>)">
                                                    <i class="bi bi-cloud-upload"></i> Cargar Docto
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

            <!-- TAB TRIMESTRAL -->
            <div class="tab-pane fade" id="trimestral" role="tabpanel">
                <h5 class="mb-3"><i class="bi bi-calendar3 text-success"></i> Items Trimestrales</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Núm.</th>
                                <th width="25%">Item</th>
                                <th width="12%">Plazo Interno</th>
                                <th width="12%">Estado</th>
                                <th width="13%">Fecha Envío</th>
                                <th width="13%">Carga Portal</th>
                                <th width="17%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itemsPorPeriodicidad['trimestral'])): ?>
                                <tr><td colspan="7" class="text-center text-muted">No tienes items trimestrales asignados</td></tr>
                            <?php else: ?>
                                <?php foreach ($itemsPorPeriodicidad['trimestral'] as $item): 
                                    $rowClass = $item['doc_id'] ? 'table-success' : 'table-warning';
                                    $plazoTexto = $item['plazo_interno'] ? date('d/m/Y', strtotime($item['plazo_interno'])) : '<span class="text-muted">-</span>';
                                    $fechaEnvio = $item['fecha_envio'] ? date('d/m/Y', strtotime($item['fecha_envio'])) : '<span class="text-muted">-</span>';
                                    $cargaPortal = $item['fecha_carga_portal'] ? date('d/m/Y', strtotime($item['fecha_carga_portal'])) : '<span class="text-muted">-</span>';
                                    
                                    $estadoBadge = !$item['doc_id'] ? '<span class="badge bg-secondary">Sin Cargar</span>' : ($item['doc_estado'] === 'aprobado' ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>');
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['item_nombre']); ?></td>
                                    <td><?php echo $plazoTexto; ?></td>
                                    <td><?php echo $estadoBadge; ?></td>
                                    <td><?php echo $fechaEnvio; ?></td>
                                    <td><?php echo $cargaPortal; ?></td>
                                    <td>
                                        <?php if ($item['doc_id']): ?>
                                            <a href="descargar_documento.php?doc_id=<?php echo $item['doc_id']; ?>" class="btn btn-sm btn-success"><i class="bi bi-file-check"></i> Ver</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargar"
                                                    onclick="seleccionarItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesActual; ?>, <?php echo $anoActual; ?>)">
                                                <i class="bi bi-upload"></i> Cargar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB SEMESTRAL -->
            <div class="tab-pane fade" id="semestral" role="tabpanel">
                <h5 class="mb-3"><i class="bi bi-calendar2-range text-info"></i> Items Semestrales</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Núm.</th>
                                <th width="25%">Item</th>
                                <th width="12%">Plazo Interno</th>
                                <th width="12%">Estado</th>
                                <th width="13%">Fecha Envío</th>
                                <th width="13%">Carga Portal</th>
                                <th width="17%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itemsPorPeriodicidad['semestral'])): ?>
                                <tr><td colspan="7" class="text-center text-muted">No tienes items semestrales asignados</td></tr>
                            <?php else: ?>
                                <?php foreach ($itemsPorPeriodicidad['semestral'] as $item): 
                                    $rowClass = $item['doc_id'] ? 'table-success' : 'table-warning';
                                    $plazoTexto = $item['plazo_interno'] ? date('d/m/Y', strtotime($item['plazo_interno'])) : '<span class="text-muted">-</span>';
                                    $fechaEnvio = $item['fecha_envio'] ? date('d/m/Y', strtotime($item['fecha_envio'])) : '<span class="text-muted">-</span>';
                                    $cargaPortal = $item['fecha_carga_portal'] ? date('d/m/Y', strtotime($item['fecha_carga_portal'])) : '<span class="text-muted">-</span>';
                                    
                                    $estadoBadge = !$item['doc_id'] ? '<span class="badge bg-secondary">Sin Cargar</span>' : ($item['doc_estado'] === 'aprobado' ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>');
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['item_nombre']); ?></td>
                                    <td><?php echo $plazoTexto; ?></td>
                                    <td><?php echo $estadoBadge; ?></td>
                                    <td><?php echo $fechaEnvio; ?></td>
                                    <td><?php echo $cargaPortal; ?></td>
                                    <td>
                                        <?php if ($item['doc_id']): ?>
                                            <a href="descargar_documento.php?doc_id=<?php echo $item['doc_id']; ?>" class="btn btn-sm btn-success"><i class="bi bi-file-check"></i> Ver</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargar"
                                                    onclick="seleccionarItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesActual; ?>, <?php echo $anoActual; ?>)">
                                                <i class="bi bi-upload"></i> Cargar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
             </div>

            <!-- TAB ANUAL -->
            <div class="tab-pane fade" id="anual" role="tabpanel">
                <h5 class="mb-3"><i class="bi bi-calendar-event text-warning"></i> Items Anuales</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Núm.</th>
                                <th width="25%">Item</th>
                                <th width="12%">Plazo Interno</th>
                                <th width="12%">Estado</th>
                                <th width="13%">Fecha Envío</th>
                                <th width="13%">Carga Portal</th>
                                <th width="17%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itemsPorPeriodicidad['anual'])): ?>
                                <tr><td colspan="7" class="text-center text-muted">No tienes items anuales asignados</td></tr>
                            <?php else: ?>
                                <?php foreach ($itemsPorPeriodicidad['anual'] as $item): 
                                    $rowClass = $item['doc_id'] ? 'table-success' : 'table-warning';
                                    $plazoTexto = $item['plazo_interno'] ? date('d/m/Y', strtotime($item['plazo_interno'])) : '<span class="text-muted">-</span>';
                                    $fechaEnvio = $item['fecha_envio'] ? date('d/m/Y', strtotime($item['fecha_envio'])) : '<span class="text-muted">-</span>';
                                    $cargaPortal = $item['fecha_carga_portal'] ? date('d/m/Y', strtotime($item['fecha_carga_portal'])) : '<span class="text-muted">-</span>';
                                    
                                    $estadoBadge = !$item['doc_id'] ? '<span class="badge bg-secondary">Sin Cargar</span>' : ($item['doc_estado'] === 'aprobado' ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>');
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['item_nombre']); ?></td>
                                    <td><?php echo $plazoTexto; ?></td>
                                    <td><?php echo $estadoBadge; ?></td>
                                    <td><?php echo $fechaEnvio; ?></td>
                                    <td><?php echo $cargaPortal; ?></td>
                                    <td>
                                        <?php if ($item['doc_id']): ?>
                                            <a href="descargar_documento.php?doc_id=<?php echo $item['doc_id']; ?>" class="btn btn-sm btn-success"><i class="bi bi-file-check"></i> Ver</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargar"
                                                    onclick="seleccionarItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', null, <?php echo $anoActual; ?>)">
                                                <i class="bi bi-upload"></i> Cargar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB OCURRENCIA -->
            <div class="tab-pane fade" id="ocurrencia" role="tabpanel">
                <h5 class="mb-3"><i class="bi bi-calendar-check text-danger"></i> Items por Ocurrencia</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Núm.</th>
                                <th width="25%">Item</th>
                                <th width="12%">Plazo Interno</th>
                                <th width="12%">Estado</th>
                                <th width="13%">Fecha Envío</th>
                                <th width="13%">Carga Portal</th>
                                <th width="17%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itemsPorPeriodicidad['ocurrencia'])): ?>
                                <tr><td colspan="7" class="text-center text-muted">No tienes items por ocurrencia asignados</td></tr>
                            <?php else: ?>
                                <?php foreach ($itemsPorPeriodicidad['ocurrencia'] as $item): 
                                    $rowClass = $item['doc_id'] ? 'table-success' : 'table-warning';
                                    $plazoTexto = $item['plazo_interno'] ? date('d/m/Y', strtotime($item['plazo_interno'])) : '<span class="text-muted">-</span>';
                                    $fechaEnvio = $item['fecha_envio'] ? date('d/m/Y', strtotime($item['fecha_envio'])) : '<span class="text-muted">-</span>';
                                    $cargaPortal = $item['fecha_carga_portal'] ? date('d/m/Y', strtotime($item['fecha_carga_portal'])) : '<span class="text-muted">-</span>';
                                    
                                    $estadoBadge = !$item['doc_id'] ? '<span class="badge bg-secondary">Sin Cargar</span>' : ($item['doc_estado'] === 'aprobado' ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>');
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['item_nombre']); ?></td>
                                    <td><?php echo $plazoTexto; ?></td>
                                    <td><?php echo $estadoBadge; ?></td>
                                    <td><?php echo $fechaEnvio; ?></td>
                                    <td><?php echo $cargaPortal; ?></td>
                                    <td>
                                        <?php if ($item['doc_id']): ?>
                                            <a href="descargar_documento.php?doc_id=<?php echo $item['doc_id']; ?>" class="btn btn-sm btn-success"><i class="bi bi-file-check"></i> Ver</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargar"
                                                    onclick="seleccionarItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>', <?php echo $mesActual; ?>, <?php echo $anoActual; ?>)">
                                                <i class="bi bi-upload"></i> Cargar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>

            <!-- TAB SEMESTRAL -->
            <div class="tab-pane fade" id="semestral" role="tabpanel">
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5>Items Semestrales</h5>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Numeración</th>
                                <th width="3%" style="text-align: center;">Historial</th>
                                <th width="22%">Nombre Item</th>
                                <th width="15%">Plazo Interno</th>
                                <th width="15%">Fecha Envío</th>
                                <th width="15%">Carga Portal</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($itemsPorPeriodicidad['semestral'])) {
                                foreach ($itemsPorPeriodicidad['semestral'] as $item) {
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoActual, $mesActual);
                                    
                                    // Obtener último documento
                                    $docsResult = $itemConPlazoClass->getDocumentosPorMes($item['id'], $userIdFiltro, $anoActual, $mesActual);
                                    $ultimoDoc = $docsResult->fetch_assoc();
                                    
                                    // Obtener verificador si existe
                                    $verificador = null;
                                    if ($ultimoDoc) {
                                        $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                    }
                                    
                                    // Calcular plazo final (automático o personalizado)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoActual, $mesActual, $item['periodicidad']);
                                    $plazoInterno = $plazoFinal ? date('d/m/Y', strtotime($plazoFinal)) : '<span class="text-muted">No configurado</span>';
                                    
                                    $cargaPortal = $verificador ? date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal'])) : '<span class="text-muted">Pendiente</span>';
                                    $fechaEnvio = $ultimoDoc ? date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio'])) : '<span class="text-muted">Sin envío</span>';
                                    $rowClass = $ultimoDoc ? 'table-success' : 'table-danger';
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>">
                                        <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                        <td style="text-align: center;">
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalHistorial"
                                                    onclick="mostrarHistorial(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', <?php echo $mesActual; ?>, <?php echo $anoActual; ?>)"
                                                    title="Ver historial de movimientos">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo $plazoInterno; ?></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($ultimoDoc): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['id']; ?>" class="btn btn-sm btn-success" title="Ver documento">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Documento
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-primary" style="white-space: nowrap;" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>');">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Documento
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($verificador): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $verificador['id']; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-check-circle"></i> Ver Verif
                                                    </button>
                                                <?php elseif ($ultimoDoc): ?>
                                                    <span class="badge bg-danger" title="El documento <?php echo htmlspecialchars($item['nombre']); ?> está a la espera de ser cargado por el Publicador de Transparencia Activa (Gescal)." data-bs-toggle="tooltip" data-bs-placement="top">No cargado a TA</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted">No hay items semestrales asignados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB ANUAL -->
            <div class="tab-pane fade" id="anual" role="tabpanel">
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5>Items Anuales</h5>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Numeración</th>
                                <th width="3%" style="text-align: center;">Historial</th>
                                <th width="22%">Nombre Item</th>
                                <th width="15%">Plazo Interno</th>
                                <th width="15%">Fecha Envío</th>
                                <th width="15%">Carga Portal</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($itemsPorPeriodicidad['anual'])) {
                                foreach ($itemsPorPeriodicidad['anual'] as $item) {
                                    // Para ANUAL, usar mes=1 (enero)
                                    $mesAnual = 1;
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoActual, $mesAnual);
                                    
                                    // Obtener último documento (SIN mes, por año completo)
                                    $docsResult = $documentoClass->getByItemFollowUpAnual($item['id'], $anoActual);
                                    $ultimoDoc = null;
                                    $tieneDocDelUsuario = false;
                                    $verificador = null;
                                    
                                    if ($docsResult && $docsResult->num_rows > 0) {
                                        $ultimoDoc = $docsResult->fetch_assoc();
                                        if ($current_user['perfil'] === 'publicador') {
                                            // Para publicador, si existe documento está cubierto
                                            $tieneDocDelUsuario = true;
                                            // Obtener verificador si existe
                                            $verificador = $verificadorClass->getByDocumento($ultimoDoc['documento_id']);
                                        } else {
                                            // Para cargador, verificar que sea suyo
                                            if ((int)$ultimoDoc['usuario_id'] === (int)$user_id) {
                                                $tieneDocDelUsuario = true;
                                                // Obtener verificador si existe
                                                $verificador = $verificadorClass->getByDocumento($ultimoDoc['documento_id']);
                                            }
                                        }
                                    }
                                    
                                    // Calcular plazo final (automático o personalizado)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoActual, $mesAnual, $item['periodicidad']);
                                    $plazoInterno = $plazoFinal ? date('d/m/Y', strtotime($plazoFinal)) : '<span class="text-muted">No configurado</span>';
                                    
                                    $cargaPortal = $verificador ? date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal'])) : '<span class="text-muted">Pendiente</span>';
                                    $fechaEnvio = $ultimoDoc ? date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio'])) : '<span class="text-muted">Sin envío</span>';
                                    $rowClass = $tieneDocDelUsuario ? 'table-success' : 'table-danger';
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>">
                                        <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                        <td style="text-align: center;">
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalHistorial"
                                                    onclick="mostrarHistorial(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', 1, <?php echo $anoActual; ?>)"
                                                    title="Ver historial de movimientos">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo $plazoInterno; ?></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($tieneDocDelUsuario): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['documento_id']; ?>" class="btn btn-sm btn-success" title="Ver documento">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Documento
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-primary" style="white-space: nowrap;" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', 1);">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Documento
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($verificador): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $verificador['id']; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-check-circle"></i> Ver Verif
                                                    </button>
                                                <?php elseif ($tieneDocDelUsuario): ?>
                                                    <span class="badge bg-danger" title="El documento <?php echo htmlspecialchars($item['nombre']); ?> está a la espera de ser cargado por el Publicador de Transparencia Activa (Gescal)." data-bs-toggle="tooltip" data-bs-placement="top">No cargado a TA</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted">No hay items de ocurrencia asignados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
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

<?php

?>

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

