<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../includes/check_auth.php';
require_login();

// Redirigir auditor a su propio dashboard (ANTES de cualquier salida HTML)
$_perfil_check = $current_profile ?? ($current_user['perfil'] ?? '');
if ($_perfil_check === 'auditor') {
    header('Location: ' . SITE_URL . 'usuario/dashboard_auditor.php');
    exit;
}

// LUEGO: Incluir header con HTML
require_once '../includes/header.php';

require_once '../classes/Item.php';
require_once '../classes/ItemPlazo.php';
require_once '../classes/ItemConPlazo.php';
require_once '../classes/Documento.php';
require_once '../classes/Verificador.php';
require_once '../classes/PlazoCalculator.php';
require_once '../classes/Historial.php';

$itemClass = new Item($db->getConnection());
$itemPlazoClass = new ItemPlazo($db->getConnection());
$itemConPlazoClass = new ItemConPlazo($db->getConnection());
$documentoClass = new Documento($db->getConnection());
$verificadorClass = new Verificador($db->getConnection());
$historialClass = new Historial($db->getConnection());

$user_id = $current_user['id'] ?? null;
$conn = $db->getConnection();

// Obtener mes y año actual
$mesActual = (int)date('m');
$anoActual = (int)date('Y');

// Mes anterior (mes a cargar)
$mesCarga = $mesActual - 1;
$anoCarga = $anoActual;
if ($mesCarga < 1) {
    $mesCarga = 12;
    $anoCarga = $anoActual - 1;
}

// Permitir seleccionar mes (solo para periodicidad mensual)
$mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : $mesCarga;
$anoSeleccionado = isset($_GET['ano']) ? (int)$_GET['ano'] : $anoCarga;

// Validar mes y año
if ($mesSeleccionado < 1 || $mesSeleccionado > 12) $mesSeleccionado = $mesCarga;
if ($anoSeleccionado < 2000 || $anoSeleccionado > 2100) $anoSeleccionado = $anoCarga;

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Perfil del usuario actual
$user_perfil = $current_profile ?? ($current_user['perfil'] ?? '');


// Filtro de documento: null = mostrar documentos de cualquier usuario para el item
// (los items ya están filtrados por asignación, así que cualquier doc del item es válido)
$userIdFiltro = null;

// Pre-fetch item IDs asignados al usuario actual (para controlar quién puede cargar documentos)
$itemsAsignadosUsuario = [];
$stmtAsig = $conn->prepare("SELECT item_id FROM item_usuarios WHERE usuario_id = ?");
$stmtAsig->bind_param('i', $user_id);
$stmtAsig->execute();
$resAsig = $stmtAsig->get_result();
while ($rowAsig = $resAsig->fetch_assoc()) {
    $itemsAsignadosUsuario[(int)$rowAsig['item_id']] = true;
}
$stmtAsig->close();

// Query SQL: Filtrar items según perfil del usuario
// Cargadores solo ven items asignados, otros perfiles ven todos
$whereUsuario = '';
if ($user_perfil === 'cargador_informacion') {
    // Cargador solo ve items asignados a él
    $whereUsuario = 'AND EXISTS (SELECT 1 FROM item_usuarios iu2 WHERE iu2.item_id = i.id AND iu2.usuario_id = ?)';
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
        u.nombre as usuario_nombre,
        vp.id as verificador_id,
        vp.fecha_carga_portal,
        u_pub.nombre as publicador_nombre
    FROM items_transparencia i
    LEFT JOIN item_usuarios iu ON i.id = iu.item_id
    LEFT JOIN usuarios u_asig ON iu.usuario_id = u_asig.id
    LEFT JOIN documentos d ON i.id = d.item_id
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN verificadores_publicador vp ON d.id = vp.documento_id
    LEFT JOIN usuarios u_pub ON vp.publicador_id = u_pub.id
    WHERE i.activo = 1 $whereUsuario
    ORDER BY 
        FIELD(i.periodicidad, 'mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia'),
        i.numeracion";

// Parámetros en orden de aparición en la query
$params = [];
$types = '';

if ($user_perfil === 'cargador_informacion') {
    $params[] = $user_id;
    $types .= 'i';
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...array_values($params));
}
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

// Procesar resultados y agrupar items por periodicidad (evitar duplicados)
while ($row = $resultado->fetch_assoc()) {
    $itemId = $row['item_id'];
    $periodicidad = $row['periodicidad'];
    
    // Agregar item al cache si no existe (evitar duplicados por múltiples documentos)
    if (!isset($itemsCache[$itemId])) {
        $itemsCache[$itemId] = [
            'id' => $row['item_id'],
            'numeracion' => $row['numeracion'],
            'nombre' => $row['item_nombre'],
            'periodicidad' => $row['periodicidad'],
            'descripcion' => $row['item_descripcion']
        ];
        $itemsPorPeriodicidad[$periodicidad][] = $itemsCache[$itemId];
    }
}

// Calcular documentos pendientes por periodicidad
// Para MENSUAL: usar mes/año seleccionado (del selector)
$contador = 0;
foreach ($itemsPorPeriodicidad['mensual'] as $item) {
    $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesSeleccionado, $anoSeleccionado);
    $tieneDocumento = false;
    
    if ($docsResult && $docsResult->num_rows > 0) {
            // Si existe cualquier documento para el item, está cubierto
            // (los items ya están filtrados por asignación para cargadores)
            $tieneDocumento = true;
        }
    
    if (!$tieneDocumento) {
        $contador++;
    }
}
$documentosPendientes['mensual'] = $contador;

// Para TRIMESTRAL/SEMESTRAL/ANUAL/OCURRENCIA: usar mes actual
foreach (['trimestral', 'semestral', 'anual', 'ocurrencia'] as $periodicidad) {
    $contador = 0;
    
    foreach ($itemsPorPeriodicidad[$periodicidad] as $item) {
        // Para ANUAL, buscar sin mes (por año completo)
        if ($periodicidad === 'anual') {
            $docsResult = $documentoClass->getByItemFollowUpAnual($item['id'], $anoActual);
        } else {
            $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesActual, $anoActual);
        }
        
        $tieneDocumento = false;
        
        if ($docsResult && $docsResult->num_rows > 0) {
            // Si existe cualquier documento para el item, está cubierto
            // (los items ya están filtrados por asignación para cargadores)
            $tieneDocumento = true;
        }
        
        if (!$tieneDocumento) {
            $contador++;
        }
    }
    $documentosPendientes[$periodicidad] = $contador;
}

