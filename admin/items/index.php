<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

require_once '../../classes/Item.php';
require_once '../../classes/Direccion.php';
require_once '../../classes/Usuario.php';

$itemClass = new Item($db->getConnection());
$direccionClass = new Direccion($db->getConnection());
$usuarioClass = new Usuario($db->getConnection());
$conn = $db->getConnection();

$error = '';
$success = '';

// Procesar formulario ANTES de incluir header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = false;

    if ($action === 'create') {
        $data = [
            'numeracion' => trim($_POST['numeracion'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'direccion_id' => intval($_POST['direccion_id'] ?? 0),
            'periodicidad' => $_POST['periodicidad'] ?? '',
            'mes_carga_anual' => $_POST['mes_carga_anual'] ?? null
        ];

        if (!empty($data['numeracion']) && !empty($data['nombre']) && !empty($data['periodicidad'])) {
            if ($itemClass->create($data)) {
                $_SESSION['success'] = 'Item creado correctamente';
                $redirect = true;
            } else {
                $error = 'Error al crear el item';
            }
        } else {
            $error = 'Complete todos los campos requeridos';
        }
    } elseif ($action === 'update') {
        $data = [
            'numeracion' => trim($_POST['numeracion'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'direccion_id' => intval($_POST['direccion_id'] ?? 0),
            'periodicidad' => $_POST['periodicidad'] ?? '',
            'mes_carga_anual' => $_POST['mes_carga_anual'] ?? null
        ];

        if ($itemClass->update(intval($_POST['item_id']), $data)) {
            $_SESSION['success'] = 'Item actualizado correctamente';
            $redirect = true;
        } else {
            $error = 'Error al actualizar el item';
        }
    } elseif ($action === 'delete') {
        if ($itemClass->deactivate(intval($_POST['item_id']))) {
            $_SESSION['success'] = 'Item desactivado correctamente';
            $redirect = true;
        } else {
            $error = 'Error al desactivar el item';
        }
    } elseif ($action === 'assign_user') {
        $item_id = intval($_POST['item_id']);
        $usuario_id = intval($_POST['usuario_id']);
        
        if ($itemClass->assignUser($item_id, $usuario_id)) {
            $_SESSION['success'] = 'Usuario asignado correctamente';
            $redirect = true;
        } else {
            $error = 'Error al asignar usuario';
        }
    } elseif ($action === 'save_item_usuarios') {
        // Guardar múltiples asignaciones de usuarios a un item
        $item_id = intval($_POST['item_id']);
        $usuarios = json_decode($_POST['usuarios'] ?? '[]', true);
        
        header('Content-Type: application/json');
        
        if (!$item_id || !is_array($usuarios)) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($usuarios as $user) {
            $usuario_id = intval($user['usuario_id'] ?? 0);
            $checked = (bool)($user['checked'] ?? false);
            
            if ($usuario_id > 0) {
                if ($checked) {
                    $result = $itemClass->assignUser($item_id, $usuario_id);
                } else {
                    $result = $itemClass->unassignUser($item_id, $usuario_id);
                }
                
                if ($result) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        echo json_encode([
            'success' => $error_count === 0,
            'message' => "Guardado: $success_count usuarios, Errores: $error_count"
        ]);
        exit;
    } elseif ($action === 'save_item_publicadores') {
        // Guardar múltiples asignaciones de publicadores a un item
        $item_id = intval($_POST['item_id']);
        $publicadores = json_decode($_POST['publicadores'] ?? '[]', true);
        
        header('Content-Type: application/json');
        
        if (!$item_id || !is_array($publicadores)) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($publicadores as $pub) {
            $usuario_id = intval($pub['usuario_id'] ?? 0);
            $checked = (bool)($pub['checked'] ?? false);
            
            if ($usuario_id > 0) {
                if ($checked) {
                    // Insertar si no existe
                    $stmt = $conn->prepare("INSERT IGNORE INTO item_publicadores (item_id, usuario_id, asignado_por) VALUES (?, ?, ?)");
                    $stmt->bind_param('iii', $item_id, $usuario_id, $_SESSION['user_id']);
                    $result = $stmt->execute();
                } else {
                    // Eliminar asignación
                    $stmt = $conn->prepare("DELETE FROM item_publicadores WHERE item_id = ? AND usuario_id = ?");
                    $stmt->bind_param('ii', $item_id, $usuario_id);
                    $result = $stmt->execute();
                }
                
                if ($result) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        echo json_encode([
            'success' => $error_count === 0,
            'message' => "Guardado: $success_count publicadores, Errores: $error_count"
        ]);
        exit;
    } elseif ($action === 'bulk_assign_users') {
        // Asignación masiva de usuarios a múltiples items
        $item_ids = json_decode($_POST['item_ids'] ?? '[]', true);
        $usuario_ids = json_decode($_POST['usuario_ids'] ?? '[]', true);
        
        header('Content-Type: application/json');
        
        if (!is_array($item_ids) || !is_array($usuario_ids) || empty($item_ids) || empty($usuario_ids)) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar items y usuarios']);
            exit;
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($item_ids as $item_id) {
            foreach ($usuario_ids as $usuario_id) {
                if ($itemClass->assignUser(intval($item_id), intval($usuario_id))) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        echo json_encode([
            'success' => $error_count === 0,
            'message' => "Asignados: $success_count, Errores: $error_count"
        ]);
        exit;
    }
    
    // PRG Pattern: Redirigir después del POST exitoso
    if ($redirect) {
        header('Location: ' . SITE_URL . 'admin/items/index.php');
        exit;
    }
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Obtener filtros
$filter_direccion = isset($_GET['direccion_id']) ? intval($_GET['direccion_id']) : null;
$filter_periodicidad = isset($_GET['periodicidad']) ? $_GET['periodicidad'] : null;
$filter_numeracion = isset($_GET['numeracion']) ? trim($_GET['numeracion']) : null;

// Aplicar filtros
$items = $itemClass->getAll($filter_direccion, $filter_periodicidad);

// Filtrar por numeración si se proporciona (búsqueda parcial)
if ($filter_numeracion) {
    $items_filtered = [];
    while ($item = $items->fetch_assoc()) {
        if (stripos($item['numeracion'], $filter_numeracion) !== false || 
            stripos($item['nombre'], $filter_numeracion) !== false) {
            $items_filtered[] = $item;
        }
    }
    // Convertir array a resultado similar
    $items = (object)['filtered' => $items_filtered, 'is_filtered' => true];
} else {
    $items_array = [];
    while ($item = $items->fetch_assoc()) {
        $items_array[] = $item;
    }
    $items = (object)['filtered' => $items_array, 'is_filtered' => false];
}

$direcciones = $direccionClass->getAll();

// DESPUÉS: Incluir header con HTML
require_once '../../includes/header.php';

// Asegurar que las variables de configuración estén disponibles
global $PERIODICIDADES;
if (!isset($PERIODICIDADES)) {
    $PERIODICIDADES = [
        'mensual' => 'Mensual',
        'trimestral' => 'Trimestral',
        'semestral' => 'Semestral',
        'anual' => 'Anual',
        'ocurrencia' => 'Ocurrencia'
    ];
}
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-file-text"></i> Gestión de Items de Transparencia</h1>
        </div>
        <div class="col text-end">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                <i class="bi bi-people-fill"></i> Asignación Masiva
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
                <i class="bi bi-plus-circle"></i> Nuevo Item
            </button>
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

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Dirección</label>
                <select name="direccion_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas las direcciones</option>
                    <?php 
                    $direcciones->data_seek(0);
                    while ($dir = $direcciones->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $dir['id']; ?>" <?php echo ($filter_direccion == $dir['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dir['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Periodicidad</label>
                <select name="periodicidad" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas las periodicidades</option>
                    <?php foreach ($PERIODICIDADES as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($filter_periodicidad === $key) ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Buscar por numeración/nombre</label>
                <input type="text" name="numeracion" class="form-control" placeholder="Ej: 1.1 o nombre" value="<?php echo htmlspecialchars($filter_numeracion ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                    <?php if ($filter_direccion || $filter_periodicidad || $filter_numeracion): ?>
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Limpiar</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light">
        <div class="row align-items-center">
            <div class="col">
                <span class="badge bg-info">Total: <?php echo count($items->filtered); ?> items</span>
            </div>
            <div class="col text-end">
                <button class="btn btn-sm btn-outline-primary" onclick="toggleSelectAll()">
                    <i class="bi bi-check-square"></i> Seleccionar Todo
                </button>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="50">
                        <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()">
                    </th>
                    <th>Numeración</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Periodicidad</th>
                    <th>Cargadores</th>
                    <th>Publicadores</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items->filtered as $item): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="item-checkbox" value="<?php echo $item['id']; ?>">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($item['numeracion']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($item['direccion_nombre'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo $PERIODICIDADES[$item['periodicidad']] ?? $item['periodicidad']; ?></span>
                            <?php if ($item['periodicidad'] === 'anual' && !empty($item['mes_carga_anual'])): 
                                $mesesNombre = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                            ?>
                                <br><small class="text-muted">Mes: <?php echo $mesesNombre[$item['mes_carga_anual']]; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $usuarios = $itemClass->getAsignedUsers($item['id']);
                            $count = $usuarios->num_rows;
                            echo '<small>' . $count . ' cargador(es)</small>';
                            ?>
                        </td>
                        <td>
                            <?php
                            $stmt_pub = $conn->prepare("SELECT COUNT(*) as total FROM item_publicadores WHERE item_id = ?");
                            $stmt_pub->bind_param('i', $item['id']);
                            $stmt_pub->execute();
                            $count_pub = $stmt_pub->get_result()->fetch_assoc()['total'];
                            echo '<small>' . $count_pub . ' publicador(es)</small>';
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#usuariosModal"
                                    onclick="loadItemUsers(<?php echo $item['id']; ?>)"
                                    title="Asignar Cargadores">
                                <i class="bi bi-person-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-success" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#publicadoresModal"
                                    onclick="loadItemPublicadores(<?php echo $item['id']; ?>)"
                                    title="Asignar Publicadores">
                                <i class="bi bi-person-check"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#itemModal"
                                    onclick="editItem(<?php echo $item['id']; ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Desactivar item?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para crear/editar item -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Nuevo Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="itemForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create" id="formAction">
                    <input type="hidden" name="item_id" id="itemId">

                    <div class="mb-3">
                        <label for="numeracion" class="form-label">Numeración</label>
                        <input type="text" class="form-control" id="numeracion" name="numeracion" placeholder="Ej: 1, 1.1, 2.3" required>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="direccion_id" class="form-label">Dirección Responsable</label>
                        <select class="form-select" id="direccion_id" name="direccion_id">
                            <option value="0">Seleccionar dirección</option>
                            <?php 
                            $direcciones->data_seek(0);
                            while ($dir = $direcciones->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $dir['id']; ?>"><?php echo htmlspecialchars($dir['nombre']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="periodicidad" class="form-label">Periodicidad</label>
                        <select class="form-select" id="periodicidad" name="periodicidad" required onchange="toggleMesCargaAnual()">
                            <option value="">Seleccionar periodicidad</option>
                            <?php foreach ($PERIODICIDADES as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="mesCargaAnualContainer" style="display:none;">
                        <label for="mes_carga_anual" class="form-label">Mes de Carga (Anual)</label>
                        <select class="form-select" id="mes_carga_anual" name="mes_carga_anual">
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                        <small class="text-muted">Mes en que se debe cargar este item anual</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para asignar usuarios -->
<div class="modal fade" id="usuariosModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Cargadores al Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="usuariosContainer"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarUsuarios">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para asignar publicadores -->
<div class="modal fade" id="publicadoresModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Asignar Publicadores al Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Los publicadores podrán cargar verificadores para los documentos de este item.
                </div>
                <div id="publicadoresContainer"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarPublicadores">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para asignación masiva -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignación Masiva de Usuarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Seleccione los items en la tabla y luego elija los usuarios a asignar.
                </div>
                
                <div class="mb-3">
                    <h6>Items seleccionados: <span id="selectedItemsCount" class="badge bg-primary">0</span></h6>
                    <div id="selectedItemsList" class="small text-muted"></div>
                </div>
                
                <hr>
                
                <h6>Seleccionar Usuarios:</h6>
                <?php 
                $usuarios = $usuarioClass->getAll();
                ?>
                <div class="row">
                    <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input bulk-user-check" type="checkbox" value="<?php echo $usuario['id']; ?>" id="bulkuser<?php echo $usuario['id']; ?>">
                                <label class="form-check-label" for="bulkuser<?php echo $usuario['id']; ?>">
                                    <?php echo htmlspecialchars($usuario['nombre']); ?> - <?php echo htmlspecialchars($usuario['email']); ?>
                                </label>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnBulkAssign">
                    <i class="bi bi-check-circle"></i> Asignar a Items Seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para seleccionar/deseleccionar todos los items
function toggleSelectAll() {
    const mainCheckbox = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = mainCheckbox.checked);
    updateBulkAssignModal();
}

// Actualizar contador de items seleccionados
function updateBulkAssignModal() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedItemsCount').textContent = count;
    
    if (count > 0) {
        let itemsList = 'Items: ';
        checkboxes.forEach((cb, index) => {
            const row = cb.closest('tr');
            const numeracion = row.querySelector('td:nth-child(2) strong').textContent;
            itemsList += numeracion + (index < count - 1 ? ', ' : '');
        });
        document.getElementById('selectedItemsList').textContent = itemsList;
    } else {
        document.getElementById('selectedItemsList').textContent = 'Ningún item seleccionado';
    }
}

// Actualizar cuando se cambie un checkbox individual
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkAssignModal);
    });
    
    // Asignación masiva
    document.getElementById('btnBulkAssign').addEventListener('click', function() {
        const itemCheckboxes = document.querySelectorAll('.item-checkbox:checked');
        const userCheckboxes = document.querySelectorAll('.bulk-user-check:checked');
        
        if (itemCheckboxes.length === 0) {
            alert('Debe seleccionar al menos un item');
            return;
        }
        
        if (userCheckboxes.length === 0) {
            alert('Debe seleccionar al menos un usuario');
            return;
        }
        
        const itemIds = Array.from(itemCheckboxes).map(cb => parseInt(cb.value));
        const userIds = Array.from(userCheckboxes).map(cb => parseInt(cb.value));
        
        if (!confirm(`¿Asignar ${userIds.length} usuario(s) a ${itemIds.length} item(s)?`)) {
            return;
        }
        
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
        
        const form = new FormData();
        form.append('action', 'bulk_assign_users');
        form.append('item_ids', JSON.stringify(itemIds));
        form.append('usuario_ids', JSON.stringify(userIds));
        
        fetch('', {
            method: 'POST',
            body: form
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Asignación completada: ' + data.message);
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Error al asignar'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Asignar a Items Seleccionados';
            }
        })
        .catch(error => {
            alert('Error de conexión');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Asignar a Items Seleccionados';
        });
    });
});

function editItem(id) {
    fetch('get_item.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('itemModalLabel').textContent = 'Editar Item';
            document.getElementById('formAction').value = 'update';
            document.getElementById('itemId').value = data.id;
            document.getElementById('numeracion').value = data.numeracion;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('direccion_id').value = data.direccion_id || 0;
            document.getElementById('periodicidad').value = data.periodicidad;
            toggleMesCargaAnual();
            if (data.periodicidad === 'anual' && data.mes_carga_anual) {
                document.getElementById('mes_carga_anual').value = data.mes_carga_anual;
            }
        });
}

function toggleMesCargaAnual() {
    var periodicidad = document.getElementById('periodicidad').value;
    var container = document.getElementById('mesCargaAnualContainer');
    container.style.display = (periodicidad === 'anual') ? 'block' : 'none';
}

function loadItemUsers(itemId) {
    fetch('get_usuarios_item.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
            // Convertir asignados a números
            const asignadosIds = data.asignados.map(id => parseInt(id));
            
            let html = '<div class="row">';
            
            data.usuarios.forEach(usuario => {
                const usuarioId = parseInt(usuario.id);
                const checked = asignadosIds.includes(usuarioId) ? 'checked' : '';
                html += '<div class="col-md-6 mb-3"><div class="form-check"><input class="form-check-input usuario-check" type="checkbox" value="' + usuario.id + '" ' + checked + ' data-item="' + itemId + '"><label class="form-check-label">' + usuario.nombre + ' (' + usuario.email + ')</label></div></div>';
            });
            
            html += '</div>';
            document.getElementById('usuariosContainer').innerHTML = html;
            
            // Configurar botón Guardar
            const btnGuardar = document.getElementById('btnGuardarUsuarios');
            btnGuardar.onclick = function() {
                const checkboxes = document.querySelectorAll('.usuario-check');
                const usuarios = [];
                
                checkboxes.forEach(cb => {
                    usuarios.push({
                        usuario_id: parseInt(cb.value),
                        checked: cb.checked
                    });
                });
                
                // Enviar todos los cambios
                const form = new FormData();
                form.append('action', 'save_item_usuarios');
                form.append('item_id', itemId);
                form.append('usuarios', JSON.stringify(usuarios));
                
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
                
                fetch('', {
                    method: 'POST',
                    body: form
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Error al guardar'));
                        btnGuardar.disabled = false;
                        btnGuardar.innerHTML = '<i class="bi bi-check-circle"></i> Guardar Cambios';
                    }
                })
                .catch(error => {
                    alert('Error de conexión');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-check-circle"></i> Guardar Cambios';
                });
            };
        });
}

// Cargar publicadores de un item
function loadItemPublicadores(itemId) {
    fetch('get_publicadores_item.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
            // Convertir asignados a números
            const asignadosIds = data.asignados.map(id => parseInt(id));
            
            let html = '<div class="row">';
            
            if (data.publicadores.length === 0) {
                html += '<div class="col-12"><div class="alert alert-warning">No hay usuarios con perfil "Publicador" en el sistema.</div></div>';
            } else {
                data.publicadores.forEach(publicador => {
                    const publicadorId = parseInt(publicador.id);
                    const checked = asignadosIds.includes(publicadorId) ? 'checked' : '';
                    html += '<div class="col-md-6 mb-3"><div class="form-check"><input class="form-check-input publicador-check" type="checkbox" value="' + publicador.id + '" ' + checked + ' data-item="' + itemId + '"><label class="form-check-label">' + publicador.nombre + ' (' + publicador.email + ')</label></div></div>';
                });
            }
            
            html += '</div>';
            document.getElementById('publicadoresContainer').innerHTML = html;
            
            // Configurar botón Guardar
            const btnGuardar = document.getElementById('btnGuardarPublicadores');
            btnGuardar.onclick = function() {
                const checkboxes = document.querySelectorAll('.publicador-check');
                const publicadores = [];
                
                checkboxes.forEach(cb => {
                    publicadores.push({
                        usuario_id: parseInt(cb.value),
                        checked: cb.checked
                    });
                });
                
                // Enviar todos los cambios
                const form = new FormData();
                form.append('action', 'save_item_publicadores');
                form.append('item_id', itemId);
                form.append('publicadores', JSON.stringify(publicadores));
                
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
                
                fetch('', {
                    method: 'POST',
                    body: form
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Error al guardar'));
                        btnGuardar.disabled = false;
                        btnGuardar.innerHTML = '<i class="bi bi-check-circle"></i> Guardar Cambios';
                    }
                })
                .catch(error => {
                    alert('Error de conexión');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-check-circle"></i> Guardar Cambios';
                });
            };
        });
}

document.getElementById('itemModal').addEventListener('hide.bs.modal', function() {
    document.getElementById('itemForm').reset();
    document.getElementById('itemModalLabel').textContent = 'Nuevo Item';
    document.getElementById('formAction').value = 'create';
    document.getElementById('itemId').value = '';
    document.getElementById('mesCargaAnualContainer').style.display = 'none';
});

// Actualizar items seleccionados cuando se abre el modal de asignación masiva
document.getElementById('bulkAssignModal').addEventListener('show.bs.modal', function() {
    updateBulkAssignModal();
});
</script>

<?php require_once '../../includes/footer.php'; ?>
