<?php
/**
 * DIAGNÓSTICO DE CAPACIDADES DE CORREO ELECTRÓNICO
 * Sistema de Transparencia Activa - Municipalidad de Los Lagos
 * 
 * Instrucciones de uso:
 * 1. Subir este archivo a: /var/www/html/app.muniloslagos.cl/carga_ta/
 * 2. Acceder desde navegador: https://app.muniloslagos.cl/carga_ta/diagnostico_correo.php
 * 3. Revisar resultados y recomendaciones
 * 4. ELIMINAR este archivo después de usarlo por seguridad
 */

// Configuración de seguridad - Comentar o eliminar en producción
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$diagnostico = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible',
    'server_os' => PHP_OS,
    'fecha_diagnostico' => date('Y-m-d H:i:s'),
    'checks' => []
];

/**
 * Función auxiliar para agregar resultados
 */
function addCheck($nombre, $estado, $mensaje, $detalles = '') {
    global $diagnostico;
    $diagnostico['checks'][] = [
        'nombre' => $nombre,
        'estado' => $estado,
        'mensaje' => $mensaje,
        'detalles' => $detalles
    ];
}

// ====== VERIFICACIÓN 1: Función mail() de PHP ======
if (function_exists('mail')) {
    addCheck(
        'Función mail()',
        'success',
        'La función mail() de PHP está disponible',
        'Puedes usar la función nativa de PHP para enviar correos, aunque puede tener limitaciones de deliverability.'
    );
} else {
    addCheck(
        'Función mail()',
        'error',
        'La función mail() de PHP NO está disponible',
        'El servidor tiene deshabilitada la función mail(). Deberás usar SMTP con PHPMailer.'
    );
}

// ====== VERIFICACIÓN 2: Extensiones PHP necesarias para SMTP ======
$extensiones_requeridas = ['openssl', 'sockets', 'mbstring', 'iconv'];
$extensiones_faltantes = [];

foreach ($extensiones_requeridas as $ext) {
    if (extension_loaded($ext)) {
        addCheck(
            "Extensión PHP: $ext",
            'success',
            "Extensión $ext está instalada",
            ''
        );
    } else {
        $extensiones_faltantes[] = $ext;
        addCheck(
            "Extensión PHP: $ext",
            'warning',
            "Extensión $ext NO está instalada",
            "Esta extensión es necesaria para usar SMTP con encriptación TLS/SSL."
        );
    }
}

// ====== VERIFICACIÓN 3: PHPMailer instalado ======
$phpmailer_paths = [
    __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
    __DIR__ . '/../vendor/autoload.php'
];

$phpmailer_encontrado = false;
$phpmailer_path = '';

foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        $phpmailer_encontrado = true;
        $phpmailer_path = $path;
        break;
    }
}

if ($phpmailer_encontrado) {
    addCheck(
        'PHPMailer',
        'success',
        'PHPMailer está instalado',
        "Ruta: $phpmailer_path"
    );
} else {
    addCheck(
        'PHPMailer',
        'error',
        'PHPMailer NO está instalado',
        'Necesitas instalar PHPMailer vía Composer: composer require phpmailer/phpmailer'
    );
}

// ====== VERIFICACIÓN 4: Composer disponible ======
exec('composer --version 2>&1', $composer_output, $composer_return);
if ($composer_return === 0) {
    addCheck(
        'Composer',
        'success',
        'Composer está instalado',
        implode("\n", $composer_output)
    );
} else {
    addCheck(
        'Composer',
        'warning',
        'Composer no está disponible o no está en PATH',
        'Puedes instalar PHPMailer manualmente si Composer no está disponible.'
    );
}

// ====== VERIFICACIÓN 5: Configuración de sendmail_path ======
$sendmail_path = ini_get('sendmail_path');
if (!empty($sendmail_path)) {
    addCheck(
        'Sendmail Path',
        'info',
        'Sendmail configurado en PHP',
        "Ruta: $sendmail_path"
    );
} else {
    addCheck(
        'Sendmail Path',
        'info',
        'Sendmail path no configurado',
        'Esto es normal en servidores que usan SMTP en lugar de sendmail local.'
    );
}

