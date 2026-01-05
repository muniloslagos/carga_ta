<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../includes/check_auth.php';
require_login();

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

// Obtener items: TODOS si es publicador, asignados si es cargador
$items = [];
if ($current_user['perfil'] === 'publicador') {
    // El publicador ve TODOS los items
    $itemsResult = $itemClass->getAll();
} else {
    // El cargador solo ve sus items asignados
    $itemsResult = $itemClass->getItemsByUser($user_id);
}

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Agrupar por periodicidad
$itemsPorPeriodicidad = [
    'mensual' => [],
    'trimestral' => [],
    'semestral' => [],
    'anual' => [],
    'ocurrencia' => []
];

foreach ($items as $item) {
    $itemsPorPeriodicidad[$item['periodicidad']][] = $item;
}

// Para publicador: ver todos los documentos; para cargador: solo los del usuario actual
$userIdFiltro = ($current_user['perfil'] === 'publicador') ? null : $user_id;

// Contar documentos pendientes por periodicidad
// Mensual: según mes seleccionado
// Trimestral/Semestral/Anual/Ocurrencia: según mes actual
$documentosPendientes = [
    'mensual' => 0,
    'trimestral' => 0,
    'semestral' => 0,
    'anual' => 0,
    'ocurrencia' => 0
];

