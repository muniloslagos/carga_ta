<?php
// PRIMERO: Autenticación ANTES de salida
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/check_auth.php';
require_login();
require_role('administrativo');

// LUEGO: HTML
require_once '../../includes/header.php';
require_once '../../classes/Item.php';
require_once '../../classes/ItemPlazo.php';
require_once '../../classes/PlazoCalculator.php';

$itemClass = new Item($db->getConnection());
$itemPlazoClass = new ItemPlazo($db->getConnection());

$success = '';
$error = '';

// Procesar POST para plazos mensual y anual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_plazo'])) {
    $tipo_plazo = $_POST['tipo_plazo'] ?? '';
    
    if ($tipo_plazo === 'mensual') {
        // Guardar días extra para TODOS los items mensual - mes específico
        $mes = (int)($_POST['mes'] ?? 0);
        $dias_extra_cargador = max(0, (int)($_POST['dias_extra_cargador'] ?? 0));
        $dias_extra_publicador = max(0, (int)($_POST['dias_extra_publicador'] ?? 0));
        $motivo = trim($_POST['motivo_extension'] ?? '');
        $ano = (int)($_POST['ano'] ?? date('Y'));
        
        if ($mes > 0 && $mes <= 12 && $ano) {
            // Obtener todos los items mensual
            $sql = "SELECT id FROM items_transparencia WHERE periodicidad = 'mensual' AND activo = 1";
            $result = $db->getConnection()->query($sql);
            
            $guardados = 0;
            if ($result && $result->num_rows > 0) {
                while ($item = $result->fetch_assoc()) {
                    $resultado = $itemPlazoClass->create([
                        'item_id' => $item['id'],
                        'ano' => $ano,
                        'mes' => $mes,
                        'plazo_interno' => null,
                        'fecha_carga_portal' => null,
                        'dias_extra_cargador' => $dias_extra_cargador,
                        'dias_extra_publicador' => $dias_extra_publicador,
                        'motivo_extension' => $motivo ?: null
                    ]);
                    if ($resultado) $guardados++;
                }
            }
            
            $meses_nombre = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $success = "Plazo para " . $meses_nombre[$mes] . " asignado a todos los items mensual ($guardados registros)";
        } else {
            $error = 'Faltan datos requeridos (mes, año)';
        }
    } elseif ($tipo_plazo === 'anual') {
        // Guardar días extra individual para item anual
        $item_id = (int)($_POST['item_id'] ?? 0);
        $ano = (int)($_POST['ano'] ?? 0);
        $dias_extra_cargador = max(0, (int)($_POST['dias_extra_cargador'] ?? 0));
        $dias_extra_publicador = max(0, (int)($_POST['dias_extra_publicador'] ?? 0));
        $motivo = trim($_POST['motivo_extension'] ?? '');
        
        if ($item_id && $ano) {
            // Obtener mes_carga_anual del item
            $itemData = $itemClass->getById($item_id);
            $mesCargaAnual = intval($itemData['mes_carga_anual'] ?? 1);
            
            $resultado = $itemPlazoClass->create([
                'item_id' => $item_id,
                'ano' => $ano,
                'mes' => $mesCargaAnual,
                'plazo_interno' => null,
                'fecha_carga_portal' => null,
                'dias_extra_cargador' => $dias_extra_cargador,
                'dias_extra_publicador' => $dias_extra_publicador,
                'motivo_extension' => $motivo ?: null
            ]);
            
            if ($resultado) {
                $success = 'Plazo anual guardado exitosamente';
            } else {
                $error = 'Error al guardar el plazo';
            }
        } else {
            $error = 'Faltan datos requeridos';
        }
    }
}

// Obtener items agrupados por periodicidad
$itemsResult = $itemClass->getAll();
$itemsPorPeriodicidad = [
    'mensual' => [],
    'anual' => [],
    'trimestral' => [],
    'semestral' => [],
    'ocurrencia' => []
];

