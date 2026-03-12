<?php
/**
 * Gestión de Usuarios
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/usuarios.php';

requireRole('admin');

$mensaje = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $nombreCompleto = sanitize($_POST['nombre_completo'] ?? '');
            $rol = sanitize($_POST['rol'] ?? 'girador');
            
            if (empty($username) || empty($password) || empty($nombreCompleto)) {
                $error = 'Todos los campos son obligatorios';
            } else {
                $result = crearUsuario($username, $password, $nombreCompleto, $rol);
                if ($result['success']) {
                    $mensaje = 'Usuario creado exitosamente';
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'editar':
            $id = (int)$_POST['id'];
            $nombreCompleto = sanitize($_POST['nombre_completo'] ?? '');
            $rol = sanitize($_POST['rol'] ?? 'girador');
            $activo = isset($_POST['activo']) ? 1 : 0;
            $password = $_POST['password'] ?? '';
            
            actualizarUsuario($id, $nombreCompleto, $rol, $activo, $password ?: null);
            $mensaje = 'Usuario actualizado exitosamente';
            break;
            
        case 'eliminar':
            $id = (int)$_POST['id'];
            $result = eliminarUsuario($id);
            if ($result['success']) {
                $mensaje = 'Usuario eliminado exitosamente';
            } else {
                $error = $result['message'];
            }
            break;
    }
}

$usuarios = getUsuarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Sistema de Numeración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-people me-2"></i>Gestión de Usuarios</h2>
                    <p class="text-muted mb-0">Administre los usuarios del sistema</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo Usuario
                </button>
            </div>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre Completo</th>
                                <th class="text-center">Rol</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usr): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-person-circle me-2"></i>
                                    <?= htmlspecialchars($usr['username']) ?>
                                </td>
                                <td><?= htmlspecialchars($usr['nombre_completo']) ?></td>
                                <td class="text-center">
                                    <?php 
                                    $roles = [
                                        'admin' => ['bg-danger', 'Administrador'],
                                        'girador' => ['bg-primary', 'Girador'],
                                        'emisor' => ['bg-info', 'Emisor']
                                    ];
                                    $rol = $roles[$usr['rol']] ?? ['bg-secondary', $usr['rol']];
                                    ?>
                                    <span class="badge <?= $rol[0] ?>"><?= $rol[1] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($usr['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editarUsuario(<?= htmlspecialchars(json_encode($usr)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($usr['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="eliminarUsuario(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['nombre_completo']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nuevo/Editar Usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formUsuario">
                    <input type="hidden" name="accion" id="accionUsuario" value="crear">
                    <input type="hidden" name="id" id="usuarioId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalUsuarioTitle">Nuevo Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Contraseña <span id="passwordReq">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted" id="passwordHelp" style="display: none;">
                                Dejar en blanco para mantener la contraseña actual
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="girador">Girador (Módulo)</option>
                                <option value="emisor">Emisor (Tickets)</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="mb-3" id="divActivoUsuario" style="display: none;">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                <label class="form-check-label" for="activo">Usuario Activo</label>
                            </div>
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
    
    <!-- Modal Confirmar Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" id="eliminarId">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de eliminar al usuario "<span id="eliminarNombre"></span>"?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalUsuario = new bootstrap.Modal(document.getElementById('modalUsuario'));
        const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));
        
        // Limpiar modal al cerrar
        document.getElementById('modalUsuario').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formUsuario').reset();
            document.getElementById('accionUsuario').value = 'crear';
            document.getElementById('usuarioId').value = '';
            document.getElementById('username').readOnly = false;
            document.getElementById('password').required = true;
            document.getElementById('passwordReq').style.display = '';
            document.getElementById('passwordHelp').style.display = 'none';
            document.getElementById('modalUsuarioTitle').textContent = 'Nuevo Usuario';
            document.getElementById('divActivoUsuario').style.display = 'none';
        });
        
        function editarUsuario(usr) {
            document.getElementById('accionUsuario').value = 'editar';
            document.getElementById('usuarioId').value = usr.id;
            document.getElementById('username').value = usr.username;
            document.getElementById('username').readOnly = true;
            document.getElementById('nombre_completo').value = usr.nombre_completo;
            document.getElementById('rol').value = usr.rol;
            document.getElementById('activo').checked = usr.activo == 1;
            document.getElementById('password').required = false;
            document.getElementById('passwordReq').style.display = 'none';
            document.getElementById('passwordHelp').style.display = '';
            document.getElementById('divActivoUsuario').style.display = 'block';
            document.getElementById('modalUsuarioTitle').textContent = 'Editar Usuario';
            modalUsuario.show();
        }
        
        function eliminarUsuario(id, nombre) {
            document.getElementById('eliminarId').value = id;
            document.getElementById('eliminarNombre').textContent = nombre;
            modalEliminar.show();
        }
    </script>
</body>
</html>
