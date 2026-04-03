<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

require_once '../../classes/Director.php';

$directorClass = new Director($db->getConnection());

$error = '';
$success = '';

// Procesar formulario ANTES de incluir header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = false;

    if ($action === 'create') {
        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'correo' => trim($_POST['correo'] ?? '')
        ];

        if (!empty($data['nombres']) && !empty($data['apellidos'])) {
            if ($directorClass->create($data)) {
                $_SESSION['success'] = 'Director creado correctamente';
                $redirect = true;
            } else {
                $error = 'Error al crear el director';
            }
        } else {
            $error = 'Nombres y apellidos son requeridos';
        }
    } elseif ($action === 'update') {
        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'correo' => trim($_POST['correo'] ?? '')
        ];

        if ($directorClass->update(intval($_POST['director_id']), $data)) {
            $_SESSION['success'] = 'Director actualizado correctamente';
            $redirect = true;
        } else {
            $error = 'Error al actualizar el director';
        }
    } elseif ($action === 'delete') {
        if ($directorClass->deactivate(intval($_POST['director_id']))) {
            $_SESSION['success'] = 'Director desactivado correctamente';
            $redirect = true;
        } else {
            $error = 'Error al desactivar el director';
        }
    }
    
    if ($redirect) {
        header('Location: ' . SITE_URL . 'admin/directores/index.php');
        exit;
    }
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$directores = $directorClass->getAll();

require_once '../../includes/header.php';
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-person-badge"></i> Gestión de Directores</h1>
        </div>
        <div class="col text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#directorModal">
                <i class="bi bi-plus-circle"></i> Nuevo Director
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
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Direcciones Asignadas</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $hayDirectores = false;
                    while ($dir = $directores->fetch_assoc()): 
                        $hayDirectores = true;
                        $direccionesAsignadas = $directorClass->getDireccionesAsignadas($dir['id']);
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dir['nombres']); ?></strong></td>
                            <td><?php echo htmlspecialchars($dir['apellidos']); ?></td>
                            <td>
                                <?php if (!empty($dir['correo'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($dir['correo']); ?>">
                                        <?php echo htmlspecialchars($dir['correo']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Sin correo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($direccionesAsignadas->num_rows > 0): ?>
                                    <?php while ($direc = $direccionesAsignadas->fetch_assoc()): ?>
                                        <span class="badge bg-primary me-1"><?php echo htmlspecialchars($direc['nombre']); ?></span>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#directorModal"
                                        onclick="editDirector(<?php echo $dir['id']; ?>)">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Desactivar este director?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="director_id" value="<?php echo $dir['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$hayDirectores): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No hay directores registrados. <a href="#" data-bs-toggle="modal" data-bs-target="#directorModal">Crear uno</a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar director -->
<div class="modal fade" id="directorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="directorModalLabel">Nuevo Director</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="directorForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create" id="formAction">
                    <input type="hidden" name="director_id" id="directorId">

                    <div class="mb-3">
                        <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombres" name="nombres" required>
                    </div>

                    <div class="mb-3">
                        <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@muniloslagos.cl">
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
function editDirector(id) {
    fetch('get_director.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('directorModalLabel').textContent = 'Editar Director';
            document.getElementById('formAction').value = 'update';
            document.getElementById('directorId').value = data.id;
            document.getElementById('nombres').value = data.nombres;
            document.getElementById('apellidos').value = data.apellidos;
            document.getElementById('correo').value = data.correo || '';
        });
}

document.getElementById('directorModal').addEventListener('hide.bs.modal', function() {
    document.getElementById('directorForm').reset();
    document.getElementById('directorModalLabel').textContent = 'Nuevo Director';
    document.getElementById('formAction').value = 'create';
    document.getElementById('directorId').value = '';
});
</script>

<?php require_once '../../includes/footer.php'; ?>