$success = '';
$error = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-inbox" style="color: #3498db;"></i> Mi Panel de Carga</h1>
            <small class="text-muted">Gestiona tus documentos de transparencia</small>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- TAB: ITEMS MENSUAL -->
<div class="row mb-4">
    <div class="col-12">
        <?php
        // Determinar qué pestaña debe estar activa (la primera disponible)
        $primeraActiva = '';
        foreach (['mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia'] as $per) {
            if (count($itemsPorPeriodicidad[$per]) > 0) {
                $primeraActiva = $per;
                break;
            }
        }
        ?>
        <ul class="nav nav-tabs" role="tablist">
            <?php if (count($itemsPorPeriodicidad['mensual']) > 0): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($primeraActiva === 'mensual') ? 'active' : ''; ?>" id="tab-mensual" data-bs-toggle="tab" data-bs-target="#mensual" type="button" role="tab">
                    <i class="bi bi-calendar-month"></i> Mensual
                    <?php if ($documentosPendientes['mensual'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['mensual']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <?php endif; ?>
            
            <?php if (count($itemsPorPeriodicidad['trimestral']) > 0): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($primeraActiva === 'trimestral') ? 'active' : ''; ?>" id="tab-trimestral" data-bs-toggle="tab" data-bs-target="#trimestral" type="button" role="tab">
                    <i class="bi bi-calendar-week"></i> Trimestral
                    <?php if ($documentosPendientes['trimestral'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['trimestral']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <?php endif; ?>
            
            <?php if (count($itemsPorPeriodicidad['semestral']) > 0): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($primeraActiva === 'semestral') ? 'active' : ''; ?>" id="tab-semestral" data-bs-toggle="tab" data-bs-target="#semestral" type="button" role="tab">
                    <i class="bi bi-calendar-range"></i> Semestral
                    <?php if ($documentosPendientes['semestral'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['semestral']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <?php endif; ?>
            
            <?php if (count($itemsPorPeriodicidad['anual']) > 0): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($primeraActiva === 'anual') ? 'active' : ''; ?>" id="tab-anual" data-bs-toggle="tab" data-bs-target="#anual" type="button" role="tab">
                    <i class="bi bi-calendar-year"></i> Anual
                    <?php if ($documentosPendientes['anual'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['anual']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <?php endif; ?>
            
            <?php if (count($itemsPorPeriodicidad['ocurrencia']) > 0): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($primeraActiva === 'ocurrencia') ? 'active' : ''; ?>" id="tab-ocurrencia" data-bs-toggle="tab" data-bs-target="#ocurrencia" type="button" role="tab">
                    <i class="bi bi-exclamation-square"></i> Ocurrencia
                    <?php if ($documentosPendientes['ocurrencia'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['ocurrencia']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- FILTRO DE ESTADO -->
        <div class="d-flex align-items-center gap-2 my-3 p-2 bg-light rounded border" id="filtroBar">
            <span class="text-muted small fw-bold me-1"><i class="bi bi-funnel"></i> Filtrar:</span>
            <?php if ($user_perfil === 'publicador'): ?>
                <button class="btn btn-sm btn-secondary active" id="filtro-todos" onclick="filtrarEstado('todos')">
                    <i class="bi bi-grid"></i> Todos
                </button>
                <button class="btn btn-sm btn-outline-warning" id="filtro-pendiente_publicar" onclick="filtrarEstado('pendiente_publicar')">
                    <i class="bi bi-hourglass-split"></i> Pendiente de Publicar
                </button>
                <button class="btn btn-sm btn-outline-success" id="filtro-publicado" onclick="filtrarEstado('publicado')">
                    <i class="bi bi-check-circle"></i> Publicados
                </button>
                <button class="btn btn-sm btn-outline-danger" id="filtro-sin_doc" onclick="filtrarEstado('sin_doc')">
                    <i class="bi bi-x-circle"></i> Sin Documento
                </button>
            <?php else: ?>
                <button class="btn btn-sm btn-secondary active" id="filtro-todos" onclick="filtrarEstado('todos')">
                    <i class="bi bi-grid"></i> Todos
                </button>
                <button class="btn btn-sm btn-outline-danger" id="filtro-pendiente" onclick="filtrarEstado('pendiente')">
                    <i class="bi bi-exclamation-circle"></i> Pendientes de Cargar
                </button>
                <button class="btn btn-sm btn-outline-success" id="filtro-cargado" onclick="filtrarEstado('cargado')">
                    <i class="bi bi-check-circle"></i> Cargados
                </button>
            <?php endif; ?>
        </div>

        <div class="tab-content mt-3">
            <!-- TAB MENSUAL -->
            <?php if (count($itemsPorPeriodicidad['mensual']) > 0): ?>
            <div class="tab-pane fade <?php echo ($primeraActiva === 'mensual') ? 'show active' : ''; ?>" id="mensual" role="tabpanel">
                <?php
                    $primerItem = !empty($itemsPorPeriodicidad['mensual']) ? $itemsPorPeriodicidad['mensual'][0] : null;
                    $plazoTituloE = $primerItem ? $itemPlazoClass->getPlazoFinal($primerItem['id'], $anoSeleccionado, $mesSeleccionado, $primerItem['periodicidad']) : null;
                    $plazoTituloP = $primerItem ? $itemPlazoClass->getPlazoPublicacionFinal($primerItem['id'], $anoSeleccionado, $mesSeleccionado, $primerItem['periodicidad']) : null;
                ?>
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5 class="d-inline-block mb-0">Items Mensuales &mdash; <?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?></h5>
                        <?php if ($plazoTituloE): ?>
                        <span class="badge ms-3 px-3 py-2" style="background-color: #ff8c00; font-size: 0.9rem; vertical-align: middle;" 
                              data-bs-toggle="tooltip" 
                              data-bs-placement="top" 
                              data-bs-html="true"
                              title="Deberá enviar todos los documentos antes de esta fecha. El no cumplimiento puede resultar en:<br>• Incumplimiento de la Ley N° 20.285 sobre Transparencia<br>• Sanciones administrativas<br>• Multas según lo establece el CPLT">
                            <i class="bi bi-calendar-event me-1"></i> Plazo envío: <?php echo date('d/m/Y', strtotime($plazoTituloE)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <form method="GET" class="d-flex gap-2">
                            <select name="mes" class="form-select form-select-sm" style="max-width: 150px;" onchange="this.form.submit();">
                                <?php
                                $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                         'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                for ($m = 1; $m <= 12; $m++) {
                                    $selected = ($mesSeleccionado == $m) ? 'selected' : '';
                                    echo "<option value='$m' $selected>{$meses[$m]} - Cargar datos de {$meses[$m]}</option>";
                                }
                                ?>
                            </select>
                            <select name="ano" class="form-select form-select-sm" style="max-width: 100px;" onchange="this.form.submit();">
                                <?php
                                $anoActual = (int)date('Y');
                                for ($a = $anoActual - 2; $a <= $anoActual; $a++) {
                                    $selected = ($anoSeleccionado == $a) ? 'selected' : '';
                                    echo "<option value='$a' $selected>$a</option>";
                                }
                                ?>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Numeración</th>
                                <th width="3%" style="text-align: center;">Historial</th>
                                <th width="22%">Nombre Item</th>
                                <th width="12%">Mes Carga</th>
                                <th width="12%">Enviado por</th>
                                <th width="17%">Fecha Envío</th>
                                <th width="17%">Carga Portal</th>
                                <th width="8%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($itemsPorPeriodicidad['mensual'])) {
                                foreach ($itemsPorPeriodicidad['mensual'] as $item) {
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoSeleccionado, $mesSeleccionado);
                                    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                             'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    $mesCargaNombre = $meses[$mesSeleccionado];
                                    
                                    // Obtener documentos del mes
                                    $docsResult = $itemConPlazoClass->getDocumentosPorMes($item['id'], $userIdFiltro, $anoSeleccionado, $mesSeleccionado);
                                    $ultimoDoc = $docsResult ? $docsResult->fetch_assoc() : null;
                                    
                                    // Obtener verificador si existe
                                    $verificador = null;
                                    if ($ultimoDoc) {
                                        $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                    }
                                    
                                    // Calcular plazos de envío y publicación (mensual)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoSeleccionado, $mesSeleccionado, $item['periodicidad']);
                                    $plazoPublicFinal = $itemPlazoClass->getPlazoPublicacionFinal($item['id'], $anoSeleccionado, $mesSeleccionado, $item['periodicidad']);
                                    $cargador = $ultimoDoc ? htmlspecialchars($ultimoDoc['usuario_nombre'] ?? '—') : '—';
                                    // Fecha Envío con icono de cumplimiento
                                    if ($ultimoDoc) {
                                        if ($plazoFinal) {
                                            $icoE = date('Y-m-d', strtotime($ultimoDoc['fecha_envio'])) <= $plazoFinal ? '🟢 ' : '🔴 ';
                                        } else { $icoE = ''; }
                                        $fechaEnvio = $icoE . date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio']));
                                    } else {
                                        $fechaEnvio = '<span class="text-muted">Sin envío</span>';
                                    }
                                    // Carga Portal con icono de cumplimiento
                                    if ($verificador) {
                                        if ($plazoPublicFinal) {
                                            $icoP = date('Y-m-d', strtotime($verificador['fecha_carga_portal'])) <= $plazoPublicFinal ? '🟢 ' : '🔴 ';
                                        } else { $icoP = ''; }
                                        $cargaPortal = $icoP . date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal']));
                                    } else {
                                        $cargaPortal = '<span class="text-muted">Pendiente</span>';
                                    }
                                    // Clase y estado para filtro de tabs
                                    if ($user_perfil === 'publicador') {
                                        if ($verificador) { $rowClass = 'table-success'; $dataEstado = 'publicado'; }
                                        elseif ($ultimoDoc) { $rowClass = 'table-warning'; $dataEstado = 'pendiente_publicar'; }
                                        else { $rowClass = 'table-danger'; $dataEstado = 'sin_doc'; }
                                    } else {
                                        $rowClass = $ultimoDoc ? 'table-success' : 'table-danger';
                                        $dataEstado = $ultimoDoc ? 'cargado' : 'pendiente';
                                    }
                                    
                                    // Badge estado
                                    $estadoBadge = '';
                                    if ($ultimoDoc) {
                                        if ($ultimoDoc['estado'] == 'aprobado') {
                                            $estadoBadge = '<span class="badge bg-success">Aprobado</span>';
                                        } elseif ($ultimoDoc['estado'] == 'rechazado') {
                                            $estadoBadge = '<span class="badge bg-danger">Rechazado</span>';
                                        } else {
                                            $estadoBadge = '<span class="badge bg-warning">Pendiente</span>';
                                        }
                                    } else {
                                        $estadoBadge = '<span class="badge bg-secondary">Sin envío</span>';
                                    }
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>" data-estado="<?php echo $dataEstado; ?>">
                                        <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                        <td style="text-align: center;">
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalHistorial"
                                                    onclick="mostrarHistorial(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', <?php echo $mesSeleccionado; ?>, <?php echo $anoSeleccionado; ?>)"
                                                    title="Ver historial de movimientos">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo $mesCargaNombre . ' ' . $anoSeleccionado; ?></td>
                                        <td><small><?php echo $cargador; ?></small></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($ultimoDoc): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['id']; ?>" class="btn btn-sm btn-success" title="Descargar documento" style="white-space: nowrap;">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Doc
                                                    </a>
                                                    <?php if (!$verificador && $user_perfil !== 'publicador'): ?>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', <?php echo $mesSeleccionado; ?>, <?php echo $ultimoDoc['id']; ?>)"
                                                            style="white-space: nowrap;" title="Reemplazar documento existente">
                                                        <i class="bi bi-pencil"></i> Modificar
                                                    </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($user_perfil !== 'publicador' || isset($itemsAsignadosUsuario[$item['id']])): ?>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', <?php echo $mesSeleccionado; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Documento
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ($verificador): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $verificador['id']; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-check-circle"></i> Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc && $user_perfil === 'publicador'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalSubirVerificador"
                                                            onclick="prepararVerificador(<?php echo $item['id']; ?>, <?php echo $ultimoDoc['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>')"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-upload"></i> Subir Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc): ?>
                                                    <span class="badge bg-danger" title="Pendiente de publicar en portal TA." data-bs-toggle="tooltip">No cargado a TA</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-muted">No hay items mensuales asignados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- TAB TRIMESTRAL -->
            <?php if (count($itemsPorPeriodicidad['trimestral']) > 0): ?>
            <div class="tab-pane fade <?php echo ($primeraActiva === 'trimestral') ? 'show active' : ''; ?>" id="trimestral" role="tabpanel">
                <?php
                    $primerItemT = !empty($itemsPorPeriodicidad['trimestral']) ? $itemsPorPeriodicidad['trimestral'][0] : null;
                    $plazoTituloET = $primerItemT ? $itemPlazoClass->getPlazoFinal($primerItemT['id'], $anoActual, $mesActual, $primerItemT['periodicidad']) : null;
                    $plazoTituloPT = $primerItemT ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemT['id'], $anoActual, $mesActual, $primerItemT['periodicidad']) : null;
                ?>
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5 class="d-inline-block mb-0">Items Trimestrales</h5>
                        <?php if ($plazoTituloET): ?>
                        <span class="badge ms-3 px-3 py-2" style="background-color: #ff8c00; font-size: 0.9rem; vertical-align: middle;" 
                              data-bs-toggle="tooltip" 
                              data-bs-placement="top" 
                              data-bs-html="true"
                              title="Deberá enviar todos los documentos antes de esta fecha. El no cumplimiento puede resultar en:<br>• Incumplimiento de la Ley N° 20.285 sobre Transparencia<br>• Sanciones administrativas<br>• Multas según lo establece el CPLT">
                            <i class="bi bi-calendar-event me-1"></i> Plazo envío: <?php echo date('d/m/Y', strtotime($plazoTituloET)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Numeración</th>
                                <th width="3%" style="text-align: center;">Historial</th>
                                <th width="22%">Nombre Item</th>
                                <th width="12%">Enviado por</th>
                                <th width="17%">Fecha Envío</th>
                                <th width="17%">Carga Portal</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($itemsPorPeriodicidad['trimestral'])) {
                                // Meses del trimestre actual (Q1=1-3, Q2=4-6, Q3=7-9, Q4=10-12)
                                $trimestreActual = (int)ceil($mesActual / 3);
                                $mesInicioTrimestre = ($trimestreActual - 1) * 3 + 1;
                                $mesesTrimestre = [$mesInicioTrimestre, $mesInicioTrimestre + 1, $mesInicioTrimestre + 2];
                                foreach ($itemsPorPeriodicidad['trimestral'] as $item) {
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoActual, $mesActual);
                                    
                                    // Obtener documento del trimestre completo
                                    $docsResult = $itemConPlazoClass->getDocumentosPorPeriodo($item['id'], $userIdFiltro, $anoActual, $mesesTrimestre);
                                    $ultimoDoc = $docsResult ? $docsResult->fetch_assoc() : null;
                                    
                                    // Obtener verificador si existe
                                    $verificador = null;
                                    if ($ultimoDoc) {
                                        $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                    }
                                    
                                    // Calcular plazos de envío y publicación (trimestral)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoActual, $mesActual, $item['periodicidad']);
                                    $plazoPublicFinal = $itemPlazoClass->getPlazoPublicacionFinal($item['id'], $anoActual, $mesActual, $item['periodicidad']);
                                    $cargador = $ultimoDoc ? htmlspecialchars($ultimoDoc['usuario_nombre'] ?? '—') : '—';
                                    // Fecha Envío con icono
                                    if ($ultimoDoc) {
                                        $icoE = ($plazoFinal && date('Y-m-d', strtotime($ultimoDoc['fecha_envio'])) <= $plazoFinal) ? '🟢 ' : ($plazoFinal ? '🔴 ' : '');
                                        $fechaEnvio = $icoE . date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio']));
                                    } else { $fechaEnvio = '<span class="text-muted">Sin envío</span>'; }
                                    // Carga Portal con icono
                                    if ($verificador) {
                                        $icoP = ($plazoPublicFinal && date('Y-m-d', strtotime($verificador['fecha_carga_portal'])) <= $plazoPublicFinal) ? '🟢 ' : ($plazoPublicFinal ? '🔴 ' : '');
                                        $cargaPortal = $icoP . date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal']));
                                    } else { $cargaPortal = '<span class="text-muted">Pendiente</span>'; }
                                    // Clase y estado para filtro de tabs
                                    if ($user_perfil === 'publicador') {
                                        if ($verificador) { $rowClass = 'table-success'; $dataEstado = 'publicado'; }
                                        elseif ($ultimoDoc) { $rowClass = 'table-warning'; $dataEstado = 'pendiente_publicar'; }
                                        else { $rowClass = 'table-danger'; $dataEstado = 'sin_doc'; }
                                    } else {
                                        $rowClass = $ultimoDoc ? 'table-success' : 'table-danger';
                                        $dataEstado = $ultimoDoc ? 'cargado' : 'pendiente';
                                    }
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>" data-estado="<?php echo $dataEstado; ?>">
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
                                        <td><small><?php echo $cargador; ?></small></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($ultimoDoc): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['id']; ?>" class="btn btn-sm btn-success" title="Ver documento">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Documento
                                                    </a>
                                                <?php else: ?>
                                                    <?php if ($user_perfil !== 'publicador' || isset($itemsAsignadosUsuario[$item['id']])): ?>
                                                    <button class="btn btn-sm btn-primary" style="white-space: nowrap;" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>');">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Documento
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ($verificador): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $verificador['id']; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-check-circle"></i> Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc && $user_perfil === 'publicador'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalSubirVerificador"
                                                            onclick="prepararVerificador(<?php echo $item['id']; ?>, <?php echo $ultimoDoc['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>')"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-upload"></i> Subir Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc): ?>
                                                    <span class="badge bg-danger" title="Pendiente de publicar en portal TA." data-bs-toggle="tooltip">No cargado a TA</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Sin documento</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted">No hay items trimestrales asignados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- TAB SEMESTRAL -->
            <?php if (count($itemsPorPeriodicidad['semestral']) > 0): ?>
            <div class="tab-pane fade <?php echo ($primeraActiva === 'semestral') ? 'show active' : ''; ?>" id="semestral" role="tabpanel">
                <?php
                    $primerItemS = !empty($itemsPorPeriodicidad['semestral']) ? $itemsPorPeriodicidad['semestral'][0] : null;
                    $plazoTituloES = $primerItemS ? $itemPlazoClass->getPlazoFinal($primerItemS['id'], $anoActual, $mesActual, $primerItemS['periodicidad']) : null;
                    $plazoTituloPS = $primerItemS ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemS['id'], $anoActual, $mesActual, $primerItemS['periodicidad']) : null;
                ?>
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5 class="d-inline-block mb-0">Items Semestrales</h5>
                        <?php if ($plazoTituloES): ?>
                        <span class="badge ms-3 px-3 py-2" style="background-color: #ff8c00; font-size: 0.9rem; vertical-align: middle;" 
                              data-bs-toggle="tooltip" 
                              data-bs-placement="top" 
                              data-bs-html="true"
                              title="Deberá enviar todos los documentos antes de esta fecha. El no cumplimiento puede resultar en:<br>• Incumplimiento de la Ley N° 20.285 sobre Transparencia<br>• Sanciones administrativas<br>• Multas según lo establece el CPLT">
                            <i class="bi bi-calendar-event me-1"></i> Plazo envío: <?php echo date('d/m/Y', strtotime($plazoTituloES)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Numeración</th>
                                <th width="3%" style="text-align: center;">Historial</th>
                                <th width="22%">Nombre Item</th>
                                <th width="12%">Enviado por</th>
                                <th width="17%">Fecha Envío</th>
                                <th width="17%">Carga Portal</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($itemsPorPeriodicidad['semestral'])) {
                                // Meses del semestre actual (H1=1-6, H2=7-12)
                                $semestreActual = $mesActual <= 6 ? 1 : 2;
                                $mesesSemestre = $semestreActual === 1 ? [1,2,3,4,5,6] : [7,8,9,10,11,12];
                                foreach ($itemsPorPeriodicidad['semestral'] as $item) {
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoActual, $mesActual);
                                    
                                    // Obtener documento del semestre completo
                                    $docsResult = $itemConPlazoClass->getDocumentosPorPeriodo($item['id'], $userIdFiltro, $anoActual, $mesesSemestre);
                                    $ultimoDoc = $docsResult ? $docsResult->fetch_assoc() : null;
                                    
                                    // Obtener verificador si existe
                                    $verificador = null;
                                    if ($ultimoDoc) {
                                        $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                    }
                                    
                                    // Calcular plazos de envío y publicación (semestral)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoActual, $mesActual, $item['periodicidad']);
                                    $plazoPublicFinal = $itemPlazoClass->getPlazoPublicacionFinal($item['id'], $anoActual, $mesActual, $item['periodicidad']);
                                    $cargador = $ultimoDoc ? htmlspecialchars($ultimoDoc['usuario_nombre'] ?? '—') : '—';
                                    // Fecha Envío con icono
                                    if ($ultimoDoc) {
                                        $icoE = ($plazoFinal && date('Y-m-d', strtotime($ultimoDoc['fecha_envio'])) <= $plazoFinal) ? '🟢 ' : ($plazoFinal ? '🔴 ' : '');
                                        $fechaEnvio = $icoE . date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio']));
                                    } else { $fechaEnvio = '<span class="text-muted">Sin envío</span>'; }
                                    // Carga Portal con icono
                                    if ($verificador) {
                                        $icoP = ($plazoPublicFinal && date('Y-m-d', strtotime($verificador['fecha_carga_portal'])) <= $plazoPublicFinal) ? '🟢 ' : ($plazoPublicFinal ? '🔴 ' : '');
                                        $cargaPortal = $icoP . date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal']));
                                    } else { $cargaPortal = '<span class="text-muted">Pendiente</span>'; }
                                    // Clase y estado para filtro de tabs
                                    if ($user_perfil === 'publicador') {
                                        if ($verificador) { $rowClass = 'table-success'; $dataEstado = 'publicado'; }
                                        elseif ($ultimoDoc) { $rowClass = 'table-warning'; $dataEstado = 'pendiente_publicar'; }
                                        else { $rowClass = 'table-danger'; $dataEstado = 'sin_doc'; }
                                    } else {
                                        $rowClass = $ultimoDoc ? 'table-success' : 'table-danger';
                                        $dataEstado = $ultimoDoc ? 'cargado' : 'pendiente';
                                    }
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>" data-estado="<?php echo $dataEstado; ?>">
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
                                        <td><small><?php echo $cargador; ?></small></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($ultimoDoc): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['id']; ?>" class="btn btn-sm btn-success" title="Ver documento">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Documento
                                                    </a>
                                                    <?php if (!$verificador && $user_perfil !== 'publicador'): ?>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', null, <?php echo $ultimoDoc['id']; ?>)"
                                                            style="white-space: nowrap;" title="Reemplazar documento existente">
                                                        <i class="bi bi-pencil"></i> Modificar
                                                    </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($user_perfil !== 'publicador' || isset($itemsAsignadosUsuario[$item['id']])): ?>
                                                    <button class="btn btn-sm btn-primary" style="white-space: nowrap;" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>');">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Documento
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ($verificador): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $verificador['id']; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-check-circle"></i> Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc && $user_perfil === 'publicador'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalSubirVerificador"
                                                            onclick="prepararVerificador(<?php echo $item['id']; ?>, <?php echo $ultimoDoc['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>')"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-upload"></i> Subir Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc): ?>
                                                    <span class="badge bg-danger" title="Pendiente de publicar en portal TA." data-bs-toggle="tooltip">No cargado a TA</span>
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
            <?php endif; ?>

            <!-- TAB ANUAL -->
            <?php if (count($itemsPorPeriodicidad['anual']) > 0): ?>
            <div class="tab-pane fade <?php echo ($primeraActiva === 'anual') ? 'show active' : ''; ?>" id="anual" role="tabpanel">
                <?php
                    $primerItemA = !empty($itemsPorPeriodicidad['anual']) ? $itemsPorPeriodicidad['anual'][0] : null;
                    $plazoTituloEA = $primerItemA ? $itemPlazoClass->getPlazoFinal($primerItemA['id'], $anoActual, 1, $primerItemA['periodicidad']) : null;
                    $plazoTituloPA = $primerItemA ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemA['id'], $anoActual, 1, $primerItemA['periodicidad']) : null;
                ?>
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5 class="d-inline-block mb-0">Items Anuales &mdash; <?php echo $anoActual; ?></h5>
                        <?php if ($plazoTituloEA): ?>
                        <span class="badge ms-3 px-3 py-2" style="background-color: #ff8c00; font-size: 0.9rem; vertical-align: middle;" 
                              data-bs-toggle="tooltip" 
                              data-bs-placement="top" 
                              data-bs-html="true"
                              title="Deberá enviar todos los documentos antes de esta fecha. El no cumplimiento puede resultar en:<br>• Incumplimiento de la Ley N° 20.285 sobre Transparencia<br>• Sanciones administrativas<br>• Multas según lo establece el CPLT">
                            <i class="bi bi-calendar-event me-1"></i> Plazo envío: <?php echo date('d/m/Y', strtotime($plazoTituloEA)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">Numeración</th>
                                <th width="3%" style="text-align: center;">Historial</th>
                                <th width="22%">Nombre Item</th>
                                <th width="12%">Enviado por</th>
                                <th width="17%">Fecha Envío</th>
                                <th width="17%">Carga Portal</th>
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
                                            $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                        } else {
                                            // Para cargador, verificar que sea suyo
                                            if ((int)$ultimoDoc['usuario_id'] === (int)$user_id) {
                                                $tieneDocDelUsuario = true;
                                                // Obtener verificador si existe
                                                $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                            }
                                        }
                                    }
                                    
                                    // Calcular plazos de envío y publicación (anual)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoActual, $mesAnual, $item['periodicidad']);
                                    $plazoPublicFinal = $itemPlazoClass->getPlazoPublicacionFinal($item['id'], $anoActual, $mesAnual, $item['periodicidad']);
                                    $cargador = $ultimoDoc ? htmlspecialchars($ultimoDoc['usuario_nombre'] ?? '—') : '—';
                                    // Fecha Envío con icono
                                    if ($ultimoDoc) {
                                        $icoE = ($plazoFinal && date('Y-m-d', strtotime($ultimoDoc['fecha_envio'])) <= $plazoFinal) ? '🟢 ' : ($plazoFinal ? '🔴 ' : '');
                                        $fechaEnvio = $icoE . date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio']));
                                    } else { $fechaEnvio = '<span class="text-muted">Sin envío</span>'; }
                                    // Carga Portal con icono
                                    if ($verificador) {
                                        $icoP = ($plazoPublicFinal && date('Y-m-d', strtotime($verificador['fecha_carga_portal'])) <= $plazoPublicFinal) ? '🟢 ' : ($plazoPublicFinal ? '🔴 ' : '');
                                        $cargaPortal = $icoP . date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal']));
                                    } else { $cargaPortal = '<span class="text-muted">Pendiente</span>'; }
                                    // Clase y estado para filtro de tabs (anual)
                                    if ($user_perfil === 'publicador') {
                                        if ($verificador) { $rowClass = 'table-success'; $dataEstado = 'publicado'; }
                                        elseif ($tieneDocDelUsuario) { $rowClass = 'table-warning'; $dataEstado = 'pendiente_publicar'; }
                                        else { $rowClass = 'table-danger'; $dataEstado = 'sin_doc'; }
                                    } else {
                                        $rowClass = $tieneDocDelUsuario ? 'table-success' : 'table-danger';
                                        $dataEstado = $tieneDocDelUsuario ? 'cargado' : 'pendiente';
                                    }
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>" data-estado="<?php echo $dataEstado; ?>">
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
                                        <td><small><?php echo $cargador; ?></small></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($tieneDocDelUsuario): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['id']; ?>" class="btn btn-sm btn-success" title="Ver documento">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Documento
                                                    </a>
                                                    <?php if (!$verificador && $user_perfil !== 'publicador'): ?>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', 1, <?php echo $ultimoDoc['id']; ?>)"
                                                            style="white-space: nowrap;" title="Reemplazar documento existente">
                                                        <i class="bi bi-pencil"></i> Modificar
                                                    </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($user_perfil !== 'publicador' || isset($itemsAsignadosUsuario[$item['id']])): ?>
                                                    <button class="btn btn-sm btn-primary" style="white-space: nowrap;" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', 1);">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Documento
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ($verificador): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $verificador['id']; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-check-circle"></i> Verificador
                                                    </button>
                                                <?php elseif ($tieneDocDelUsuario && $user_perfil === 'publicador'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalSubirVerificador"
                                                            onclick="prepararVerificador(<?php echo $item['id']; ?>, <?php echo $ultimoDoc['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>')"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-upload"></i> Subir Verificador
                                                    </button>
                                                <?php elseif ($tieneDocDelUsuario): ?>
                                                    <span class="badge bg-danger" title="Pendiente de publicar en portal TA." data-bs-toggle="tooltip">No cargado a TA</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-muted">No hay items anuales asignados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- TAB OCURRENCIA -->
            <?php if (count($itemsPorPeriodicidad['ocurrencia']) > 0): ?>
            <div class="tab-pane fade <?php echo ($primeraActiva === 'ocurrencia') ? 'show active' : ''; ?>" id="ocurrencia" role="tabpanel">
                <?php
                    $primerItemO = !empty($itemsPorPeriodicidad['ocurrencia']) ? $itemsPorPeriodicidad['ocurrencia'][0] : null;
                    $plazoTituloEO = $primerItemO ? $itemPlazoClass->getPlazoFinal($primerItemO['id'], $anoActual, $mesActual, $primerItemO['periodicidad']) : null;
                    $plazoTituloPO = $primerItemO ? $itemPlazoClass->getPlazoPublicacionFinal($primerItemO['id'], $anoActual, $mesActual, $primerItemO['periodicidad']) : null;
                ?>
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5 class="d-inline-block mb-0">Items de Ocurrencia Libre</h5>
                        <?php if ($plazoTituloEO): ?>
                        <span class="badge ms-3 px-3 py-2" style="background-color: #ff8c00; font-size: 0.9rem; vertical-align: middle;" 
                              data-bs-toggle="tooltip" 
                              data-bs-placement="top" 
                              data-bs-html="true"
                              title="Deberá enviar todos los documentos antes de esta fecha. El no cumplimiento puede resultar en:<br>• Incumplimiento de la Ley N° 20.285 sobre Transparencia<br>• Sanciones administrativas<br>• Multas según lo establece el CPLT">
                            <i class="bi bi-calendar-event me-1"></i> Plazo envío: <?php echo date('d/m/Y', strtotime($plazoTituloEO)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Numeración</th>
                                <th width="25%">Nombre Item</th>
                                <th width="12%">Enviado por</th>
                                <th width="17%">Fecha Envío</th>
                                <th width="17%">Carga Portal</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($itemsPorPeriodicidad['ocurrencia'])) {
                                foreach ($itemsPorPeriodicidad['ocurrencia'] as $item) {
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoActual, $mesActual);
                                    
                                    // Obtener último documento (ocurrencia)
                                    $docsResult = $itemConPlazoClass->getDocumentosPorMes($item['id'], $user_id, $anoActual, $mesActual);
                                    $ultimoDoc = $docsResult ? $docsResult->fetch_assoc() : null;
                                    
                                    // Obtener verificador si existe
                                    $verificador = null;
                                    if ($ultimoDoc) {
                                        $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                    }
                                    
                                    // Calcular plazos de envío y publicación (ocurrencia)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoActual, $mesActual, $item['periodicidad']);
                                    $plazoPublicFinal = $itemPlazoClass->getPlazoPublicacionFinal($item['id'], $anoActual, $mesActual, $item['periodicidad']);
                                    $cargador = $ultimoDoc ? htmlspecialchars($ultimoDoc['usuario_nombre'] ?? '—') : '—';
                                    // Fecha Envío con icono
                                    if ($ultimoDoc) {
                                        $icoE = ($plazoFinal && date('Y-m-d', strtotime($ultimoDoc['fecha_envio'])) <= $plazoFinal) ? '🟢 ' : ($plazoFinal ? '🔴 ' : '');
                                        $fechaEnvio = $icoE . date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio']));
                                    } else { $fechaEnvio = '<span class="text-muted">Sin envío</span>'; }
                                    // Carga Portal con icono
                                    if ($verificador) {
                                        $icoP = ($plazoPublicFinal && date('Y-m-d', strtotime($verificador['fecha_carga_portal'])) <= $plazoPublicFinal) ? '🟢 ' : ($plazoPublicFinal ? '🔴 ' : '');
                                        $cargaPortal = $icoP . date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal']));
                                    } else { $cargaPortal = '<span class="text-muted">Pendiente</span>'; }
                                    // Clase y estado para filtro de tabs
                                    if ($user_perfil === 'publicador') {
                                        if ($verificador) { $rowClass = 'table-success'; $dataEstado = 'publicado'; }
                                        elseif ($ultimoDoc) { $rowClass = 'table-warning'; $dataEstado = 'pendiente_publicar'; }
                                        else { $rowClass = 'table-danger'; $dataEstado = 'sin_doc'; }
                                    } else {
                                        $rowClass = $ultimoDoc ? 'table-success' : 'table-danger';
                                        $dataEstado = $ultimoDoc ? 'cargado' : 'pendiente';
                                    }
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>" data-estado="<?php echo $dataEstado; ?>">
                                        <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><small><?php echo $cargador; ?></small></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($ultimoDoc): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['id']; ?>" class="btn btn-sm btn-success" title="Ver documento">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Documento
                                                    </a>
                                                    <?php if (!$verificador && $user_perfil !== 'publicador'): ?>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', null, <?php echo $ultimoDoc['id']; ?>)"
                                                            style="white-space: nowrap;" title="Reemplazar documento existente">
                                                        <i class="bi bi-pencil"></i> Modificar
                                                    </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($user_perfil !== 'publicador' || isset($itemsAsignadosUsuario[$item['id']])): ?>
                                                    <button class="btn btn-sm btn-primary" style="white-space: nowrap;" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>');">
                                                        <i class="bi bi-cloud-upload"></i> Cargar Documento
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ($verificador): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalVerVerificador"
                                                            onclick="verVerificador(<?php echo $verificador['id']; ?>)"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-check-circle"></i> Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc && $user_perfil === 'publicador'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalSubirVerificador"
                                                            onclick="prepararVerificador(<?php echo $item['id']; ?>, <?php echo $ultimoDoc['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>')"
                                                            style="white-space: nowrap;">
                                                        <i class="bi bi-upload"></i> Subir Verificador
                                                    </button>
                                                <?php elseif ($ultimoDoc): ?>
                                                    <span class="badge bg-danger" title="Pendiente de publicar." data-bs-toggle="tooltip">No cargado a TA</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center text-muted">No hay items de ocurrencia asignados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL: Subir Verificador (Publicador) -->
<div class="modal fade" id="modalSubirVerificador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#f0ad4e;">
                <div>
                    <h5 class="modal-title mb-1"><i class="bi bi-upload"></i> Subir Verificador de Portal</h5>
                    <small>Item: <strong id="verificadorItemNombre">-</strong></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="subir_verificador.php">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="verificadorItemId">
                    <input type="hidden" name="documento_id" id="verificadorDocId">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Suba la imagen o PDF que acredita la publicación en el portal de Transparencia Activa.
                    </div>
                    <div class="mb-3">
                        <label for="fecha_carga_portal" class="form-label">Fecha de Carga en Portal <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="fecha_carga_portal" name="fecha_carga_portal" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="archivo_verificador" class="form-label">Archivo Verificador (imagen o PDF) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="archivo_verificador" name="archivo_verificador" required accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Formatos: PDF, JPG, PNG (máx. 10MB)</small>
                        <!-- Zona de pegado desde portapapeles -->
                        <div id="pasteZone" style="margin-top:10px; border:2px dashed #f0ad4e; border-radius:8px; padding:18px; text-align:center; color:#888; cursor:pointer; background:#fffbf2;">
                            <i class="bi bi-clipboard-image" style="font-size:1.5rem;"></i><br>
                            <span id="pasteZoneText">Haz clic aquí y pega una imagen con <kbd>Ctrl+V</kbd></span>
                        </div>
                        <div id="pastePreview" style="display:none; margin-top:8px; text-align:center;">
                            <img id="pastePreviewImg" src="" style="max-width:100%; max-height:200px; border-radius:6px; border:1px solid #ddd;">
                            <br><small class="text-success"><i class="bi bi-check-circle"></i> Imagen pegada desde portapapeles</small>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="clearPaste"><i class="bi bi-x"></i> Quitar</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="comentarios_verificador" class="form-label">Comentarios (Opcional)</label>
                        <textarea class="form-control" id="comentarios_verificador" name="comentarios" rows="2" placeholder="Observaciones adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-upload"></i> Subir y Publicar
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
                    <input type="hidden" name="doc_id_reemplazar" id="docIdReemplazarInput" value="0">

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

// --- FILTRO DE PESTAÑA POR ESTADO ---
function filtrarEstado(estado) {
    // Actualizar botones activos
    document.querySelectorAll('#filtroBar button').forEach(btn => {
        btn.classList.remove('active', 'btn-secondary', 'btn-warning', 'btn-success', 'btn-danger');
        btn.classList.add(btn.id.replace('filtro-', 'btn-outline-').replace('todos', 'outline-secondary'));
    });
    const btnActivo = document.getElementById('filtro-' + estado);
    if (btnActivo) {
        btnActivo.classList.remove('btn-outline-secondary', 'btn-outline-warning', 'btn-outline-success', 'btn-outline-danger');
        btnActivo.classList.add('btn-secondary', 'active');
    }

    // Filtrar filas en el tab activo
    const tabActivo = document.querySelector('.tab-pane.active');
    if (!tabActivo) return;
    tabActivo.querySelectorAll('tr[data-estado]').forEach(row => {
        if (estado === 'todos') {
            row.style.display = '';
        } else {
            row.style.display = (row.dataset.estado === estado) ? '' : 'none';
        }
    });
}

// --- PREPARAR MODAL SUBIR VERIFICADOR ---
function prepararVerificador(itemId, docId, itemNombre) {
    document.getElementById('verificadorItemId').value = itemId;
    document.getElementById('verificadorDocId').value = docId;
    document.getElementById('verificadorItemNombre').textContent = itemNombre;
    // Limpiar zona de pegado al abrir el modal
    resetPasteZone();
}

function resetPasteZone() {
    document.getElementById('pastePreview').style.display = 'none';
    document.getElementById('pasteZone').style.display = 'block';
    document.getElementById('pasteZoneText').textContent = 'Haz clic aquí y pega una imagen con Ctrl+V';
    document.getElementById('archivo_verificador').removeAttribute('required');
    document.getElementById('archivo_verificador').value = '';
}

// Pegar imagen desde portapapeles
const pasteZone = document.getElementById('pasteZone');
const fileInput = document.getElementById('archivo_verificador');

// Activar foco en la zona al hacer clic
pasteZone.addEventListener('click', function() {
    pasteZone.style.borderColor = '#e67e00';
    pasteZone.style.background = '#fff3e0';
    document.getElementById('pasteZoneText').textContent = 'Zona activa — pega ahora con Ctrl+V';
    pasteZone.focus();
});

pasteZone.setAttribute('tabindex', '0');

// Escuchar pegado tanto en la zona como en el documento (cuando el modal está abierto)
function handlePasteEvent(e) {
    const modal = document.getElementById('modalSubirVerificador');
    if (!modal.classList.contains('show')) return;

    const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items;
    if (!items) return;

    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            e.preventDefault();
            const blob = items[i].getAsFile();
            const fileName = 'captura_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.png';
            const file = new File([blob], fileName, { type: blob.type });

            // Asignar al input file usando DataTransfer
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;

            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('pastePreviewImg').src = ev.target.result;
                document.getElementById('pastePreview').style.display = 'block';
                document.getElementById('pasteZone').style.display = 'none';
            };
            reader.readAsDataURL(blob);
            break;
        }
    }
}

document.addEventListener('paste', handlePasteEvent);
pasteZone.addEventListener('paste', handlePasteEvent);

// Botón para quitar imagen pegada
document.getElementById('clearPaste').addEventListener('click', function() {
    fileInput.value = '';
    fileInput.setAttribute('required', 'required');
    resetPasteZone();
});

// Si el usuario elige un archivo, ocultar la zona de pegado
fileInput.addEventListener('change', function() {
    if (fileInput.files.length > 0) {
        document.getElementById('pasteZone').style.display = 'none';
        document.getElementById('pastePreview').style.display = 'none';
    }
});

function seleccionarItem(itemId, itemNombre, mesCarga = null, docIdReemplazar = 0) {
    document.getElementById('itemIdInput').value = itemId;
    document.getElementById('docIdReemplazarInput').value = docIdReemplazar;
    document.getElementById('itemNombreModal').textContent = itemNombre;
    const modalTitle = document.querySelector('#modalCargar .modal-title');
    const modalHeader = document.querySelector('#modalCargar .modal-header');
    if (docIdReemplazar > 0) {
        modalTitle.textContent = 'Modificar Documento';
        modalHeader.classList.add('bg-warning');
        modalHeader.classList.remove('bg-light');
    } else {
        modalTitle.textContent = 'Cargar Documento';
        modalHeader.classList.add('bg-light');
        modalHeader.classList.remove('bg-warning');
    }
    
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

<script>
// Inicializar tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>

