<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

// LUEGO: Incluir header con HTML
require_once '../../includes/header.php';

require_once '../../classes/Direccion.php';
require_once '../../classes/Usuario.php';

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
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'perfil' => $_POST['perfil'] ?? '',
            'direccion_id' => intval($_POST['direccion_id'] ?? 0)
        ];

        if (!empty($data['nombre']) && !empty($data['email']) && !empty($data['password'])) {
            if ($usuarioClass->create($data)) {
                $_SESSION['success'] = 'Usuario creado correctamente';
                $redirect = true;
            } else {
                $error = 'Error al crear el usuario';
            }
        } else {
            $error = 'Complete todos los campos requeridos';
        }
    } elseif ($action === 'update') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'perfil' => $_POST['perfil'] ?? '',
            'direccion_id' => intval($_POST['direccion_id'] ?? 0)
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        if ($usuarioClass->update(intval($_POST['usuario_id']), $data)) {
            $_SESSION['success'] = 'Usuario actualizado correctamente';
            $redirect = true;
        } else {
            $error = 'Error al actualizar el usuario';
        }
    } elseif ($action === 'delete') {
        if ($usuarioClass->deactivate(intval($_POST['usuario_id']))) {
            $_SESSION['success'] = 'Usuario desactivado correctamente';
            $redirect = true;
        } else {
            $error = 'Error al desactivar el usuario';
        }
    }
    
    // PRG Pattern: Redirigir después del POST exitoso
    if ($redirect) {
        header('Location: ' . SITE_URL . 'admin/usuarios/index.php');
        exit;
    }
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$direcciones = $direccionClass->getAll();
$usuarios = $usuarioClass->getAll();
?>

<div class="page-header mb-4 pb-3" style="border-bottom: 3px solid #3498db;">
    <div class="row align-items-center">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box" style="background: #3498db; width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-people text-white" style="font-size: 1.8rem;"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="color: #2c3e50; font-weight: 600;">Gestión de Usuarios</h1>
                    <small class="text-muted">Administra perfiles y permisos del sistema</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal">
                <i class="bi bi-plus-circle"></i> Nuevo Usuario
            </button>
        </div>
    </div>
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
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Dirección</th>
                    <th>Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($usuario['nombre']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo $PROFILES[$usuario['perfil']] ?? $usuario['perfil']; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($usuario['direccion_nombre'] ?? 'N/A'); ?></td>
                        <td><small class="text-muted"><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#usuarioModal"
                                    onclick="editUsuario(<?php echo $usuario['id']; ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Desactivar usuario?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
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

<!-- Modal para crear/editar usuario -->
<div class="modal fade" id="usuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="usuarioModalLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="usuarioForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create" id="formAction">
                    <input type="hidden" name="usuario_id" id="usuarioId">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña <small id="passwordNote"></small></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="perfil" class="form-label">Perfil</label>
                        <select class="form-select" id="perfil" name="perfil" required>
                            <option value="">Seleccionar perfil</option>
                            <?php foreach ($PROFILES as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="direccion_id" class="form-label">Dirección</label>
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
function editUsuario(id) {
    // Cargar datos del usuario
    fetch('get_usuario.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('usuarioModalLabel').textContent = 'Editar Usuario';
            document.getElementById('formAction').value = 'update';
            document.getElementById('usuarioId').value = data.id;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('email').value = data.email;
            document.getElementById('perfil').value = data.perfil;
            document.getElementById('direccion_id').value = data.direccion_id || 0;
            document.getElementById('password').required = false;
            document.getElementById('passwordNote').textContent = '(opcional para editar)';
        });
}

// Reset modal cuando se abre para crear nuevo
document.getElementById('usuarioModal').addEventListener('hide.bs.modal', function() {
    document.getElementById('usuarioForm').reset();
    document.getElementById('usuarioModalLabel').textContent = 'Nuevo Usuario';
    document.getElementById('formAction').value = 'create';
    document.getElementById('usuarioId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordNote').textContent = '';
});
</script>

<?php require_once '../../includes/footer.php'; ?>
