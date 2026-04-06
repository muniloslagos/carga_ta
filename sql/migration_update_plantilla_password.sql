-- Actualización de plantilla de envío de contraseña
-- Elimina la recomendación de cambiar contraseña (ya que ahora se envía una nueva contraseña personalizada)

UPDATE plantillas_correo 
SET cuerpo = '<h2>Estimado/a {nombre_usuario},</h2>

<p>Se ha generado una nueva contraseña para su acceso al <strong>Sistema de Transparencia Activa</strong>.</p>

<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
    <p style="margin: 5px 0;"><strong>Usuario:</strong> {email_usuario}</p>
    <p style="margin: 5px 0;"><strong>Contraseña:</strong> <code style="background-color: #e9ecef; padding: 3px 8px; border-radius: 3px;">{password}</code></p>
</div>

<p><strong>URL de acceso:</strong><br>
<a href="{url_sistema}" style="color: #007bff;">{url_sistema}</a></p>

<p>Si tiene algún problema o duda, comuníquese con el administrador del sistema.</p>

<p>Saludos cordiales,<br>
<strong>Municipalidad de Los Lagos</strong></p>'
WHERE tipo = 'envio_password';
