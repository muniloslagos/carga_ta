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
        // Guardar plazo para TODOS los items mensual - mes específico
        $mes = (int)($_POST['mes'] ?? 0);
        $plazo_fecha = $_POST['plazo_fecha'] ?? '';
        $ano = (int)($_POST['ano'] ?? date('Y'));
        
        if ($mes > 0 && $mes <= 12 && $plazo_fecha && $ano) {
            // Obtener todos los items mensual
            $sql = "SELECT id FROM items_transparencia WHERE periodicidad = 'mensual' AND activo = 1";
            $result = $db->getConnection()->query($sql);
            
            $guardados = 0;
            if ($result && $result->num_rows > 0) {
                while ($item = $result->fetch_assoc()) {
                    // Guardar plazo para el mes específico en TODOS los items mensual
                    $resultado = $itemPlazoClass->create([
                        'item_id' => $item['id'],
                        'ano' => $ano,
                        'mes' => $mes,
                        'plazo_interno' => $plazo_fecha,
                        'fecha_carga_portal' => null
                    ]);
                    if ($resultado) $guardados++;
                }
            }
            
            $meses_nombre = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $success = "Plazo para " . $meses_nombre[$mes] . " asignado a todos los items mensual ($guardados registros)";
        } else {
            $error = 'Faltan datos requeridos (mes, fecha, año)';
        }
    } elseif ($tipo_plazo === 'anual') {
        // Guardar plazo individual para item anual
        $item_id = (int)($_POST['item_id'] ?? 0);
        $ano = (int)($_POST['ano'] ?? 0);
        $plazo_fecha = $_POST['plazo_fecha'] ?? '';
        
        if ($item_id && $ano && $plazo_fecha) {
            $resultado = $itemPlazoClass->create([
                'item_id' => $item_id,
                'ano' => $ano,
                'mes' => 1, // Usar mes 1 como referencia para items anuales
                'plazo_interno' => $plazo_fecha,
                'fecha_carga_portal' => null
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
                            <th width="20%">Mes</th>
                            <th width="30%">Fecha de Plazo <?php echo $ano_mensual_actual; ?></th>
                            <th width="50%">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $meses_nombre = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        
                        for ($m = 1; $m <= 12; $m++) {
                            $plazo_mes = $itemPlazoClass->getByItemAndMes($itemsPorPeriodicidad['mensual'][0]['id'] ?? null, $ano_mensual_actual, $m);
                            $fecha_actual = $plazo_mes ? $plazo_mes['plazo_interno'] : '';
                        ?>
                            <tr>
                                <td><strong><?php echo $meses_nombre[$m]; ?> <?php echo $ano_mensual_actual; ?></strong></td>
                                <td>
                                    <?php 
                                    if ($fecha_actual) {
                                        echo '<span class="badge bg-success">' . date('d/m/Y', strtotime($fecha_actual)) . '</span>';
                                    } else {
                                        echo '<span class="text-muted">No configurado</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPlazoMensual"
                                            onclick="editarPlazoMensual(<?php echo $m; ?>, '<?php echo $meses_nombre[$m]; ?>', '<?php echo $fecha_actual; ?>', <?php echo $ano_mensual_actual; ?>)">
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

                    <div class="mb-3">
                        <label for="plazoFechaMensualModal" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="plazoFechaMensualModal" name="plazo_fecha" required>
                        <small class="text-muted">Esta fecha se aplicará a TODOS los items mensual en este mes</small>
                    </div>

                    <div class="alert alert-info">
                        <strong>Nota:</strong> Al guardar, el sistema asignará esta fecha de plazo a todos los items con periodicidad mensual para este mes.
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
                            <th width="15%">Numeración</th>
                            <th width="35%">Nombre</th>
                            <th width="30%">Plazo 2025</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemsPorPeriodicidad['anual'] as $item): ?>
                            <?php 
                            $plazo2025 = $itemPlazoClass->getByItemAndMes($item['id'], 2025, 1);
                            $plazoText = $plazo2025 ? date('d/m/Y', strtotime($plazo2025['plazo_interno'])) : '<span class="text-muted">No configurado</span>';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['numeracion']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                <td><?php echo $plazoText; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPlazoAnual"
                                            onclick="editarPlazoAnual(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['nombre'])); ?>', '<?php echo $plazo2025 ? $plazo2025['plazo_interno'] : ''; ?>')">
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

                    <div class="mb-3">
                        <label for="plazoFechaModal" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="plazoFechaModal" name="plazo_fecha" required>
                        <small class="text-muted">Especifique la fecha de vencimiento para este item</small>
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
function editarPlazoAnual(itemId, itemNombre, plazoFecha) {
    document.getElementById('modalItemId').value = itemId;
    document.getElementById('itemNombreModal').value = itemNombre;
    document.getElementById('plazoFechaModal').value = plazoFecha;
}

function editarPlazoMensual(mes, mesNombre, fechaActual, ano) {
    document.getElementById('modalMes').value = mes;
    document.getElementById('modalAnoMensual').value = ano;
    document.getElementById('mesNombreModal').value = mesNombre + ' ' + ano;
    document.getElementById('plazoFechaMensualModal').value = fechaActual;
}
</script>

<?php require_once '../../includes/footer.php'; ?>
