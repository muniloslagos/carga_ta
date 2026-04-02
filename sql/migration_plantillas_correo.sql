-- Tabla para plantillas de correo editables
CREATE TABLE IF NOT EXISTS plantillas_correo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('inicio_proceso', 'fin_proceso_cargadores', 'fin_proceso_general') NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    cuerpo TEXT NOT NULL,
    variables_disponibles TEXT COMMENT 'JSON con variables disponibles para esta plantilla',
    activo TINYINT(1) DEFAULT 1,
    envio_automatico TINYINT(1) DEFAULT 0 COMMENT 'Si está activado el envío automático',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modificado_por INT,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY tipo_unico (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para historial de envíos de correo
CREATE TABLE IF NOT EXISTS historial_envios_correo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plantilla_id INT NOT NULL,
    tipo_envio ENUM('masivo', 'individual') NOT NULL,
    destinatario_id INT COMMENT 'NULL si es masivo, ID del usuario si es individual',
    destinatarios_count INT DEFAULT 0 COMMENT 'Cantidad de destinatarios en envío masivo',
    mes_periodo INT COMMENT 'Mes del período para el que se envió (1-12)',
    ano_periodo INT COMMENT 'Año del período',
    correos_enviados INT DEFAULT 0 COMMENT 'Cantidad de correos enviados exitosamente',
    correos_fallidos INT DEFAULT 0 COMMENT 'Cantidad de correos que fallaron',
    detalles_envio TEXT COMMENT 'JSON con detalles de cada envío',
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enviado_por INT NOT NULL,
    FOREIGN KEY (plantilla_id) REFERENCES plantillas_correo(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (enviado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_fecha_envio (fecha_envio),
    INDEX idx_tipo_periodo (tipo_envio, ano_periodo, mes_periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar plantillas por defecto
INSERT INTO plantillas_correo (tipo, asunto, cuerpo, variables_disponibles, activo, envio_automatico) VALUES
(
    'inicio_proceso',
    'Inicio de Carga - Transparencia Activa {mes_carga} {ano_carga}',
    '<h2>Estimado/a {nombre_usuario},</h2>

<p>Se ha iniciado el proceso de carga de documentos para <strong>Transparencia Activa del mes de {mes_carga} {ano_carga}</strong>.</p>

<p>La carga de documentos se realizará desde el <strong>1 de {mes_siguiente}</strong>.</p>

<h3>Ítems asignados que debe cargar:</h3>
{items_asignados}

<p><strong>Plazo de entrega:</strong> {plazo_dias} días hábiles (hasta el <strong>{fecha_limite}</strong>)</p>

<hr>
<p><small>Sistema de Transparencia Activa - Municipalidad de Los Lagos</small></p>',
    '{"nombre_usuario":"Nombre del usuario","mes_carga":"Mes del período a cargar","ano_carga":"Año del período","mes_siguiente":"Mes en que inicia la carga","items_asignados":"Lista HTML de ítems asignados","plazo_dias":"Número de días hábiles","fecha_limite":"Fecha límite formateada"}',
    1,
    0
),
(
    'fin_proceso_cargadores',
    'Fin de Plazo - Carga de Documentos {mes_carga} {ano_carga}',
    '<h2>Estimado/a {nombre_usuario},</h2>

<p>Le informamos que <strong>ha vencido el plazo de carga</strong> de documentos para Transparencia Activa del mes de <strong>{mes_carga} {ano_carga}</strong>.</p>

<p><strong>Fecha límite:</strong> {fecha_limite}</p>

<h3>Estado de su carga:</h3>
{resumen_carga}

<p>Aunque el plazo ha vencido, la plataforma permanece abierta para cargas posteriores si es necesario.</p>

<hr>
<p><small>Sistema de Transparencia Activa - Municipalidad de Los Lagos</small></p>',
    '{"nombre_usuario":"Nombre del usuario","mes_carga":"Mes del período","ano_carga":"Año del período","fecha_limite":"Fecha límite que venció","resumen_carga":"HTML con resumen de ítems cargados/pendientes"}',
    1,
    0
),
(
    'fin_proceso_general',
    'Resumen General del Proceso - {mes_carga} {ano_carga}',
    '<h2>Estimado/a Director/Administrador,</h2>

<p>Se presenta el resumen del proceso de Transparencia Activa para el período <strong>{mes_carga} {ano_carga}</strong>:</p>

<h3>📊 Estadísticas de Carga (Cargadores):</h3>
{estadisticas_carga}

<h3>🌐 Estadísticas de Publicación (Publicadores):</h3>
{estadisticas_publicacion}

<h3>Cumplimiento de Plazos:</h3>
<ul>
    <li><strong>Plazo cargadores (6 días hábiles):</strong> {cumplimiento_cargadores}</li>
    <li><strong>Plazo publicadores (10 días hábiles):</strong> {cumplimiento_publicadores}</li>
</ul>

<hr>
<p><small>Sistema de Transparencia Activa - Municipalidad de Los Lagos</small></p>',
    '{"mes_carga":"Mes del período","ano_carga":"Año del período","estadisticas_carga":"HTML con estadísticas de cargadores","estadisticas_publicacion":"HTML con estadísticas de publicación","cumplimiento_cargadores":"Porcentaje o texto de cumplimiento","cumplimiento_publicadores":"Porcentaje o texto de cumplimiento"}',
    1,
    0
);
