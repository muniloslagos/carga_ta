-- Migración: Agregar plantilla de envío de contraseñas

-- Actualizar ENUM para incluir 'envio_password'
ALTER TABLE `plantillas_correo`
MODIFY COLUMN `tipo` ENUM(
    'inicio_proceso',
    'fin_proceso_cargadores',
    'fin_proceso_general',
    'documento_observado',
    'recuperar_password',
    'envio_password'
) NOT NULL;

-- Insertar plantilla predeterminada para envío de contraseña
INSERT INTO `plantillas_correo` (`tipo`, `asunto`, `cuerpo`, `variables_disponibles`, `activo`, `envio_automatico`) 
VALUES (
    'envio_password',
    'Credenciales de Acceso - Sistema de Transparencia Activa',
    '<h2>Estimado/a {nombre_usuario},</h2>

<p>Le enviamos sus <strong>credenciales de acceso</strong> al Sistema de Administración de Carga Unificada y Control de Transparencia Activa.</p>

<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
    <p style="margin: 5px 0;"><strong>Usuario:</strong> {email_usuario}</p>
    <p style="margin: 5px 0;"><strong>Contraseña:</strong> <code style="background-color: #e9ecef; padding: 3px 8px; border-radius: 3px;">{password}</code></p>
</div>

<p><strong>URL de acceso:</strong><br>
<a href="{url_sistema}" style="color: #007bff;">{url_sistema}</a></p>

<p>Por motivos de seguridad, le recomendamos cambiar su contraseña después del primer inicio de sesión.</p>

<p>Saludos cordiales,<br>
<strong>Municipalidad de Los Lagos</strong></p>',
    '{"nombre_usuario": "Nombre completo del usuario", "email_usuario": "Correo electrónico del usuario", "password": "Contraseña del usuario", "url_sistema": "URL del sistema"}',
    1,
    0
);
