<?php
/**
 * Test de envío de correo con logs detallados
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'classes/EmailSender.php';

echo "<h2>Diagnóstico de Envío de Correo</h2>";
echo "<pre>";

// 1. Verificar PHPMailer
echo "1. Verificando PHPMailer...\n";
$phpmailer_path = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
if (file_exists($phpmailer_path)) {
    echo "   ✓ PHPMailer encontrado en: $phpmailer_path\n";
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
    echo "   ✓ Clases cargadas correctamente\n";
} else {
    echo "   ✗ PHPMailer NO encontrado\n";
    exit;
}

// 2. Verificar configuración SMTP
echo "\n2. Verificando configuración SMTP...\n";
$result = $db->getConnection()->query("SELECT * FROM configuracion_smtp WHERE smtp_activo = 1 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $config = $result->fetch_assoc();
    echo "   ✓ Host: " . $config['smtp_host'] . "\n";
    echo "   ✓ Port: " . $config['smtp_port'] . "\n";
    echo "   ✓ User: " . $config['smtp_usuario'] . "\n";
    echo "   ✓ From: " . $config['smtp_de_correo'] . "\n";
    echo "   ✓ Encryption: " . $config['smtp_encriptacion'] . "\n";
} else {
    echo "   ✗ No hay configuración SMTP activa\n";
    exit;
}

// 3. Test de envío real con logs
echo "\n3. Enviando correo de prueba...\n";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    // Habilitar debug verbose
    $mail->SMTPDebug = 2; // 0=off, 1=client, 2=client+server
    $mail->Debugoutput = function($str, $level) {
        echo "   DEBUG: $str\n";
    };
    
    // Configuración del servidor
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_usuario'];
    $mail->Password = $config['smtp_password'];
    $mail->Port = $config['smtp_port'];
    
    if ($config['smtp_encriptacion'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($config['smtp_encriptacion'] === 'tls') {
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }
    
    $mail->CharSet = 'UTF-8';
    
    // Remitente y destinatario
    $mail->setFrom($config['smtp_de_correo'], $config['smtp_de_nombre']);
    
    // CAMBIA ESTE EMAIL POR UNO TUYO PARA PROBAR
    $email_prueba = 'TU_EMAIL@gmail.com'; // <--- CAMBIA ESTO
    $mail->addAddress($email_prueba, 'Usuario de Prueba');
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Test de Correo - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h2>Correo de Prueba</h2><p>Este es un correo de prueba del sistema de Transparencia Activa.</p><p>Si recibes este correo, el sistema está funcionando correctamente.</p>';
    
    // Enviar
    if ($mail->send()) {
        echo "\n✓ ¡Correo enviado exitosamente!\n";
        echo "   Revisa la bandeja de entrada de: $email_prueba\n";
    } else {
        echo "\n✗ Error al enviar: " . $mail->ErrorInfo . "\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ Excepción capturada: {$mail->ErrorInfo}\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
