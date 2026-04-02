<?php
require_once dirname(dirname(__DIR__)) . '/includes/check_auth.php';

// Verificar permisos - Solo administrativo
if ($current_profile !== 'administrativo') {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

require_once dirname(dirname(__DIR__)) . '/includes/header.php';

$conn = $db->getConnection();
$mensaje = '';
$error = '';
$tipo_mensaje = 'success';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_smtp'])) {
        try {
            $smtp_host = trim($_POST['smtp_host'] ?? '');
            $smtp_port = (int)($_POST['smtp_port'] ?? 587);
            $smtp_usuario = trim($_POST['smtp_usuario'] ?? '');
            $smtp_password = trim($_POST['smtp_password'] ?? '');
            $smtp_encriptacion = $_POST['smtp_encriptacion'] ?? 'tls';
            $smtp_de_correo = trim($_POST['smtp_de_correo'] ?? '');
            $smtp_de_nombre = trim($_POST['smtp_de_nombre'] ?? 'Sistema de Transparencia');
            $smtp_activo = isset($_POST['smtp_activo']) ? 1 : 0;
            
            // Validaciones
            if (empty($smtp_host)) {
                throw new Exception('El servidor SMTP es obligatorio');
            }
            if (empty($smtp_usuario)) {
                throw new Exception('El usuario SMTP es obligatorio');
            }
            if (empty($smtp_de_correo)) {
                throw new Exception('El correo del remitente es obligatorio');
            }
            if (!filter_var($smtp_de_correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El correo del remitente no es válido');
            }
            
            // Verificar si existe configuración
            $config_existe = $conn->query("SELECT id FROM configuracion_smtp LIMIT 1")->fetch_assoc();
            
            if ($config_existe) {
                // Actualizar
                $stmt = $conn->prepare("UPDATE configuracion_smtp SET 
                    smtp_host = ?, 
                    smtp_port = ?, 
                    smtp_usuario = ?, 
                    smtp_password = ?, 
                    smtp_encriptacion = ?, 
                    smtp_de_correo = ?, 
                    smtp_de_nombre = ?, 
                    smtp_activo = ?,
                    modificado_por = ?
                    WHERE id = ?");
                $stmt->bind_param('sissssssii', 
                    $smtp_host, 
                    $smtp_port, 
                    $smtp_usuario, 
                    $smtp_password, 
                    $smtp_encriptacion, 
                    $smtp_de_correo, 
                    $smtp_de_nombre, 
                    $smtp_activo,
                    $_SESSION['user_id'],
                    $config_existe['id']
                );
            } else {
                // Insertar
                $stmt = $conn->prepare("INSERT INTO configuracion_smtp 
                    (smtp_host, smtp_port, smtp_usuario, smtp_password, smtp_encriptacion, smtp_de_correo, smtp_de_nombre, smtp_activo, modificado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sisssssii', 
                    $smtp_host, 
                    $smtp_port, 
                    $smtp_usuario, 
                    $smtp_password, 
                    $smtp_encriptacion, 
                    $smtp_de_correo, 
                    $smtp_de_nombre, 
                    $smtp_activo,
                    $_SESSION['user_id']
                );
            }
            
            if ($stmt->execute()) {
                $mensaje = 'Configuración SMTP guardada exitosamente';
                $tipo_mensaje = 'success';
            } else {
                throw new Exception('Error al guardar la configuración');
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    } elseif (isset($_POST['probar_smtp'])) {
        // Probar conexión SMTP usando datos guardados en BD
        try {
            $correo_prueba = trim($_POST['correo_prueba'] ?? '');
            
            if (empty($correo_prueba) || !filter_var($correo_prueba, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ingrese un correo electrónico válido para la prueba');
            }
            
            // Obtener configuración guardada de la BD
            $config_bd = $conn->query("SELECT * FROM configuracion_smtp ORDER BY id DESC LIMIT 1")->fetch_assoc();
            
            if (!$config_bd) {
                throw new Exception('No existe configuración SMTP. Guarde la configuración primero antes de probar.');
            }
            
            $smtp_host = $config_bd['smtp_host'];
            $smtp_port = (int)$config_bd['smtp_port'];
            $smtp_usuario = $config_bd['smtp_usuario'];
            $smtp_password = $config_bd['smtp_password'];
            $smtp_encriptacion = $config_bd['smtp_encriptacion'];
            $smtp_de_correo = $config_bd['smtp_de_correo'];
            $smtp_de_nombre = $config_bd['smtp_de_nombre'];
            
            // Validar datos guardados
            if (empty($smtp_host) || empty($smtp_usuario) || empty($smtp_password) || empty($smtp_de_correo)) {
                throw new Exception('La configuración guardada está incompleta. Complete todos los campos y guarde antes de probar.');
            }
            
            // Usar PHPMailer directamente para probar
            require_once dirname(dirname(__DIR__)) . '/vendor/phpmailer/phpmailer/src/Exception.php';
            require_once dirname(dirname(__DIR__)) . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once dirname(dirname(__DIR__)) . '/vendor/phpmailer/phpmailer/src/SMTP.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_usuario;
            $mail->Password = $smtp_password;
            $mail->Port = $smtp_port;
            
            // Configurar encriptación
            if ($smtp_encriptacion === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtp_encriptacion === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            // Configuración de caracteres
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Remitente
            $mail->setFrom($smtp_de_correo, $smtp_de_nombre);
            
            // Destinatario
            $mail->addAddress($correo_prueba);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = 'Prueba de Configuración SMTP - Sistema de Transparencia';
            $mail->Body = '<h2>Prueba de Configuración SMTP</h2>';
            $mail->Body .= '<p>Este es un correo de prueba para verificar la configuración del servidor SMTP.</p>';
            $mail->Body .= '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>';
            $mail->Body .= '<p><strong>Servidor:</strong> ' . htmlspecialchars($smtp_host) . ':' . $smtp_port . '</p>';
            $mail->Body .= '<p><strong>Encriptación:</strong> ' . strtoupper($smtp_encriptacion) . '</p>';
            $mail->Body .= '<hr><p><small>Si recibió este correo, la configuración SMTP está funcionando correctamente.</small></p>';
            $mail->AltBody = 'Prueba SMTP';
            
            $mail->send();
            
            $mensaje = '✓ Correo de prueba enviado exitosamente a ' . htmlspecialchars($correo_prueba);
            $mensaje .= '<br><small class="text-muted">Revisa tu bandeja de entrada y carpeta de spam.</small>';
            $tipo_mensaje = 'success';
            
            // Marcar como verificado
            $conn->query("UPDATE configuracion_smtp SET smtp_verificado = 1");
            
        } catch (Exception $e) {
            $error = 'Error en prueba SMTP: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
            
            // Marcar como no verificado si existe configuración
            $conn->query("UPDATE configuracion_smtp SET smtp_verificado = 0");
        }
    }
}

// Obtener configuración actual
$config_smtp = $conn->query("SELECT * FROM configuracion_smtp ORDER BY id DESC LIMIT 1")->fetch_assoc();

if (!$config_smtp) {
    $config_smtp = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_usuario' => '',
        'smtp_password' => '',
        'smtp_encriptacion' => 'tls',
        'smtp_de_correo' => '',
        'smtp_de_nombre' => 'Sistema de Transparencia Activa',
        'smtp_activo' => 0,
        'smtp_verificado' => 0
    ];
}
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-envelope-at"></i> Configuración de Correo Electrónico (SMTP)</h2>
                    <p class="text-muted">Configure el servidor SMTP para el envío de notificaciones automáticas</p>
                </div>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver al Panel
                </a>
            </div>
            <hr>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Configuración del Servidor SMTP</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formSmtp">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Información importante:</strong> Configure correctamente el servidor SMTP para que el sistema pueda enviar notificaciones automáticas a los usuarios.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="smtp_host" class="form-label">Servidor SMTP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                       value="<?= htmlspecialchars($config_smtp['smtp_host']) ?>" required
                                       placeholder="smtp.gmail.com">
                                <small class="text-muted">Ejemplo: smtp.gmail.com, smtp.office365.com, mail.sudominio.cl</small>
                            </div>
                            <div class="col-md-4">
                                <label for="smtp_port" class="form-label">Puerto <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                       value="<?= htmlspecialchars($config_smtp['smtp_port']) ?>" required
                                       min="1" max="65535">
                                <small class="text-muted">TLS: 587 | SSL: 465</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="smtp_encriptacion" class="form-label">Tipo de Encriptación</label>
                            <select class="form-select" id="smtp_encriptacion" name="smtp_encriptacion">
                                <option value="tls" <?= $config_smtp['smtp_encriptacion'] === 'tls' ? 'selected' : '' ?>>TLS (recomendado para puerto 587)</option>
                                <option value="ssl" <?= $config_smtp['smtp_encriptacion'] === 'ssl' ? 'selected' : '' ?>>SSL (puerto 465)</option>
                                <option value="none" <?= $config_smtp['smtp_encriptacion'] === 'none' ? 'selected' : '' ?>>Sin encriptación (no recomendado)</option>
                            </select>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3"><i class="bi bi-person-lock"></i> Credenciales de Autenticación</h6>

                        <div class="mb-3">
                            <label for="smtp_usuario" class="form-label">Usuario SMTP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="smtp_usuario" name="smtp_usuario" 
                                   value="<?= htmlspecialchars($config_smtp['smtp_usuario']) ?>" required
                                   placeholder="correo@muniloslagos.cl">
                            <small class="text-muted">Generalmente es una dirección de correo electrónico</small>
                        </div>

                        <div class="mb-3">
                            <label for="smtp_password" class="form-label">Contraseña SMTP <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                       value="<?= htmlspecialchars($config_smtp['smtp_password']) ?>"
                                       placeholder="Contraseña o App Password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-shield-lock"></i>
                                Para Gmail, use una <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> en lugar de su contraseña normal
                            </small>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3"><i class="bi bi-envelope"></i> Información del Remitente</h6>

                        <div class="mb-3">
                            <label for="smtp_de_correo" class="form-label">Correo del Remitente <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="smtp_de_correo" name="smtp_de_correo" 
                                   value="<?= htmlspecialchars($config_smtp['smtp_de_correo']) ?>" required
                                   placeholder="transparencia@muniloslagos.cl">
                            <small class="text-muted">Este correo aparecerá como remitente en las notificaciones</small>
                        </div>

                        <div class="mb-3">
                            <label for="smtp_de_nombre" class="form-label">Nombre del Remitente</label>
                            <input type="text" class="form-control" id="smtp_de_nombre" name="smtp_de_nombre" 
                                   value="<?= htmlspecialchars($config_smtp['smtp_de_nombre']) ?>"
                                   placeholder="Sistema de Transparencia Activa">
                            <small class="text-muted">Nombre descriptivo que verán los destinatarios</small>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="smtp_activo" name="smtp_activo" 
                                       <?= $config_smtp['smtp_activo'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="smtp_activo">
                                    <strong>Activar envío de correos automáticos</strong>
                                </label>
                                <div class="form-text">
                                    Si está desactivado, no se enviarán notificaciones por correo electrónico
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="guardar_smtp" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Estado de la configuración -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Estado</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Sistema de correos:</strong><br>
                        <?php if ($config_smtp['smtp_activo']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Desactivado</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <strong>Verificación:</strong><br>
                        <?php if ($config_smtp['smtp_verificado']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Verificado</span>
                        <?php else: ?>
                            <span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Sin verificar</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($config_smtp['fecha_modificacion'])): ?>
                    <div class="mb-0">
                        <strong>Última modificación:</strong><br>
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($config_smtp['fecha_modificacion'])) ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Prueba de conexión -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-send-check"></i> Probar Conexión</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Importante:</strong> Guarde la configuración arriba primero, luego envíe una prueba aquí.
                    </div>
                    <p class="mb-3">Envíe un correo de prueba para verificar que la configuración guardada funciona correctamente.</p>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="correo_prueba" class="form-label">Correo de destino:</label>
                            <input type="email" class="form-control" id="correo_prueba" name="correo_prueba" 
                                   placeholder="correo@ejemplo.cl" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="probar_smtp" class="btn btn-success">
                                <i class="bi bi-send"></i> Enviar Prueba
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Guía rápida -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-book"></i> Guía Rápida</h5>
                </div>
                <div class="card-body">
                    <h6>Gmail / Google Workspace:</h6>
                    <ul class="small">
                        <li>Servidor: smtp.gmail.com</li>
                        <li>Puerto: 587 (TLS)</li>
                        <li>Use una <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a></li>
                    </ul>
                    <h6>Outlook / Office 365:</h6>
                    <ul class="small">
                        <li>Servidor: smtp.office365.com</li>
                        <li>Puerto: 587 (TLS)</li>
                        <li>Usuario: correo completo</li>
                    </ul>
                    <h6>Servidor propio:</h6>
                    <ul class="small">
                        <li>Consulte con su proveedor</li>
                        <li>Puertos comunes: 25, 465, 587</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('smtp_password');
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
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
