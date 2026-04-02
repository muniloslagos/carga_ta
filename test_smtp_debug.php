<?php
/**
 * Script de diagnóstico detallado SMTP
 * Muestra los errores reales de PHPMailer
 */

require_once 'config/config.php';

$conn = $db->getConnection();

echo "<h2>Diagnóstico SMTP Detallado</h2>";

// 1. Verificar configuración en BD
echo "<h3>1. Configuración en Base de Datos:</h3>";
$config = $conn->query("SELECT * FROM configuracion_smtp ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($config) {
    echo "<pre>";
    echo "smtp_host: " . htmlspecialchars($config['smtp_host']) . "\n";
    echo "smtp_port: " . htmlspecialchars($config['smtp_port']) . "\n";
    echo "smtp_usuario: " . htmlspecialchars($config['smtp_usuario']) . "\n";
    echo "smtp_password: " . (empty($config['smtp_password']) ? '(vacía)' : '******') . "\n";
    echo "smtp_encriptacion: " . htmlspecialchars($config['smtp_encriptacion']) . "\n";
    echo "smtp_de_correo: " . htmlspecialchars($config['smtp_de_correo']) . "\n";
    echo "smtp_de_nombre: " . htmlspecialchars($config['smtp_de_nombre']) . "\n";
    echo "smtp_activo: " . ($config['smtp_activo'] ? 'SÍ ✓' : 'NO ✗') . "\n";
    echo "smtp_verificado: " . ($config['smtp_verificado'] ? 'SÍ ✓' : 'NO ✗') . "\n";
    echo "</pre>";
} else {
    echo "<p style='color:red;'>No hay configuración SMTP en la base de datos</p>";
    die();
}

// 2. Verificar PHPMailer
echo "<h3>2. Verificar PHPMailer:</h3>";
if (file_exists(__DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    echo "<p style='color:green;'>✓ PHPMailer encontrado</p>";
    
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
    
    // 3. Intentar envío con debug activado
    echo "<h3>3. Prueba de Envío (con debug):</h3>";
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Activar debug verbose
        $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client+server
        $mail->Debugoutput = function($str, $level) {
            echo htmlspecialchars($str) . "<br>";
        };
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_usuario'];
        $mail->Password = $config['smtp_password'];
        $mail->Port = $config['smtp_port'];
        
        // Configurar encriptación
        if ($config['smtp_encriptacion'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($config['smtp_encriptacion'] === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        // Timeouts
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Configuración de caracteres
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Remitente
        $mail->setFrom($config['smtp_de_correo'], $config['smtp_de_nombre']);
        
        // Destinatario - CAMBIA ESTO A TU CORREO
        $mail->addAddress('juanand87@gmail.com', 'Prueba');
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Prueba Debug SMTP - ' . date('Y-m-d H:i:s');
        $mail->Body = '<h2>Prueba de Configuración SMTP</h2><p>Este correo se envió con debug activado.</p><p>Fecha: ' . date('d/m/Y H:i:s') . '</p>';
        $mail->AltBody = 'Prueba SMTP';
        
        echo "<div style='background:#f0f0f0; padding:10px; margin:10px 0; border:1px solid #ccc;'>";
        echo "<strong>Intentando enviar correo...</strong><br><br>";
        
        $mail->send();
        
        echo "</div>";
        echo "<h3 style='color:green;'>✓ ¡Correo enviado exitosamente!</h3>";
        echo "<p>Revisa tu bandeja de entrada y spam en juanand87@gmail.com</p>";
        
    } catch (Exception $e) {
        echo "</div>";
        echo "<h3 style='color:red;'>✗ Error al enviar:</h3>";
        echo "<pre style='background:#ffe6e6; padding:10px; border:1px solid #cc0000;'>";
        echo "Mensaje: " . $e->getMessage() . "\n";
        echo "ErrorInfo: " . $mail->ErrorInfo . "\n";
        echo "</pre>";
    }
    
} else {
    echo "<p style='color:red;'>✗ PHPMailer NO encontrado en vendor/phpmailer/phpmailer/src/</p>";
}

echo "<hr><p><small>Fecha: " . date('Y-m-d H:i:s') . "</small></p>";
?>