while ($item = $itemsResult->fetch_assoc()) {
    $periodicidad = strtolower($item['periodicidad']);
    if (isset($itemsPorPeriodicidad[$periodicidad])) {
        $itemsPorPeriodicidad[$periodicidad][] = $item;
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$anoActual = (int)date('Y');
?>

<div class="page-header">
    <h1><i class="bi bi-calendar-check"></i> Gestión de Plazos Internos</h1>
    <p class="text-white-50">Configurar fechas de vencimiento para envío de documentos</p>
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

<!-- ITEMS MENSUAL -->
<?php if (!empty($itemsPorPeriodicidad['mensual'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-month"></i> Items Mensual</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">Configure el <strong>día de vencimiento para cada mes del año</strong>. Por ejemplo:</p>
            <ul class="text-muted mb-4">
                <li><strong>Enero 2025:</strong> especifique la fecha de vencimiento para enero</li>
                <li><strong>Febrero 2025:</strong> especifique la fecha de vencimiento para febrero</li>
                <li>...así para cada mes</li>
                <li><strong>Esta fecha se aplicará a TODOS los items con periodicidad mensual</strong></li>
            </ul>
            
            <!-- Selector de Año -->
            <div class="mb-4">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <label for="ano_selector" class="form-label">Seleccionar Año</label>
                        <select class="form-select" name="ano_mensual" id="ano_selector" onchange="this.form.submit();">
                            <?php
                            $ano_actual = (int)date('Y');
                            $ano_mensual_actual = isset($_GET['ano_mensual']) ? (int)$_GET['ano_mensual'] : $ano_actual;
                            for ($a = $ano_actual - 1; $a <= $ano_actual + 3; $a++) {
                                $selected = ($a == $ano_mensual_actual) ? 'selected' : '';
                                echo "<option value='$a' $selected>$a</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">Mes</th>
                            <th width="25%">Plazo Cargador (6° + extra)</th>
                            <th width="25%">Plazo Publicador (10° + extra)</th>
                            <th width="20%">Motivo</th>
                            <th width="15%">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $meses_nombre = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        
                        for ($m = 1; $m <= 12; $m++) {
                            $plazo_mes = $itemPlazoClass->getByItemAndMes($itemsPorPeriodicidad['mensual'][0]['id'] ?? null, $ano_mensual_actual, $m);
                            $dias_extra_c = $plazo_mes ? (int)($plazo_mes['dias_extra_cargador'] ?? 0) : 0;
                            $dias_extra_p = $plazo_mes ? (int)($plazo_mes['dias_extra_publicador'] ?? 0) : 0;
                            $motivo_actual = $plazo_mes && isset($plazo_mes['motivo_extension']) ? $plazo_mes['motivo_extension'] : '';
                            
                            // Calcular fechas resultantes
                            $plazoCargador = PlazoCalculator::calcularPlazoEnvioConExtra('mensual', $ano_mensual_actual, $m, $dias_extra_c);
                            $plazoPublicador = PlazoCalculator::calcularPlazoPublicacionConExtra('mensual', $ano_mensual_actual, $m, $dias_extra_p);
                            $plazoBaseCargador = PlazoCalculator::calcularPlazoEnvio('mensual', $ano_mensual_actual, $m);
                            $plazoBasePublicador = PlazoCalculator::calcularPlazoPublicacion('mensual', $ano_mensual_actual, $m);
                        ?>
                            <tr>
                                <td><strong><?php echo $meses_nombre[$m]; ?></strong></td>
                                <td>
                                    <?php if ($plazoCargador): ?>
                                        <span class="badge bg-primary"><?php echo date('d/m/Y', strtotime($plazoCargador)); ?></span>
                                        <?php if ($dias_extra_c > 0): ?>
                                            <br><small class="text-success"><i class="bi bi-plus-circle"></i> <?php echo $dias_extra_c; ?> día(s) extra</small>
                                        <?php else: ?>
                                            <br><small class="text-muted">Base: 6° hábil</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($plazoPublicador): ?>
                                        <span class="badge bg-success"><?php echo date('d/m/Y', strtotime($plazoPublicador)); ?></span>
                                        <?php if ($dias_extra_p > 0): ?>
                                            <br><small class="text-success"><i class="bi bi-plus-circle"></i> <?php echo $dias_extra_p; ?> día(s) extra</small>
                                        <?php else: ?>
                                            <br><small class="text-muted">Base: 10° hábil</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($motivo_actual)): ?>
                                        <small><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($motivo_actual); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPlazoMensual"
                                            onclick="editarPlazoMensual(<?php echo $m; ?>, '<?php echo $meses_nombre[$m]; ?>', <?php echo $dias_extra_c; ?>, <?php echo $dias_extra_p; ?>, <?php echo $ano_mensual_actual; ?>, '<?php echo addslashes($motivo_actual); ?>')">
                                        <i class="bi bi-pencil"></i> Configurar
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <hr class="my-4">
            
            <h6 class="mb-3">Items Mensual (<strong><?php echo count($itemsPorPeriodicidad['mensual']); ?></strong>):</h6>
            <div class="row">
                <?php foreach ($itemsPorPeriodicidad['mensual'] as $item): ?>
                    <div class="col-md-6 mb-2">
                        <span class="badge bg-info"><?php echo htmlspecialchars($item['numeracion']); ?></span>
                        <?php echo htmlspecialchars($item['nombre']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL EDITAR PLAZO MENSUAL -->
<div class="modal fade" id="modalPlazoMensual" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configurar Plazo Mensual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="tipo_plazo" value="mensual">
                    <input type="hidden" name="guardar_plazo" value="1">
                    <input type="hidden" name="mes" id="modalMes">
                    <input type="hidden" name="ano" id="modalAnoMensual">

                    <div class="mb-3">
                        <label for="mesNombreModal" class="form-label">Mes</label>
                        <input type="text" class="form-control" id="mesNombreModal" disabled>
                    </div>

                    <div class="alert alert-info mb-3">
                        <strong><i class="bi bi-calculator"></i> Plazos automáticos base:</strong>
                        <div class="mt-2">
                            <span class="badge bg-primary">Cargador:</span> <span id="plazoBaseCargadorMensual">-</span> (6° día hábil)
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-success">Publicador:</span> <span id="plazoBasePublicadorMensual">-</span> (10° día hábil)
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="diasExtraCargadorMensual" class="form-label">
                                <i class="bi bi-upload text-primary"></i> Días extra <strong>Cargador</strong>
                            </label>
                            <input type="number" class="form-control" id="diasExtraCargadorMensual" 
                                   name="dias_extra_cargador" min="0" max="20" value="0"
                                   oninput="actualizarPreviewMensual()">
                            <small class="text-muted">Se suman a los 6 días hábiles base</small>
                        </div>
                        <div class="col-md-6">
                            <label for="diasExtraPublicadorMensual" class="form-label">
                                <i class="bi bi-globe text-success"></i> Días extra <strong>Publicador</strong>
                            </label>
                            <input type="number" class="form-control" id="diasExtraPublicadorMensual" 
                                   name="dias_extra_publicador" min="0" max="20" value="0"
                                   oninput="actualizarPreviewMensual()">
                            <small class="text-muted">Se suman a los 10 días hábiles base</small>
                        </div>
                    </div>

                    <div class="alert alert-success mb-3" id="previewPlazos">
                        <strong><i class="bi bi-eye"></i> Vista previa:</strong>
                        <div class="mt-2">
                            <span class="badge bg-primary">Cargador:</span> <span id="previewCargador">-</span>
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-success">Publicador:</span> <span id="previewPublicador">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="motivoExtensionMensual" class="form-label">Motivo de Extensión (opcional)</label>
                        <input type="text" class="form-control" id="motivoExtensionMensual" name="motivo_extension" 
                               placeholder="Ej: Ampliado por feriados 18 y 19 de septiembre"
                               maxlength="255">
                        <small class="text-muted">Complete solo si extiende el plazo por feriados u otro motivo</small>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Nota:</strong> Los días extra se aplicarán a todos los items con periodicidad mensual para este mes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ITEMS TRIMESTRAL -->
<?php if (!empty($itemsPorPeriodicidad['trimestral'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-calendar3"></i> Items Trimestral (Plazos Fijos)</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <strong>Plazos Fijos Definidos:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Q1 (Enero-Marzo):</strong> Vencimiento <strong>7 de Abril</strong></li>
                    <li><strong>Q2 (Abril-Junio):</strong> Vencimiento <strong>7 de Julio</strong></li>
                    <li><strong>Q3 (Julio-Septiembre):</strong> Vencimiento <strong>7 de Noviembre</strong></li>
                    <li><strong>Q4 (Octubre-Diciembre):</strong> Vencimiento <strong>7 de Enero</strong> del año siguiente</li>
                </ul>
            </div>
            
            <hr class="my-3">
            
            <h6 class="mb-3">Items Trimestrales (<strong><?php echo count($itemsPorPeriodicidad['trimestral']); ?></strong>):</h6>
            <div class="row">
                <?php foreach ($itemsPorPeriodicidad['trimestral'] as $item): ?>
                    <div class="col-md-6 mb-2">
                        <span class="badge bg-info"><?php echo htmlspecialchars($item['numeracion']); ?></span>
                        <?php echo htmlspecialchars($item['nombre']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ITEMS SEMESTRAL -->
<?php if (!empty($itemsPorPeriodicidad['semestral'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-calendar2"></i> Items Semestral (Plazos Fijos)</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-0">
                <strong>Plazos Fijos Definidos:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>S1 (Enero-Junio):</strong> Vencimiento <strong>7 de Agosto</strong></li>
                    <li><strong>S2 (Julio-Diciembre):</strong> Vencimiento <strong>7 de Enero</strong> del año siguiente</li>
                </ul>
            </div>
            
            <hr class="my-3">
            
            <h6 class="mb-3">Items Semestrales (<strong><?php echo count($itemsPorPeriodicidad['semestral']); ?></strong>):</h6>
            <div class="row">
                <?php foreach ($itemsPorPeriodicidad['semestral'] as $item): ?>
                    <div class="col-md-6 mb-2">
                        <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($item['numeracion']); ?></span>
                        <?php echo htmlspecialchars($item['nombre']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ITEMS ANUAL -->
<?php if (!empty($itemsPorPeriodicidad['anual'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-calendar"></i> Items Anual</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">Configure la fecha de vencimiento <strong>individual para cada item anual</strong>.</p>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">Num.</th>
                            <th width="25%">Nombre</th>
                            <th width="20%">Plazo Cargador</th>
                            <th width="20%">Plazo Publicador</th>
                            <th width="15%">Motivo</th>
                            <th width="10%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemsPorPeriodicidad['anual'] as $item): ?>
                            <?php 
                            $mesCargaAnual = intval($item['mes_carga_anual'] ?? 1);
                            $plazo2025 = $itemPlazoClass->getByItemAndMes($item['id'], $anoActual, $mesCargaAnual);
                            $dias_extra_c_anual = $plazo2025 ? (int)($plazo2025['dias_extra_cargador'] ?? 0) : 0;
                            $dias_extra_p_anual = $plazo2025 ? (int)($plazo2025['dias_extra_publicador'] ?? 0) : 0;
                            $motivo_anual = $plazo2025 ? ($plazo2025['motivo_extension'] ?? '') : '';
                            
                            $plazoCargadorAnual = PlazoCalculator::calcularPlazoEnvioConExtra('anual', $anoActual, $mesCargaAnual, $dias_extra_c_anual);
                            $plazoPublicadorAnual = PlazoCalculator::calcularPlazoPublicacionConExtra('anual', $anoActual, $mesCargaAnual, $dias_extra_p_anual);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                <td>
                                    <?php if ($plazoCargadorAnual): ?>
                                        <span class="badge bg-primary"><?php echo date('d/m/Y', strtotime($plazoCargadorAnual)); ?></span>
                                        <?php if ($dias_extra_c_anual > 0): ?>
                                            <br><small class="text-success">+<?php echo $dias_extra_c_anual; ?> día(s)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($plazoPublicadorAnual): ?>
                                        <span class="badge bg-success"><?php echo date('d/m/Y', strtotime($plazoPublicadorAnual)); ?></span>
                                        <?php if ($dias_extra_p_anual > 0): ?>
                                            <br><small class="text-success">+<?php echo $dias_extra_p_anual; ?> día(s)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($motivo_anual)): ?>
                                        <small><?php echo htmlspecialchars($motivo_anual); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPlazoAnual"
                                            onclick="editarPlazoAnual(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['nombre'])); ?>', <?php echo $dias_extra_c_anual; ?>, <?php echo $dias_extra_p_anual; ?>, '<?php echo addslashes($motivo_anual); ?>')">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL EDITAR PLAZO ANUAL -->
<div class="modal fade" id="modalPlazoAnual" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configurar Plazo Anual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="tipo_plazo" value="anual">
                    <input type="hidden" name="guardar_plazo" value="1">
                    <input type="hidden" name="item_id" id="modalItemId">

                    <div class="mb-3">
                        <label for="itemNombreModal" class="form-label">Item</label>
                        <input type="text" class="form-control" id="itemNombreModal" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="anoModal" class="form-label">Año <span class="text-danger">*</span></label>
                        <select class="form-select" name="ano" id="anoModal" required>
                            <?php
                            for ($a = $anoActual; $a <= $anoActual + 2; $a++) {
                                echo "<option value='$a' " . ($a == $anoActual ? 'selected' : '') . ">$a</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="alert alert-info mb-3">
                        <strong><i class="bi bi-calculator"></i> Plazos automáticos base:</strong>
                        <div class="mt-2">
                            <span class="badge bg-primary">Cargador:</span> 6° día hábil del mes correspondiente
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-success">Publicador:</span> 10° día hábil del mes correspondiente
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="diasExtraCargadorAnual" class="form-label">
                                <i class="bi bi-upload text-primary"></i> Días extra <strong>Cargador</strong>
                            </label>
                            <input type="number" class="form-control" id="diasExtraCargadorAnual" 
                                   name="dias_extra_cargador" min="0" max="20" value="0">
                            <small class="text-muted">Se suman a los 6 días hábiles base</small>
                        </div>
                        <div class="col-md-6">
                            <label for="diasExtraPublicadorAnual" class="form-label">
                                <i class="bi bi-globe text-success"></i> Días extra <strong>Publicador</strong>
                            </label>
                            <input type="number" class="form-control" id="diasExtraPublicadorAnual" 
                                   name="dias_extra_publicador" min="0" max="20" value="0">
                            <small class="text-muted">Se suman a los 10 días hábiles base</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="motivoExtensionAnual" class="form-label">Motivo de Extensión (opcional)</label>
                        <input type="text" class="form-control" id="motivoExtensionAnual" name="motivo_extension" 
                               placeholder="Ej: Ampliado por feriados de año nuevo"
                               maxlength="255">
                        <small class="text-muted">Complete solo si extiende el plazo por feriados u otro motivo</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarPlazoAnual(itemId, itemNombre, diasExtraCargador, diasExtraPublicador, motivo) {
    document.getElementById('modalItemId').value = itemId;
    document.getElementById('itemNombreModal').value = itemNombre;
    document.getElementById('diasExtraCargadorAnual').value = diasExtraCargador || 0;
    document.getElementById('diasExtraPublicadorAnual').value = diasExtraPublicador || 0;
    document.getElementById('motivoExtensionAnual').value = motivo || '';
}

// Variables globales para preview mensual
var _mesModal = 0;
var _anoModal = 0;

function editarPlazoMensual(mes, mesNombre, diasExtraCargador, diasExtraPublicador, ano, motivo) {
    document.getElementById('modalMes').value = mes;
    document.getElementById('modalAnoMensual').value = ano;
    document.getElementById('mesNombreModal').value = mesNombre + ' ' + ano;
    document.getElementById('diasExtraCargadorMensual').value = diasExtraCargador || 0;
    document.getElementById('diasExtraPublicadorMensual').value = diasExtraPublicador || 0;
    document.getElementById('motivoExtensionMensual').value = motivo || '';
    
    _mesModal = mes;
    _anoModal = ano;
    
    // Calcular mes deadline para mensual (M+1)
    var mesDeadline = mes + 1;
    var anoDeadline = ano;
    if (mesDeadline > 12) {
        mesDeadline = 1;
        anoDeadline++;
    }
    
    // Plazos base
    var baseCargador = calcularNesimoDiaHabil(anoDeadline, mesDeadline, 6);
    var basePublicador = calcularNesimoDiaHabil(anoDeadline, mesDeadline, 10);
    
    document.getElementById('plazoBaseCargadorMensual').innerHTML = '<strong>' + baseCargador + '</strong>';
    document.getElementById('plazoBasePublicadorMensual').innerHTML = '<strong>' + basePublicador + '</strong>';
    
    actualizarPreviewMensual();
}

function actualizarPreviewMensual() {
    var diasExtraC = parseInt(document.getElementById('diasExtraCargadorMensual').value) || 0;
    var diasExtraP = parseInt(document.getElementById('diasExtraPublicadorMensual').value) || 0;
    
    var mesDeadline = _mesModal + 1;
    var anoDeadline = _anoModal;
    if (mesDeadline > 12) {
        mesDeadline = 1;
        anoDeadline++;
    }
    
    var previewC = calcularNesimoDiaHabil(anoDeadline, mesDeadline, 6 + diasExtraC);
    var previewP = calcularNesimoDiaHabil(anoDeadline, mesDeadline, 10 + diasExtraP);
    
    var labelC = diasExtraC > 0 ? previewC + ' <span class="text-success">(+' + diasExtraC + ' días)</span>' : previewC;
    var labelP = diasExtraP > 0 ? previewP + ' <span class="text-success">(+' + diasExtraP + ' días)</span>' : previewP;
    
    document.getElementById('previewCargador').innerHTML = '<strong>' + labelC + '</strong>';
    document.getElementById('previewPublicador').innerHTML = '<strong>' + labelP + '</strong>';
}

// Calcula el N-ésimo día hábil de un mes (lun-vie, sin feriados)
function calcularNesimoDiaHabil(ano, mes, n) {
    var diasHabiles = 0;
    var dia = 1;
    var maxDias = new Date(ano, mes, 0).getDate();
    var meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    while (diasHabiles < n && dia <= maxDias) {
        var fecha = new Date(ano, mes - 1, dia);
        var diaSemana = fecha.getDay(); // 0=domingo, 6=sábado
        
        if (diaSemana !== 0 && diaSemana !== 6) {
            diasHabiles++;
            if (diasHabiles === n) {
                return dia + ' de ' + meses[mes] + ' ' + ano;
            }
        }
        dia++;
    }
    
    return 'No calculable';
}
</script>

<?php require_once '../../includes/footer.php'; ?>