// ====== VERIFICACIÓN 6: Verificar binarios de correo en servidor ======
$mail_binaries = ['sendmail', 'postfix', 'exim', 'exim4'];
foreach ($mail_binaries as $binary) {
    exec("which $binary 2>&1", $output, $return_code);
    if ($return_code === 0 && !empty($output)) {
        addCheck(
            "Binario: $binary",
            'success',
            "$binary encontrado en el servidor",
            implode("\n", $output)
        );
    }
}

// ====== VERIFICACIÓN 7: Permisos de escritura en directorio actual ======
if (is_writable(__DIR__)) {
    addCheck(
        'Permisos de escritura',
        'success',
        'El directorio actual tiene permisos de escritura',
        'Composer podrá instalar PHPMailer sin problemas.'
    );
} else {
    addCheck(
        'Permisos de escritura',
        'warning',
        'El directorio actual NO tiene permisos de escritura',
        'Puede haber problemas al instalar dependencias. Verifica permisos.'
    );
}

// ====== VERIFICACIÓN 8: Límites de PHP ======
addCheck(
    'Memory Limit',
    'info',
    'Límite de memoria PHP: ' . ini_get('memory_limit'),
    'Suficiente para operaciones de correo normales.'
);

addCheck(
    'Max Execution Time',
    'info',
    'Tiempo máximo de ejecución: ' . ini_get('max_execution_time') . ' segundos',
    'Tiempo suficiente para enviar correos vía SMTP.'
);

// ====== GENERACIÓN DE RECOMENDACIONES ======
$recomendaciones = [];

if (!function_exists('mail') && !$phpmailer_encontrado) {
    $recomendaciones[] = [
        'tipo' => 'critico',
        'mensaje' => 'ACCIÓN REQUERIDA: Instalar PHPMailer',
        'pasos' => [
            'Acceder al servidor vía SSH',
            'cd /var/www/html/app.muniloslagos.cl/carga_ta',
            'composer require phpmailer/phpmailer',
            'Si Composer no está disponible, descargar manualmente desde GitHub'
        ]
    ];
} elseif (!$phpmailer_encontrado) {
    $recomendaciones[] = [
        'tipo' => 'importante',
        'mensaje' => 'RECOMENDACIÓN: Instalar PHPMailer',
        'pasos' => [
            'Aunque mail() está disponible, PHPMailer ofrece mejor control y deliverability',
            'composer require phpmailer/phpmailer',
            'Configurar SMTP con cuenta de correo institucional'
        ]
    ];
}

if (!empty($extensiones_faltantes)) {
    $recomendaciones[] = [
        'tipo' => 'importante',
        'mensaje' => 'Extensiones PHP faltantes: ' . implode(', ', $extensiones_faltantes),
        'pasos' => [
            'Contactar al administrador del servidor',
            'Solicitar instalación de extensiones: ' . implode(', ', $extensiones_faltantes),
            'O usar configuración SMTP sin TLS (menos seguro)'
        ]
    ];
}

