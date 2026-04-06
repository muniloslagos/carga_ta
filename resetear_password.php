<?php
/**
 * Restablecer contraseña con token
 */
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

$token = $_GET['token'] ?? '';
$mensaje = '';
$tipo_mensaje = ''; // success, error, warning
$token_valido = false;
$usuario_id = null;

// Validar formato del token
if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $mensaje = 'El enlace de recuperación no es válido.';
    $tipo_mensaje = 'error';
} else {
    // Verificar si existe la tabla
    $checkTable = $conn->query("SHOW TABLES LIKE 'password_reset_tokens'");
    
    if (!$checkTable || $checkTable->num_rows === 0) {
        $mensaje = 'El sistema de recuperación de contraseña no está configurado. Contacta al administrador.';
        $tipo_mensaje = 'error';
    } else {
        // Buscar token en la base de datos
        $stmt = $conn->prepare("
            SELECT prt.id, prt.usuario_id, prt.usado, prt.fecha_expiracion,
                   u.nombre, u.email
            FROM password_reset_tokens prt
            INNER JOIN usuarios u ON prt.usuario_id = u.id
            WHERE prt.token = ?
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_data = $result->fetch_assoc();
        $stmt->close();
        
        if (!$token_data) {
            $mensaje = 'El enlace de recuperación no existe o ha sido eliminado.';
            $tipo_mensaje = 'error';
        } elseif ($token_data['usado']) {
            $mensaje = 'Este enlace ya fue utilizado. Si necesitas restablecer tu contraseña nuevamente, solicita un nuevo enlace.';
            $tipo_mensaje = 'warning';
        } elseif (strtotime($token_data['fecha_expiracion']) < time()) {
            $mensaje = 'Este enlace ha expirado. Los enlaces de recuperación son válidos por 1 hora. Solicita uno nuevo.';
            $tipo_mensaje = 'warning';
        } else {
            $token_valido = true;
            $usuario_id = $token_data['usuario_id'];
        }
    }
}

// Procesar formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nueva_password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    if (empty($nueva_password)) {
        $mensaje = 'Por favor, ingresa una nueva contraseña.';
        $tipo_mensaje = 'error';
    } elseif (strlen($nueva_password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
        $tipo_mensaje = 'error';
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden.';
        $tipo_mensaje = 'error';
    } else {
        // Actualizar contraseña
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $password_hash, $usuario_id);
        
        if ($stmt->execute()) {
            // Marcar token como usado
            $stmt2 = $conn->prepare("UPDATE password_reset_tokens SET usado = 1 WHERE token = ?");
            $stmt2->bind_param('s', $token);
            $stmt2->execute();
            $stmt2->close();
            
            $mensaje = '¡Contraseña actualizada exitosamente! Ahora puedes iniciar sesión con tu nueva contraseña.';
            $tipo_mensaje = 'success';
            $token_valido = false; // Ocultar formulario
        } else {
            $mensaje = 'Error al actualizar la contraseña. Inténtalo nuevamente.';
            $tipo_mensaje = 'error';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .reset-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }
        .password-strength {
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-card">
            <div class="reset-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            
            <h2 class="text-center mb-3">Restablecer Contraseña</h2>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php 
                    echo $tipo_mensaje === 'success' ? 'success' : 
                        ($tipo_mensaje === 'warning' ? 'warning' : 'danger'); 
                ?> alert-dismissible fade show">
                    <i class="bi bi-<?php 
                        echo $tipo_mensaje === 'success' ? 'check-circle' : 
                            ($tipo_mensaje === 'warning' ? 'exclamation-circle' : 'exclamation-triangle'); 
                    ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($token_valido): ?>
                <p class="text-center text-muted mb-4">
                    Ingresa tu nueva contraseña para la cuenta: <strong><?php echo htmlspecialchars($token_data['email']); ?></strong>
                </p>
                
                <form method="POST" action="" id="resetForm">
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Mínimo 6 caracteres" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small class="text-muted" id="strengthText"></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_password" class="form-label">Confirmar Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" 
                                   placeholder="Repite la contraseña" required minlength="6">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-check-circle"></i> Actualizar Contraseña
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center mt-4">
                    <a href="<?php echo SITE_URL; ?>recuperar_password.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-counterclockwise"></i> Solicitar Nuevo Enlace
                    </a>
                </div>
            <?php endif; ?>
            
            <hr>
            
            <div class="text-center">
                <a href="<?php echo SITE_URL; ?>login.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Password strength indicator
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            const colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745'];
            const texts = ['Débil', 'Regular', 'Buena', 'Fuerte'];
            const widths = ['25%', '50%', '75%', '100%'];
            
            const level = Math.min(strength - 1, 3);
            if (level >= 0) {
                strengthBar.style.width = widths[level];
                strengthBar.style.backgroundColor = colors[level];
                strengthText.textContent = texts[level];
                strengthText.style.color = colors[level];
            } else {
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
            }
        });
        
        // Validar que las contraseñas coincidan
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmar = document.getElementById('confirmar_password').value;
            
            if (password !== confirmar) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html>
