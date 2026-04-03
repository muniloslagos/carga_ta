-- Actualizar texto de plantilla fin_proceso_cargadores
-- Cambiar "está por vencer" por texto que indique que HOY vence el plazo

UPDATE plantillas_correo 
SET cuerpo = '<h2>Estimado/a {nombre_usuario},</h2>

<p>El día de <strong>hoy ({fecha_limite})</strong> vence el plazo para cargar los documentos correspondientes al período <strong>{mes_carga} {ano_carga}</strong>.</p>

<p>Si aún tiene documentos pendientes, debe cargarlos lo antes posible.</p>

<h3>Estado de Carga:</h3>
{resumen_carga}

<p>Si tiene documentos pendientes, por favor regularice su situación a la brevedad.</p>

<p>Saludos cordiales,<br>
<strong>Municipalidad de Los Lagos</strong></p>'
WHERE tipo = 'fin_proceso_cargadores';