if ($phpmailer_encontrado) {
    $recomendaciones[] = [
        'tipo' => 'exito',
        'mensaje' => '✓ Sistema listo para envío de correos',
        'pasos' => [
            'Configurar SMTP en admin/smtp/ del sistema',
            'Usar cuenta de correo institucional (transparencia@muniloslagos.cl)',
            'Configurar servidor SMTP de cPanel (generalmente mail.muniloslagos.cl)',
            'Probar envío de correo de prueba desde el sistema'
        ]
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Correo Electrónico - Sistema TA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-item strong {
            display: block;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-item span {
            color: #212529;
            font-size: 16px;
            font-weight: 500;
        }
        
        .checks-section {
            padding: 30px;
        }
        
        .section-title {
            font-size: 22px;
            color: #212529;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .check-item {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 4px;
            transition: transform 0.2s;
        }
        
        .check-item:hover {
            transform: translateX(5px);
        }
        
        .check-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .check-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .check-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .check-item.info {
            border-left-color: #17a2b8;
            background: #d1ecf1;
        }
        
        .check-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .check-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .success .check-icon {
            background: #28a745;
            color: white;
        }
        
        .error .check-icon {
            background: #dc3545;
            color: white;
        }
        
        .warning .check-icon {
            background: #ffc107;
            color: #212529;
        }
        
        .info .check-icon {
            background: #17a2b8;
            color: white;
        }
        
        .check-name {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
        }
        
        .check-message {
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .check-details {
            font-size: 12px;
            color: #6c757d;
            font-family: 'Courier New', monospace;
            background: rgba(0,0,0,0.05);
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .recommendations {
            padding: 30px;
            background: #f8f9fa;
        }
        
        .recommendation {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #6c757d;
        }
        
        .recommendation.critico {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .recommendation.importante {
            border-left-color: #ffc107;
            background: #fffef5;
        }
        
        .recommendation.exito {
            border-left-color: #28a745;
            background: #f5fff8;
        }
        
        .recommendation h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #212529;
        }
        
        .recommendation ol {
            margin-left: 20px;
        }
        
        .recommendation li {
            margin-bottom: 8px;
            color: #495057;
            line-height: 1.6;
        }
        
        .footer {
            background: #212529;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .footer strong {
            color: #ffc107;
        }
        
        .alert-security {
            background: #dc3545;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert-security">
            ⚠️ IMPORTANTE: Eliminar este archivo después de usarlo por razones de seguridad
        </div>
        
        <div class="header">
            <h1>🔍 Diagnóstico de Capacidades de Correo</h1>
            <p>Sistema de Transparencia Activa - Municipalidad de Los Lagos</p>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <strong>Versión PHP</strong>
                <span><?php echo $diagnostico['php_version']; ?></span>
            </div>
            <div class="info-item">
                <strong>Servidor Web</strong>
                <span><?php echo $diagnostico['server_software']; ?></span>
            </div>
            <div class="info-item">
                <strong>Sistema Operativo</strong>
                <span><?php echo $diagnostico['server_os']; ?></span>
            </div>
            <div class="info-item">
                <strong>Fecha Diagnóstico</strong>
                <span><?php echo $diagnostico['fecha_diagnostico']; ?></span>
            </div>
        </div>
        
        <div class="checks-section">
            <h2 class="section-title">📋 Resultados de Verificación</h2>
            
            <?php foreach ($diagnostico['checks'] as $check): ?>
                <div class="check-item <?php echo $check['estado']; ?>">
                    <div class="check-header">
                        <div class="check-icon">
                            <?php 
                            switch($check['estado']) {
                                case 'success': echo '✓'; break;
                                case 'error': echo '✗'; break;
                                case 'warning': echo '!'; break;
                                case 'info': echo 'i'; break;
                            }
                            ?>
                        </div>
                        <div class="check-name"><?php echo htmlspecialchars($check['nombre']); ?></div>
                    </div>
                    <div class="check-message"><?php echo htmlspecialchars($check['mensaje']); ?></div>
                    <?php if (!empty($check['detalles'])): ?>
                        <div class="check-details"><?php echo htmlspecialchars($check['detalles']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($recomendaciones)): ?>
            <div class="recommendations">
                <h2 class="section-title">💡 Recomendaciones</h2>
                
                <?php foreach ($recomendaciones as $rec): ?>
                    <div class="recommendation <?php echo $rec['tipo']; ?>">
                        <h3><?php echo htmlspecialchars($rec['mensaje']); ?></h3>
                        <ol>
                            <?php foreach ($rec['pasos'] as $paso): ?>
                                <li><?php echo htmlspecialchars($paso); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Diagnóstico generado automáticamente • <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Recuerda eliminar este archivo después de revisarlo</strong></p>
        </div>
    </div>
</body>
</html>