// Para MENSUAL: usar mes seleccionado
$contador = 0;
foreach ($itemsPorPeriodicidad['mensual'] as $item) {
    $docsResult = $documentoClass->getByItemFollowUp($item['id'], $mesSeleccionado, $anoSeleccionado);
    $tieneDocumento = false;
    
    if ($docsResult && $docsResult->num_rows > 0) {
        if ($current_user['perfil'] === 'publicador') {
            // Para publicador: si existe documento (de cualquier usuario) está cubierto
            $tieneDocumento = true;
        } else {
            // Para cargador: verificar que el documento pertenece al usuario actual
            while ($doc = $docsResult->fetch_assoc()) {
                if ((int)$doc['usuario_id'] === (int)$user_id) {
                    $tieneDocumento = true;
                    break;
                }
            }
        }
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
            if ($current_user['perfil'] === 'publicador') {
                // Para publicador: si existe documento (de cualquier usuario) está cubierto
                $tieneDocumento = true;
            } else {
                // Para cargador: verificar que el documento pertenece al usuario actual
                while ($doc = $docsResult->fetch_assoc()) {
                    if ((int)$doc['usuario_id'] === (int)$user_id) {
                        $tieneDocumento = true;
                        break;
                    }
                }
            }
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

<div class="page-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 2.5rem 2rem; margin: -1rem -1rem 2rem -1rem; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h1 style="font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem;">
        <i class="bi bi-inbox" style="color: #3498db; margin-right: 0.5rem;"></i> 
        Mi Panel de Carga
    </h1>
    <p style="margin: 0; color: #bdc3c7; font-size: 1.05rem;">
        <i class="bi bi-check-circle" style="color: #27ae60;"></i> 
        Bienvenido, gestiona tus documentos de transparencia
    </p>
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
                    <i class="bi bi-calendar-week"></i> Trimestral
                    <?php if ($documentosPendientes['trimestral'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['trimestral']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-semestral" data-bs-toggle="tab" data-bs-target="#semestral" type="button" role="tab">
                    <i class="bi bi-calendar-range"></i> Semestral
                    <?php if ($documentosPendientes['semestral'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['semestral']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-anual" data-bs-toggle="tab" data-bs-target="#anual" type="button" role="tab">
                    <i class="bi bi-calendar-year"></i> Anual
                    <?php if ($documentosPendientes['anual'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $documentosPendientes['anual']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-ocurrencia" data-bs-toggle="tab" data-bs-target="#ocurrencia" type="button" role="tab">
                    <i class="bi bi-exclamation-square"></i> Ocurrencia
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
                        <h5>Items Mensuales</h5>
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
                                <th width="15%">Plazo Interno</th>
                                <th width="15%">Fecha Envío</th>
                                <th width="15%">Carga Portal</th>
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
                                    $ultimoDoc = $docsResult->fetch_assoc();
                                    
                                    // Obtener verificador si existe
                                    $verificador = null;
                                    if ($ultimoDoc) {
                                        $verificador = $verificadorClass->getByDocumento($ultimoDoc['id']);
                                    }
                                    
                                    $fechaEnvio = $ultimoDoc ? date('d/m/Y H:i', strtotime($ultimoDoc['fecha_envio'])) : '<span class="text-muted">Sin envío</span>';
                                    
                                    // Calcular plazo final (automático o personalizado)
                                    $plazoFinal = $itemPlazoClass->getPlazoFinal($item['id'], $anoSeleccionado, $mesSeleccionado, $item['periodicidad']);
                                    $plazoInterno = $plazoFinal ? date('d/m/Y', strtotime($plazoFinal)) : '<span class="text-muted">No configurado</span>';
                                    
                                    $cargaPortal = $verificador ? date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal'])) : '<span class="text-muted">Pendiente</span>';
                                    
                                    // Clase para fila con documento cargado
                                    $rowClass = $ultimoDoc ? 'table-success' : 'table-danger';
                                    
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
                                    <tr class="<?php echo $rowClass; ?>">
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
                                        <td><?php echo $plazoInterno; ?></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($ultimoDoc): ?>
                                                    <a href="descargar_documento.php?doc_id=<?php echo $ultimoDoc['id']; ?>" class="btn btn-sm btn-success" title="Descargar documento" style="white-space: nowrap;">
                                                        <i class="bi bi-file-earmark-check"></i> Ver Doc
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                            data-bs-target="#modalCargar"
                                                            onclick="seleccionarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', <?php echo $mesSeleccionado; ?>)"
                                                            style="white-space: nowrap;">
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
                                echo '<tr><td colspan="7" class="text-center text-muted">No hay items mensuales asignados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB TRIMESTRAL -->
            <div class="tab-pane fade" id="trimestral" role="tabpanel">
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5>Items Trimestrales</h5>
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
                            if (!empty($itemsPorPeriodicidad['trimestral'])) {
                                foreach ($itemsPorPeriodicidad['trimestral'] as $item) {
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoActual, $mesActual);
                                    
                                    // Obtener documentos
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
                                                <?php elseif ($ultimoDoc && $current_user['perfil'] === 'cargador_informacion'): ?>
                                                    <button class="btn btn-sm btn-warning" title="Agregar verificador" style="white-space: nowrap;" data-bs-toggle="modal"
                                                            data-bs-target="#modalAgregarVerificador"
                                                            onclick="prepararAgregarVerificador(<?php echo $item['id']; ?>, <?php echo $ultimoDoc['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>', '<?php echo htmlspecialchars($item['periodicidad']); ?>', <?php echo $mesActual; ?>, <?php echo $anoActual; ?>)">
                                                        <i class="bi bi-plus-circle"></i> Agregar Verif
                                                    </button>
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
            <div class="tab-pane fade" id="ocurrencia" role="tabpanel">
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5>Items de Ocurrencia Libre</h5>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Numeración</th>
                                <th width="25%">Nombre Item</th>
                                <th width="15%">Plazo Interno</th>
                                <th width="15%">Fecha Envío</th>
                                <th width="15%">Carga Portal</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($itemsPorPeriodicidad['ocurrencia'])) {
                                foreach ($itemsPorPeriodicidad['ocurrencia'] as $item) {
                                    $itemInfo = $itemConPlazoClass->getItemConPlazo($item['id'], $anoActual, $mesActual);
                                    
                                    // Obtener último documento
                                    $docsResult = $itemConPlazoClass->getDocumentosPorMes($item['id'], $user_id, $anoActual, $mesActual);
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
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo $plazoInterno; ?></td>
                                        <td><?php echo $fechaEnvio; ?></td>
                                        <td><?php echo $cargaPortal; ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
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
                                                <button class="btn btn-sm btn-info" title="Verificador de Carga" disabled style="white-space: nowrap;">
                                                    <i class="bi bi-check-circle"></i> Verificador Carga
                                                </button>
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

