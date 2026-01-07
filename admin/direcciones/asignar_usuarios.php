<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

require_once '../../classes/Direccion.php';
require_once '../../classes/Usuario.php';

$direccionClass = new Direccion($db->getConnection());
$usuarioClass = new Usuario($db->getConnection());

$direccion_id = intval($_GET['direccion_id'] ?? 0);
$error = '';
$success = '';

if ($direccion_id <= 0) {
    header('Location: ' . SITE_URL . 'admin/direcciones/index.php');
    exit;
}

$direccion = $direccionClass->getById($direccion_id);
if (!$direccion) {
    header('Location: ' . SITE_URL . 'admin/direcciones/index.php');
    exit;
}

// Procesar asignación/desasignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    
    if ($action === 'asignar' && $usuario_id > 0) {
        $sql = "UPDATE usuarios SET direccion_id = ? WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("ii", $direccion_id, $usuario_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Usuario asignado correctamente';
        } else {
            $error = 'Error al asignar usuario';
        }
        
        header('Location: ' . SITE_URL . 'admin/direcciones/asignar_usuarios.php?direccion_id=' . $direccion_id);
        exit;
    } elseif ($action === 'desasignar' && $usuario_id > 0) {
        $sql = "UPDATE usuarios SET direccion_id = NULL WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Usuario desasignado correctamente';
        } else {
            $error = 'Error al desasignar usuario';
        }
        
        header('Location: ' . SITE_URL . 'admin/direcciones/asignar_usuarios.php?direccion_id=' . $direccion_id);
        exit;
    }
}

// Mostrar mensaje de sesión
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Obtener usuarios asignados y disponibles
$usuarios_asignados = $direccionClass->getUsuarios($direccion_id);

// Obtener usuarios que NO están asignados a esta dirección
$sql = "SELECT * FROM usuarios 
        WHERE activo = 1 AND (direccion_id != ? OR direccion_id IS NULL)
        ORDER BY nombre ASC";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $direccion_id);
$stmt->execute();
$usuarios_disponibles = $stmt->get_result();

// AHORA SÍ: Incluir header con HTML
require_once '../../includes/header.php';
?>

<div class="page-header mb-4 pb-3" style="border-bottom: 3px solid #3498db;">
    <div class="row align-items-center">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box" style="background: #3498db; width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-person-plus text-white" style="font-size: 1.8rem;"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="color: #2c3e50; font-weight: 600;">
                        <?php echo htmlspecialchars($direccion['nombre']); ?>
                    </h1>
                    <small class="text-muted">Asignar usuarios a esta dirección</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
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

<div class="row">
    <!-- Usuarios Asignados -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill"></i> 
                    Usuarios Asignados (<?php echo $usuarios_asignados->num_rows; ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if ($usuarios_asignados->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($usuario = $usuarios_asignados->fetch_assoc()): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></small>
                                    <br>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($usuario['perfil']); ?>
                                    </span>
                                </div>
                                <form method="POST" onsubmit="return confirm('¿Desasignar este usuario?');">
                                    <input type="hidden" name="action" value="desasignar">
                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-x-circle"></i> Desasignar
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> No hay usuarios asignados a esta dirección
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Usuarios Disponibles -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-person-plus-fill"></i> 
                    Usuarios Disponibles (<?php echo $usuarios_disponibles->num_rows; ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if ($usuarios_disponibles->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($usuario = $usuarios_disponibles->fetch_assoc()): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></small>
                                    <br>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($usuario['perfil']); ?>
                                    </span>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="asignar">
                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-plus-circle"></i> Asignar
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i> No hay usuarios disponibles para asignar
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
