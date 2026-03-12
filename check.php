<?php
/**
 * Script para validar la instalación del sistema
 * Acceder en: http://localhost/cumplimiento/check.php
 */

require_once __DIR__ . '/config/config.php';

$checks = [
    'PHP Version' => [
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'required' => 'PHP 7.4+',
        'current' => PHP_VERSION
    ],
    'MySQL Support' => [
        'status' => extension_loaded('mysqli'),
        'required' => 'MySQLi Extension',
        'current' => extension_loaded('mysqli') ? 'Installed' : 'Not Found'
    ],
    'File Upload' => [
        'status' => ini_get('file_uploads'),
        'required' => 'Enabled',
        'current' => ini_get('file_uploads') ? 'Enabled' : 'Disabled'
    ],
    'Max Upload Size' => [
        'status' => ini_get('upload_max_filesize') >= 10,
        'required' => '10MB+',
        'current' => ini_get('upload_max_filesize')
    ],
    'Session Support' => [
        'status' => extension_loaded('session'),
        'required' => 'Session Extension',
        'current' => extension_loaded('session') ? 'Installed' : 'Not Found'
    ],
    'Uploads Folder' => [
        'status' => is_writable('uploads'),
        'required' => 'Writable',
        'current' => is_writable('uploads') ? 'Writable' : 'Not Writable'
    ]
];

// Intentar conectar a BD
try {
    $test_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($test_conn->connect_error) {
        $checks['Database Connection'] = [
            'status' => false,
            'required' => 'Connected',
            'current' => 'Error: ' . $test_conn->connect_error
        ];
    } else {
        $checks['Database Connection'] = [
            'status' => true,
            'required' => 'Connected',
            'current' => 'Connected to ' . DB_NAME
        ];
        $test_conn->close();
    }
} catch (Exception $e) {
    $checks['Database Connection'] = [
        'status' => false,
        'required' => 'Connected',
        'current' => 'Error: ' . $e->getMessage()
    ];
}

$all_ok = true;
foreach ($checks as $check) {
    if (!$check['status']) $all_ok = false;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .check-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-item.ok {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .check-item.fail {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .icon {
            font-size: 1.5rem;
            margin-right: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-ok {
            background-color: #28a745;
            color: white;
        }
        .status-fail {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="check-card" style="max-width: 600px;">
        <div class="text-center mb-4">
            <h1><i class="bi bi-shield-check" style="color: #3498db;"></i></h1>
            <h2>Verificación del Sistema</h2>
        </div>

        <?php if ($all_ok): ?>
            <div class="alert alert-success text-center mb-4">
                <i class="bi bi-check-circle"></i> 
                <strong>Todos los requisitos están cumplidos</strong>
            </div>
        <?php else: ?>
            <div class="alert alert-danger text-center mb-4">
                <i class="bi bi-exclamation-circle"></i> 
                <strong>Algunas verificaciones fallaron</strong>
            </div>
        <?php endif; ?>

        <div class="checks">
            <?php foreach ($checks as $name => $check): ?>
                <div class="check-item <?php echo $check['status'] ? 'ok' : 'fail'; ?>">
                    <div>
                        <div class="d-flex align-items-center">
                            <span class="icon">
                                <?php echo $check['status'] ? '✓' : '✗'; ?>
                            </span>
                            <div>
                                <strong><?php echo htmlspecialchars($name); ?></strong>
                                <p class="mb-0 text-muted small">Requerido: <?php echo htmlspecialchars($check['required']); ?></p>
                            </div>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $check['status'] ? 'status-ok' : 'status-fail'; ?>">
                        <?php echo htmlspecialchars($check['current']); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 pt-4 border-top text-center">
            <?php if ($all_ok): ?>
                <a href="setup.php" class="btn btn-success btn-lg">
                    <i class="bi bi-play-circle"></i> Ir a Configurar Base de Datos
                </a>
            <?php else: ?>
                <p class="text-danger">Por favor, resuelva los problemas anteriores antes de continuar.</p>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="index.php" class="btn btn-outline-secondary">Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>
