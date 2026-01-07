<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

require_once '../../classes/Direccion.php';

$direccionClass = new Direccion($db->getConnection());

$error = '';
$success = '';

// Procesar formulario ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = false;

    if ($action === 'create') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];

        if (!empty($data['nombre'])) {
            if ($direccionClass->create($data)) {
                $_SESSION['success'] = 'Dirección creada correctamente';
                $redirect = true;
            } else {
                $error = 'Error al crear la dirección';
            }
        } else {
            $error = 'El nombre es requerido';
        }
    } elseif ($action === 'update') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];

        if ($direccionClass->update(intval($_POST['direccion_id']), $data)) {
            $_SESSION['success'] = 'Dirección actualizada correctamente';
            $redirect = true;
        } else {
            $error = 'Error al actualizar la dirección';
        }
    } elseif ($action === 'delete') {
        if ($direccionClass->deactivate(intval($_POST['direccion_id']))) {
            $_SESSION['success'] = 'Dirección desactivada correctamente';
            $redirect = true;
        } else {
            $error = 'Error al desactivar la dirección';
        }
    }
    
    // PRG Pattern: Redirigir después del POST exitoso
    if ($redirect) {
        header('Location: ' . SITE_URL . 'admin/direcciones/index.php');
        exit;
    }
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// AHORA SÍ: Incluir header con HTML
require_once '../../includes/header.php';

$direcciones = $direccionClass->getAll();
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-building"></i> Gestión de Direcciones</h1>
        </div>
        <div class="col text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#direccionModal">
                <i class="bi bi-plus-circle"></i> Nueva Dirección
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
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">ID</th>
                        <th width="20%">Nombre</th>
                        <th width="35%">Descripción</th>
                        <th width="12%" class="text-center">Usuarios</th>
                        <th width="28%" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($dir = $direcciones->fetch_assoc()): ?>
                        <?php
                        $usuarios = $direccionClass->getUsuarios($dir['id']);
                        $count = $usuarios->num_rows;
                        ?>
                        <tr>
                            <td><strong><?php echo $dir['id']; ?></strong></td>
                            <td>
                                <i class="bi bi-building text-primary"></i> 
                                <strong><?php echo htmlspecialchars($dir['nombre']); ?></strong>
                            </td>
                            <td class="text-muted">
                                <?php echo htmlspecialchars($dir['descripcion'] ?? 'Sin descripción'); ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">
                                    <i class="bi bi-people"></i> <?php echo $count; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="asignar_usuarios.php?direccion_id=<?php echo $dir['id']; ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="Asignar usuarios">
                                    <i class="bi bi-person-plus"></i>
                                </a>
                                <button class="btn btn-sm btn-warning" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#direccionModal"
                                        onclick="editDireccion(<?php echo $dir['id']; ?>)"
                                        title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Desactivar dirección?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="direccion_id" value="<?php echo $dir['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
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
</div>

<!-- Modal para crear/editar dirección -->
<div class="modal fade" id="direccionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="direccionModalLabel">Nueva Dirección</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="direccionForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create" id="formAction">
                    <input type="hidden" name="direccion_id" id="direccionId">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
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

<script>
function editDireccion(id) {
    fetch('get_direccion.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('direccionModalLabel').textContent = 'Editar Dirección';
            document.getElementById('formAction').value = 'update';
            document.getElementById('direccionId').value = data.id;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('descripcion').value = data.descripcion || '';
        });
}

document.getElementById('direccionModal').addEventListener('hide.bs.modal', function() {
    document.getElementById('direccionForm').reset();
    document.getElementById('direccionModalLabel').textContent = 'Nueva Dirección';
    document.getElementById('formAction').value = 'create';
    document.getElementById('direccionId').value = '';
});
</script>

<?php require_once '../../includes/footer.php'; ?>
