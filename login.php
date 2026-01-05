<?php
// Iniciar sesión y cargar configuración PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

// Inicializar base de datos
$db = new Database();

// Verificar si el usuario está autenticado
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $is_logged_in && isset($_SESSION['user']) ? $_SESSION['user'] : null;
$current_profile = $is_logged_in && isset($_SESSION['profile']) ? $_SESSION['profile'] : null;

// SI YA ESTÁ AUTENTICADO, REDIRIGIR ANTES DE CUALQUIER SALIDA
if ($is_logged_in && $current_profile) {
    if ($current_profile === 'administrativo') {
        header('Location: ' . SITE_URL . 'admin/index.php');
    } elseif ($current_profile === 'publicador') {
        header('Location: ' . SITE_URL . 'admin/publicador/');
    } else {
        header('Location: ' . SITE_URL . 'usuario/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        require_once __DIR__ . '/classes/Usuario.php';
        $usuarioClass = new Usuario($db->getConnection());
        $user = $usuarioClass->authenticate($email, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'perfil' => $user['perfil']
            ];
            $_SESSION['profile'] = $user['perfil'];

            // Registrar en logs
            $action = "Inicio de sesión";
            $ip = $_SERVER['REMOTE_ADDR'];
            $conn = $db->getConnection();
            $sql = "INSERT INTO logs (usuario_id, accion, ip_address) VALUES ({$user['id']}, '$action', '$ip')";
            $conn->query($sql);

            if ($user['perfil'] === 'administrativo') {
                header('Location: ' . SITE_URL . 'admin/index.php');
            } elseif ($user['perfil'] === 'publicador') {
                header('Location: ' . SITE_URL . 'admin/publicador/');
            } else {
                header('Location: ' . SITE_URL . 'usuario/dashboard.php');
            }
            exit;
        } else {
            $error = 'Email o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor, complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header text-center">
                        <i class="bi bi-shield-check" style="font-size: 2rem; color: white;"></i>
                        <h2 class="mt-2"><?php echo SITE_NAME; ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </button>
                        </form>

                        <hr>

                        <div class="alert alert-info">
                            <strong>Nota:</strong> Si es la primera vez, acceda a <a href="setup.php">setup.php</a> para crear la base de datos.
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted">&copy; 2025 Sistema de Transparencia Activa</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
