<?php
/**
 * Recuperación de contraseña - Solicitud de token
 */
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/CorreoManager.php';

$db = new Database();
$conn = $db->getConnection();

$mensaje = '';
$tipo_mensaje = ''; // success o error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $mensaje = 'Por favor, ingresa tu correo electrónico.';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Por favor, ingresa un correo electrónico válido.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si existe la tabla password_reset_tokens
        $checkTable = $conn->query("SHOW TABLES LIKE 'password_reset_tokens'");
        
        if (!$checkTable || $checkTable->num_rows === 0) {
            $mensaje = 'El sistema de recuperación de contraseña no está configurado. Contacta al administrador.';
            $tipo_mensaje = 'error';
        } else {
            // Buscar usuario por email
            $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE email = ? AND activo = 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            $stmt->close();
            
            // IMPORTANTE: Siempre mostrar el mismo mensaje para evitar enumeración de usuarios
            $mensaje = 'Si el correo está registrado en el sistema, recibirás un enlace de recuperación en los próximos minutos.';
            $tipo_mensaje = 'success';
            
            if ($usuario) {
                try {
                    $correoManager = new CorreoManager($conn);
                    $correoManager->enviarRecuperacionPassword($usuario['id']);
                } catch (Exception $e) {
                    // No mostrar el error al usuario por seguridad
                    error_log("Error al enviar correo de recuperación: " . $e->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo SITE_NAME; ?></title>
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
        .recovery-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        .recovery-icon {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="recovery-card">
            <div class="recovery-icon">
                <i class="bi bi-key"></i>
            </div>
            
            <h2 class="text-center mb-3">Recuperar Contraseña</h2>
            <p class="text-center text-muted mb-4">
                Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
            </p>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="usuario@ejemplo.cl" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-send"></i> Enviar Enlace de Recuperación
                </button>
            </form>
            
            <hr>
            
            <div class="text-center">
                <a href="<?php echo SITE_URL; ?>login.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
