<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

require_once '../../classes/Direccion.php';
require_once '../../classes/Usuario.php';

$direccionClass = new Direccion($db->getConnection());
$usuarioClass = new Usuario($db->getConnection());

// Definir perfiles disponibles
$PROFILES = [
    'administrativo' => 'Administrador',
    'cargador_informacion' => 'Cargador de Información',
    'publicador' => 'Publicador',
    'auditor' => 'Auditor'
];

$error = '';
$success = '';

// Procesar formulario ANTES de incluir header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = false;

    if ($action === 'create') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'perfil' => '', // Ya no se usa directamente
            'direccion_id' => intval($_POST['direccion_id'] ?? 0)
        ];
        
        $perfiles_seleccionados = $_POST['perfiles'] ?? [];

        if (!empty($data['nombre']) && !empty($data['email']) && !empty($data['password']) && !empty($perfiles_seleccionados)) {
            // Usar el primer perfil seleccionado como perfil principal en la tabla usuarios (compatibilidad)
            $data['perfil'] = $perfiles_seleccionados[0];
            
            if ($usuarioClass->create($data)) {
                // Obtener el ID del usuario recién creado
                $nuevo_usuario_id = $db->getConnection()->insert_id;
                
                // Agregar todos los perfiles seleccionados
                foreach ($perfiles_seleccionados as $index => $perfil) {
                    $es_principal = ($index === 0) ? 1 : 0;
                    $usuarioClass->agregarPerfil($nuevo_usuario_id, $perfil, $_SESSION['user_id'], $es_principal);
                }
                
                $_SESSION['success'] = 'Usuario creado correctamente con ' . count($perfiles_seleccionados) . ' perfil(es)';
                $redirect = true;
            } else {
                $error = 'Error al crear el usuario: ' . $db->getConnection()->error;
            }
        } else {
            $error = 'Complete todos los campos requeridos y seleccione al menos un perfil';
        }
    } elseif ($action === 'update') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'perfil' => '', // Se actualizará después
            'direccion_id' => intval($_POST['direccion_id'] ?? 0)
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        $perfiles_seleccionados = $_POST['perfiles'] ?? [];
        $usuario_id = intval($_POST['usuario_id']);

        if (!empty($perfiles_seleccionados)) {
            // Usar el primer perfil seleccionado como perfil principal
            $data['perfil'] = $perfiles_seleccionados[0];
            
            if ($usuarioClass->update($usuario_id, $data)) {
                // Obtener perfiles actuales del usuario
                $perfiles_actuales = $usuarioClass->getPerfiles($usuario_id);
                
                // Eliminar perfiles que ya no están seleccionados
                foreach ($perfiles_actuales as $perfil_actual) {
                    if (!in_array($perfil_actual, $perfiles_seleccionados)) {
                        $usuarioClass->eliminarPerfil($usuario_id, $perfil_actual);
                    }
                }
                
                // Agregar nuevos perfiles seleccionados
                foreach ($perfiles_seleccionados as $index => $perfil) {
                    $es_principal = ($index === 0) ? 1 : 0;
                    $usuarioClass->agregarPerfil($usuario_id, $perfil, $_SESSION['user_id'], $es_principal);
                }
                
                $_SESSION['success'] = 'Usuario actualizado correctamente con ' . count($perfiles_seleccionados) . ' perfil(es)';
                $redirect = true;
            } else {
                $error = 'Error al actualizar el usuario';
            }
        } else {
            $error = 'Debe seleccionar al menos un perfil';
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

// DESPUÉS: Incluir header con HTML
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-people"></i> Gestión de Usuarios</h1>
        </div>
        <div class="col text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal">
                <i class="bi bi-plus-circle"></i> Nuevo Usuario
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
                            <?php 
                            // Obtener perfiles del usuario
                            $perfiles_usuario = $usuarioClass->getPerfiles($usuario['id']);
                            if (empty($perfiles_usuario)) {
                                // Fallback al perfil de la tabla usuarios
                                echo '<span class="badge bg-info">' . ($PROFILES[$usuario['perfil']] ?? $usuario['perfil']) . '</span>';
                            } else {
                                foreach ($perfiles_usuario as $index => $perfil) {
                                    $badge_class = $index === 0 ? 'bg-primary' : 'bg-info';
                                    echo '<span class="badge ' . $badge_class . ' me-1">' . ($PROFILES[$perfil] ?? $perfil);
                                    if ($index === 0) echo ' <i class="bi bi-star-fill" title="Principal"></i>';
                                    echo '</span>';
                                }
                            }
                            ?>
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
                        <label class="form-label">Perfiles <small class="text-muted">(selecciona uno o más)</small></label>
                        <div class="border rounded p-3">
                            <?php foreach ($PROFILES as $key => $value): ?>
                                <div class="form-check">
                                    <input class="form-check-input perfil-checkbox" 
                                           type="checkbox" 
                                           name="perfiles[]" 
                                           value="<?php echo $key; ?>" 
                                           id="perfil_<?php echo $key; ?>">
                                    <label class="form-check-label" for="perfil_<?php echo $key; ?>">
                                        <?php echo $value; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 
                            El primer perfil seleccionado será el perfil principal
                        </div>
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
            document.getElementById('direccion_id').value = data.direccion_id || 0;
            document.getElementById('password').required = false;
            document.getElementById('passwordNote').textContent = '(opcional para editar)';
            
            // Desmarcar todos los checkboxes primero
            document.querySelectorAll('.perfil-checkbox').forEach(cb => cb.checked = false);
            
            // Marcar los perfiles asignados al usuario
            if (data.perfiles && Array.isArray(data.perfiles)) {
                data.perfiles.forEach(perfil => {
                    const checkbox = document.getElementById('perfil_' + perfil);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        });
}

// Reset modal cuando se cierra
document.getElementById('usuarioModal').addEventListener('hide.bs.modal', function() {
    document.getElementById('usuarioForm').reset();
    document.getElementById('usuarioModalLabel').textContent = 'Nuevo Usuario';
    document.getElementById('formAction').value = 'create';
    document.getElementById('usuarioId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordNote').textContent = '';
});

// Validar que al menos un perfil esté seleccionado antes de enviar
document.getElementById('usuarioForm').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('.perfil-checkbox:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Debes seleccionar al menos un perfil');
        return false;
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
