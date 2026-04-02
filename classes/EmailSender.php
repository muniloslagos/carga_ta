<?php
/**
 * Clase para envío de correos electrónicos
 * Sistema de Transparencia Activa - Municipalidad de Los Lagos
 */

require_once __DIR__ . '/../config/config.php';

class EmailSender {
    private $conn;
    private $config;
    private $error;
    
    public function __construct() {
        global $db;
        $this->conn = $db->getConnection();
        $this->cargarConfiguracion();
    }
    
    /**
     * Cargar configuración SMTP desde la base de datos
     */
    private function cargarConfiguracion() {
        $result = $this->conn->query("SELECT * FROM configuracion_smtp WHERE smtp_activo = 1 ORDER BY id DESC LIMIT 1");
        
        if ($result && $result->num_rows > 0) {
            $this->config = $result->fetch_assoc();
        } else {
            $this->config = null;
        }
    }
    
    /**
     * Verificar si el sistema de correos está activo
     */
    public function estaActivo() {
        return $this->config !== null && $this->config['smtp_activo'] == 1;
    }
    
    /**
     * Enviar correo electrónico
     * 
     * @param string $destino Correo electrónico del destinatario
     * @param string $asunto Asunto del correo
     * @param string $cuerpo Cuerpo del correo en HTML
     * @param string $destinoNombre Nombre del destinatario (opcional)
     * @return bool True si se envió correctamente, False si hubo error
     */
    public function enviarCorreo($destino, $asunto, $cuerpo, $destinoNombre = '') {
        if (!$this->estaActivo()) {
            $this->error = 'El sistema de correos no está activo';
            return false;
        }
        
        try {
            // Intentar cargar PHPMailer
            $phpmailer_path = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            
            if (file_exists($phpmailer_path)) {
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
                
                return $this->enviarConPHPMailer($destino, $asunto, $cuerpo, $destinoNombre);
            } else {
                // Fallback: usar función mail() de PHP
                return $this->enviarConMail($destino, $asunto, $cuerpo, $destinoNombre);
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Enviar correo usando PHPMailer
     */
    private function enviarConPHPMailer($destino, $asunto, $cuerpo, $destinoNombre) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_usuario'];
            $mail->Password = $this->config['smtp_password'];
            $mail->Port = $this->config['smtp_port'];
            
            // Configurar encriptación
            if ($this->config['smtp_encriptacion'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($this->config['smtp_encriptacion'] === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            // Configuración de caracteres
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Remitente
            $mail->setFrom($this->config['smtp_de_correo'], $this->config['smtp_de_nombre']);
            $mail->addReplyTo($this->config['smtp_de_correo'], $this->config['smtp_de_nombre']);
            
            // Destinatario
            if (!empty($destinoNombre)) {
                $mail->addAddress($destino, $destinoNombre);
            } else {
                $mail->addAddress($destino);
            }
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $this->generarPlantillaHTML($cuerpo);
            $mail->AltBody = strip_tags($cuerpo);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            $this->error = "Error PHPMailer: {$mail->ErrorInfo}";
            return false;
        }
    }
    
    /**
     * Enviar correo usando la función mail() de PHP
     * Requiere que el servidor tenga configurado sendmail o SMTP
     */
    private function enviarConMail($destino, $asunto, $cuerpo, $destinoNombre) {
        // Configurar encabezados
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . $this->config['smtp_de_nombre'] . ' <' . $this->config['smtp_de_correo'] . '>';
        $headers[] = 'Reply-To: ' . $this->config['smtp_de_correo'];
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        // Preparar el cuerpo HTML
        $cuerpoCompleto = $this->generarPlantillaHTML($cuerpo);
        
        // Enviar
        $resultado = mail($destino, $asunto, $cuerpoCompleto, implode("\r\n", $headers));
        
        if (!$resultado) {
            $this->error = 'Error al enviar el correo con la función mail()';
            return false;
        }
        
        return true;
    }
    
    /**
     * Generar plantilla HTML para el correo
     */
    private function generarPlantillaHTML($contenido) {
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border: 1px solid #dee2e6;
        }
        .footer {
            background-color: #e9ecef;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-left: 4px solid #0dcaf0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin: 0;">Sistema de Transparencia Activa</h2>
        <p style="margin: 5px 0 0 0;">Municipalidad de Los Lagos</p>
    </div>
    <div class="content">
        ' . $contenido . '
    </div>
    <div class="footer">
        <p style="margin: 5px 0;">Este es un correo automático del Sistema de Transparencia Activa.</p>
        <p style="margin: 5px 0;">Por favor no responda directamente a este correo.</p>
        <p style="margin: 5px 0;">© ' . date('Y') . ' Municipalidad de Los Lagos - Todos los derechos reservados</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Obtener el último error
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Enviar notificación de documento pendiente a un usuario
     */
    public function notificarDocumentoPendiente($usuario_id, $item_nombre, $periodicidad, $plazo_envio) {
        // Obtener datos del usuario
        $stmt = $this->conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        
        if (!$usuario || empty($usuario['correo'])) {
            $this->error = 'Usuario sin correo electrónico configurado';
            return false;
        }
        
        $asunto = 'Recordatorio: Documento de Transparencia Pendiente';
        
        $cuerpo = '<h3>Estimado/a ' . htmlspecialchars($usuario['nombre']) . ',</h3>';
        $cuerpo .= '<p>Le recordamos que tiene un documento de transparencia pendiente de carga:</p>';
        $cuerpo .= '<div class="alert alert-warning">';
        $cuerpo .= '<strong>Item:</strong> ' . htmlspecialchars($item_nombre) . '<br>';
        $cuerpo .= '<strong>Periodicidad:</strong> ' . ucfirst($periodicidad) . '<br>';
        $cuerpo .= '<strong>Plazo de envío:</strong> ' . date('d/m/Y', strtotime($plazo_envio));
        $cuerpo .= '</div>';
        $cuerpo .= '<p>Por favor, ingrese al sistema para cargar el documento correspondiente.</p>';
        $cuerpo .= '<p><a href="' . SITE_URL . '" class="button">Ir al Sistema</a></p>';
        
        return $this->enviarCorreo($usuario['correo'], $asunto, $cuerpo, $usuario['nombre']);
    }
    
    /**
     * Enviar notificación de documento aprobado
     */
    public function notificarDocumentoAprobado($usuario_id, $item_nombre, $periodo) {
        $stmt = $this->conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        
        if (!$usuario || empty($usuario['correo'])) {
            return false;
        }
        
        $asunto = 'Documento Aprobado - Transparencia Activa';
        
        $cuerpo = '<h3>Estimado/a ' . htmlspecialchars($usuario['nombre']) . ',</h3>';
        $cuerpo .= '<p>Su documento ha sido aprobado exitosamente:</p>';
        $cuerpo .= '<div class="alert alert-info">';
        $cuerpo .= '<strong>Item:</strong> ' . htmlspecialchars($item_nombre) . '<br>';
        $cuerpo .= '<strong>Período:</strong> ' . htmlspecialchars($periodo);
        $cuerpo .= '</div>';
        $cuerpo .= '<p>El documento será publicado en el portal de transparencia.</p>';
        
        return $this->enviarCorreo($usuario['correo'], $asunto, $cuerpo, $usuario['nombre']);
    }
    
    /**
     * Enviar notificación de documento rechazado
     */
    public function notificarDocumentoRechazado($usuario_id, $item_nombre, $periodo, $motivo) {
        $stmt = $this->conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        
        if (!$usuario || empty($usuario['correo'])) {
            return false;
        }
        
        $asunto = 'Documento Rechazado - Acción Requerida';
        
        $cuerpo = '<h3>Estimado/a ' . htmlspecialchars($usuario['nombre']) . ',</h3>';
        $cuerpo .= '<p>Su documento ha sido rechazado y requiere corrección:</p>';
        $cuerpo .= '<div class="alert alert-danger">';
        $cuerpo .= '<strong>Item:</strong> ' . htmlspecialchars($item_nombre) . '<br>';
        $cuerpo .= '<strong>Período:</strong> ' . htmlspecialchars($periodo) . '<br>';
        $cuerpo .= '<strong>Motivo del rechazo:</strong> ' . htmlspecialchars($motivo);
        $cuerpo .= '</div>';
        $cuerpo .= '<p>Por favor, corrija el documento y vuélvalo a cargar en el sistema.</p>';
        $cuerpo .= '<p><a href="' . SITE_URL . '" class="button">Ir al Sistema</a></p>';
        
        return $this->enviarCorreo($usuario['correo'], $asunto, $cuerpo, $usuario['nombre']);
    }
    
    /**
     * Enviar notificación de plazo vencido al director
     */
    public function notificarPlazoVencido($direccion_id, $item_nombre, $usuario_nombre, $dias_vencido) {
        // Obtener correo del director de la dirección
        $stmt = $this->conn->prepare("
            SELECT u.nombre, u.correo 
            FROM usuarios u
            INNER JOIN direcciones d ON d.id = ?
            WHERE u.perfil = 'director' AND u.activo = 1
            LIMIT 1
        ");
        $stmt->bind_param('i', $direccion_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $director = $result->fetch_assoc();
        $stmt->close();
        
        if (!$director || empty($director['correo'])) {
            return false;
        }
        
        $asunto = 'URGENTE: Plazo Vencido - Transparencia Activa';
        
        $cuerpo = '<h3>Estimado/a ' . htmlspecialchars($director['nombre']) . ',</h3>';
        $cuerpo .= '<p><strong>Informamos que hay un documento de transparencia con plazo vencido en su dirección.</strong></p>';
        $cuerpo .= '<div class="alert alert-danger">';
        $cuerpo .= '<strong>Item:</strong> ' . htmlspecialchars($item_nombre) . '<br>';
        $cuerpo .= '<strong>Responsable:</strong> ' . htmlspecialchars($usuario_nombre) . '<br>';
        $cuerpo .= '<strong>Días de atraso:</strong> ' . $dias_vencido . ' días';
        $cuerpo .= '</div>';
        $cuerpo .= '<p>Esta situación puede afectar el cumplimiento normativo de la municipalidad ante el Consejo para la Transparencia.</p>';
        $cuerpo .= '<p>Solicitamos gestionar la carga inmediata del documento pendiente.</p>';
        
        return $this->enviarCorreo($director['correo'], $asunto, $cuerpo, $director['nombre']);
    }
}
