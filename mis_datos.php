<?php
session_start();
require_once 'config/database.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener datos actuales del usuario
$stmt = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    // Validaciones básicas
    if (empty($nombre) || empty($email)) {
        $mensaje = 'El nombre y el correo son obligatorios.';
        $tipo_mensaje = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El correo electrónico no es válido.';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar si el correo ya existe (para otro usuario)
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $_SESSION['user_id']);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $mensaje = 'El correo electrónico ya está registrado por otro usuario.';
            $tipo_mensaje = 'danger';
        } else {
            // Si se está cambiando la contraseña
            if (!empty($password_nueva)) {
                if (strlen($password_nueva) < 6) {
                    $mensaje = 'La nueva contraseña debe tener al menos 6 caracteres.';
                    $tipo_mensaje = 'danger';
                } elseif ($password_nueva !== $password_confirmar) {
                    $mensaje = 'Las contraseñas nuevas no coinciden.';
                    $tipo_mensaje = 'danger';
                } elseif (empty($password_actual)) {
                    $mensaje = 'Debe ingresar su contraseña actual para cambiarla.';
                    $tipo_mensaje = 'danger';
                } else {
                    // Verificar contraseña actual
                    $stmt_pass = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
                    $stmt_pass->bind_param("i", $_SESSION['user_id']);
                    $stmt_pass->execute();
                    $result_pass = $stmt_pass->get_result();
                    $user_pass = $result_pass->fetch_assoc();
                    
                    if (!password_verify($password_actual, $user_pass['password'])) {
                        $mensaje = 'La contraseña actual es incorrecta.';
                        $tipo_mensaje = 'danger';
                    } else {
                        // Actualizar nombre, email y contraseña
                        $nueva_password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                        $stmt_update = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ? WHERE id = ?");
                        $stmt_update->bind_param("sssi", $nombre, $email, $nueva_password_hash, $_SESSION['user_id']);
                        
                        if ($stmt_update->execute()) {
                            $_SESSION['user_name'] = $nombre; // Actualizar sesión
                            $mensaje = 'Sus datos han sido actualizados correctamente.';
                            $tipo_mensaje = 'success';
                            
                            // Recargar datos del usuario
                            $usuario['nombre'] = $nombre;
                            $usuario['email'] = $email;
                        } else {
                            $mensaje = 'Error al actualizar los datos.';
                            $tipo_mensaje = 'danger';
                        }
                    }
                }
            } else {
                // Solo actualizar nombre y email (sin cambio de contraseña)
                $stmt_update = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
                $stmt_update->bind_param("ssi", $nombre, $email, $_SESSION['user_id']);
                
                if ($stmt_update->execute()) {
                    $_SESSION['user_name'] = $nombre; // Actualizar sesión
                    $mensaje = 'Sus datos han sido actualizados correctamente.';
                    $tipo_mensaje = 'success';
                    
                    // Recargar datos del usuario
                    $usuario['nombre'] = $nombre;
                    $usuario['email'] = $email;
                } else {
                    $mensaje = 'Error al actualizar los datos.';
                    $tipo_mensaje = 'danger';
                }
            }
        }
    }
}

$titulo = "Mis Datos";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle"></i> Mis Datos</h5>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($mensaje) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($usuario['email']) ?>" required>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Cambiar Contraseña (opcional)</h6>
                        <p class="text-muted small">Deje estos campos vacíos si no desea cambiar su contraseña.</p>
                        
                        <div class="mb-3">
                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="password_actual" name="password_actual">
                            <small class="text-muted">Requerida solo si desea cambiar su contraseña.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password_nueva" name="password_nueva" minlength="6">
                            <small class="text-muted">Mínimo 6 caracteres.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" minlength="6">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Cambios
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
