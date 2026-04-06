-- Migración: Sistema de recuperación de contraseña

-- Tabla para tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` timestamp NULL,
  `usado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `usuario_id` (`usuario_id`),
  KEY `fecha_expiracion` (`fecha_expiracion`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar plantilla de correo para recuperación de contraseña
INSERT INTO `plantillas_correo` (`tipo`, `nombre`, `asunto`, `cuerpo`, `variables_disponibles`, `activa`) 
VALUES (
  'recuperar_password',
  'Recuperación de Contraseña',
  'Recuperación de Contraseña - Sistema Transparencia Activa',
  '<h2>Hola {nombre_usuario},</h2>

<p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el <strong>Sistema de Transparencia Activa</strong> de la Municipalidad de Los Lagos.</p>

<p>Si realizaste esta solicitud, haz clic en el siguiente botón para crear una nueva contraseña:</p>

<p style="text-align:center; margin: 30px 0;">
  <a href="{enlace_recuperacion}" style="display:inline-block; padding:15px 30px; background-color:#0d6efd; color:#ffffff; text-decoration:none; border-radius:5px; font-weight:bold; font-size:16px;">
    🔐 Restablecer Contraseña
  </a>
</p>

<p><strong>⏰ Este enlace expirará en 1 hora</strong> (a las {fecha_expiracion}).</p>

<p>Si no solicitaste restablecer tu contraseña, puedes ignorar este correo. Tu contraseña actual seguirá siendo válida.</p>

<hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">

<p style="color: #666; font-size: 12px;">
  <strong>Nota de seguridad:</strong> Si tienes problemas para hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:<br>
  <a href="{enlace_recuperacion}" style="color: #0d6efd; word-break: break-all;">{enlace_recuperacion}</a>
</p>

<p>Saludos cordiales,<br>
<strong>Municipalidad de Los Lagos</strong><br>
Unidad de Transparencia</p>',
  '{"nombre_usuario": "Nombre completo del usuario", "enlace_recuperacion": "URL para restablecer contraseña", "fecha_expiracion": "Hora de expiración del enlace"}',
  1
);
