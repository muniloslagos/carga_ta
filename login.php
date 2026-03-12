<?php
/**
 * Login - Sistema de Numeración
 * Municipalidad de Los Lagos
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/usuarios.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    switch ($_SESSION['user_rol']) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/');
            break;
        case 'girador':
            header('Location: ' . BASE_URL . '/girador/');
            break;
        case 'emisor':
            header('Location: ' . BASE_URL . '/emisor/');
            break;
    }
    exit;
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Complete todos los campos';
    } else {
        $result = autenticar($username, $password);
        if ($result['success']) {
            switch ($_SESSION['user_rol']) {
                case 'admin':
                    header('Location: ' . BASE_URL . '/admin/');
                    break;
                case 'girador':
                    header('Location: ' . BASE_URL . '/girador/');
                    break;
                case 'emisor':
                    header('Location: ' . BASE_URL . '/emisor/');
                    break;
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Mensaje de error por query string
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'No tiene permisos para acceder a esa sección';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Numeración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 60px;
            color: #1e3c72;
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-title h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 5px;
        }
        .login-title p {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-building"></i>
        </div>
        <div class="login-title">
            <h1>Sistema de Numeración</h1>
            <p>Municipalidad de Los Lagos</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Ingrese su usuario" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Ingrese su contraseña" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="<?= BASE_URL ?>/pantalla/" class="text-decoration-none text-muted">
                <i class="bi bi-display me-1"></i>Ver Pantalla Pública
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
