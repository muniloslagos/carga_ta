<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

// LUEGO: Incluir header con HTML
require_once '../../includes/header.php';

require_once '../../classes/Item.php';
require_once '../../classes/Direccion.php';
require_once '../../classes/Usuario.php';

$itemClass = new Item($db->getConnection());
$direccionClass = new Direccion($db->getConnection());
$usuarioClass = new Usuario($db->getConnection());

$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = false;

    if ($action === 'create') {
        $data = [
            'numeracion' => trim($_POST['numeracion'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'direccion_id' => intval($_POST['direccion_id'] ?? 0),
            'periodicidad' => $_POST['periodicidad'] ?? ''
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
            'periodicidad' => $_POST['periodicidad'] ?? ''
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

$items = $itemClass->getAll();
$direcciones = $direccionClass->getAll();
?>

<div class="page-header mb-4 pb-3" style="border-bottom: 2px solid #e0e0e0;">
    <div class="row align-items-center">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(79,172,254,0.3);">
                    <i class="bi bi-list-check text-white" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h1 class="mb-1" style="color: #2c3e50; font-weight: 600; font-size: 1.5rem;">Gestión de Items de Transparencia</h1>
                    <small class="text-muted" style="font-size: 0.875rem;">Administra los items y sus plazos</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <button class="btn btn-lg" data-bs-toggle="modal" data-bs-target="#itemModal" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; box-shadow: 0 3px 10px rgba(79,172,254,0.3); font-weight: 500;">
                <i class="bi bi-plus-circle-fill"></i> Nuevo Item
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

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Numeración</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Periodicidad</th>
                    <th>Usuarios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['numeracion']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($item['direccion_nombre'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo $PERIODICIDADES[$item['periodicidad']] ?? $item['periodicidad']; ?></span>
                        </td>
                        <td>
                            <?php
                            $usuarios = $itemClass->getAsignedUsers($item['id']);
                            $count = $usuarios->num_rows;
                            echo '<small>' . $count . ' usuario(s)</small>';
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#usuariosModal"
                                    onclick="loadItemUsers(<?php echo $item['id']; ?>)">
                                <i class="bi bi-person-plus"></i>
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
                <?php endwhile; ?>
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
                        <select class="form-select" id="periodicidad" name="periodicidad" required>
                            <option value="">Seleccionar periodicidad</option>
                            <?php foreach ($PERIODICIDADES as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
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
                <h5 class="modal-title">Asignar Usuarios al Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="usuariosContainer"></div>
            </div>
        </div>
    </div>
</div>

<script>
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
        });
}

function loadItemUsers(itemId) {
    fetch('get_usuarios_item.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
            let html = '<form method="POST" id="asignForm"><input type="hidden" name="action" value="assign_user"><input type="hidden" name="item_id" value="' + itemId + '">';
            html += '<div class="row">';
            
            data.usuarios.forEach(usuario => {
                const checked = data.asignados.includes(usuario.id) ? 'checked' : '';
                html += '<div class="col-md-6 mb-3"><div class="form-check"><input class="form-check-input usuario-check" type="checkbox" value="' + usuario.id + '" ' + checked + ' data-item="' + itemId + '"><label class="form-check-label">' + usuario.nombre + ' (' + usuario.email + ')</label></div></div>';
            });
            
            html += '</div></form>';
            document.getElementById('usuariosContainer').innerHTML = html;
            
            // Event listener para checkboxes
            document.querySelectorAll('.usuario-check').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const form = new FormData();
                    form.append('action', 'assign_user');
                    form.append('item_id', itemId);
                    form.append('usuario_id', this.value);
                    
                    fetch('', { method: 'POST', body: form })
                        .then(() => location.reload());
                });
            });
        });
}

document.getElementById('itemModal').addEventListener('hide.bs.modal', function() {
    document.getElementById('itemForm').reset();
    document.getElementById('itemModalLabel').textContent = 'Nuevo Item';
    document.getElementById('formAction').value = 'create';
    document.getElementById('itemId').value = '';
});
</script>

<?php require_once '../../includes/footer.php'; ?>
