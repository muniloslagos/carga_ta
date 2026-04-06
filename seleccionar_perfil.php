<?php
// Selección de perfil cuando el usuario tiene múltiples perfiles asignados
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Usuario.php';

$db = new Database();
$usuarioClass = new Usuario($db->getConnection());

// Si el usuario ya está autenticado y accede desde el menú, convertir a temp_user_id
if (isset($_SESSION['user_id']) && !isset($_SESSION['temp_user_id'])) {
    $_SESSION['temp_user_id'] = $_SESSION['user_id'];
    // Limpiar sesión actual pero mantener temp_user_id
    $temp_id = $_SESSION['temp_user_id'];
    session_destroy();
    session_start();
    $_SESSION['temp_user_id'] = $temp_id;
}

// Verificar que hay un usuario autenticado temporalmente
if (!isset($_SESSION['temp_user_id'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// Obtener información del usuario
$user = $usuarioClass->getById($_SESSION['temp_user_id']);
if (!$user) {
    session_destroy();
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// Obtener perfiles asignados
$perfiles = $usuarioClass->getPerfiles($user['id']);

// Si solo tiene un perfil, redirigir directamente
if (count($perfiles) <= 1) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nombre' => $user['nombre'],
        'email' => $user['email'],
        'perfil' => $perfiles[0] ?? $user['perfil']
    ];
    $_SESSION['profile'] = $perfiles[0] ?? $user['perfil'];
    unset($_SESSION['temp_user_id']);
    
    if ($_SESSION['profile'] === 'administrativo') {
        header('Location: ' . SITE_URL . 'admin/index.php');
    } else {
        header('Location: ' . SITE_URL . 'usuario/dashboard.php');
    }
    exit;
}

// Procesar selección de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perfil_seleccionado'])) {
    $perfil_seleccionado = $_POST['perfil_seleccionado'];
    
    // Verificar que el perfil esté asignado al usuario
    if (in_array($perfil_seleccionado, $perfiles)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'perfil' => $perfil_seleccionado
        ];
        $_SESSION['profile'] = $perfil_seleccionado;
        unset($_SESSION['temp_user_id']);
        
        // Registrar en logs
        $action = "Selección de perfil: $perfil_seleccionado";
        $ip = $_SERVER['REMOTE_ADDR'];
        $conn = $db->getConnection();
        $sql = "INSERT INTO logs (usuario_id, accion, ip_address) VALUES ({$user['id']}, '$action', '$ip')";
        $conn->query($sql);
        
        if ($perfil_seleccionado === 'administrativo') {
            header('Location: ' . SITE_URL . 'admin/index.php');
        } else {
            header('Location: ' . SITE_URL . 'usuario/dashboard.php');
        }
        exit;
    }
}

// Nombres amigables de perfiles
$nombres_perfiles = [
    'administrativo' => 'Administrativo',
    'director_revisor' => 'Director / Revisor',
    'cargador_informacion' => 'Cargador de Información',
    'publicador' => 'Publicador',
    'auditor' => 'Auditor'
];

// Iconos de perfiles
$iconos_perfiles = [
    'administrativo' => 'bi-gear-fill',
    'director_revisor' => 'bi-person-check-fill',
    'cargador_informacion' => 'bi-upload',
    'publicador' => 'bi-megaphone-fill',
    'auditor' => 'bi-shield-check'
];

// Descripciones de perfiles
$descripciones_perfiles = [
    'administrativo' => 'Gestión completa del sistema',
    'director_revisor' => 'Revisión y aprobación de documentos',
    'cargador_informacion' => 'Carga de documentos asignados',
    'publicador' => 'Publicación en portal de transparencia',
    'auditor' => 'Auditoría y verificación de cumplimiento'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Perfil - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>css/style.css" rel="stylesheet">
    <style>
        .perfil-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .perfil-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-color: #0d6efd;
        }
        .perfil-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg">
                    <div class="card-header text-center" style="padding: 30px 20px;">
                        <img src="https://muniloslagos.cl/wp-content/uploads/2025/02/logo2025.png" alt="Logo Municipalidad" style="max-width: 150px; margin-bottom: 20px;">
                        <h3>Bienvenido/a, <?php echo htmlspecialchars($user['nombre']); ?></h3>
                        <p class="mb-0">Tienes múltiples perfiles asignados. Selecciona con cuál deseas ingresar:</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="formSeleccionPerfil">
                            <div class="row g-3">
                                <?php foreach ($perfiles as $perfil): ?>
                                    <div class="col-md-6">
                                        <div class="card perfil-card h-100" onclick="seleccionarPerfil('<?php echo $perfil; ?>')">
                                            <div class="card-body text-center p-4">
                                                <i class="bi <?php echo $iconos_perfiles[$perfil]; ?> perfil-icon text-primary"></i>
                                                <h4><?php echo $nombres_perfiles[$perfil]; ?></h4>
                                                <p class="text-muted mb-0">
                                                    <?php echo $descripciones_perfiles[$perfil]; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="perfil_seleccionado" id="perfil_seleccionado">
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="<?php echo SITE_URL; ?>logout.php" class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Podrás cambiar de perfil en cualquier momento desde el menú superior
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function seleccionarPerfil(perfil) {
            document.getElementById('perfil_seleccionado').value = perfil;
            document.getElementById('formSeleccionPerfil').submit();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
