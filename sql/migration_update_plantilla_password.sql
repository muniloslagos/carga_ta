-- Actualización de plantilla de envío de contraseña
-- Elimina la recomendación de cambiar contraseña (ya que ahora se envía una nueva contraseña personalizada)

UPDATE plantillas_correo 
SET cuerpo = '<p>Estimado/a {nombre_usuario},</p>

<p>Se ha generado una nueva contraseña para su acceso al <strong>Sistema de Transparencia Activa</strong>.</p>

<p><strong>Datos de acceso:</strong></p>
<ul>
    <li><strong>Usuario:</strong> {email_usuario}</li>
    <li><strong>Contraseña:</strong> {password}</li>
</ul>

<p>Puede ingresar al sistema desde el siguiente enlace:</p>
<p><a href="{url_sistema}">{url_sistema}</a></p>

<p>Si tiene algún problema o duda, comuníquese con el administrador del sistema.</p>'
WHERE tipo_correo = 'envio_password';
