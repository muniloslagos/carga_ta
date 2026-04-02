-- Tabla de plantillas de correo
CREATE TABLE IF NOT EXISTS `plantillas_correo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('inicio_proceso','fin_proceso_cargadores','fin_proceso_general') NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `cuerpo` text NOT NULL,
  `variables_disponibles` text COMMENT 'JSON con las variables disponibles y su descripción',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `envio_automatico` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si está activo el envío automático',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo` (`tipo`),
  KEY `modificado_por` (`modificado_por`),
  CONSTRAINT `plantillas_correo_ibfk_1` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de envíos
CREATE TABLE IF NOT EXISTS `historial_envios_correo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plantilla_id` int(11) NOT NULL,
  `tipo_envio` enum('masivo','individual') NOT NULL,
  `destinatario_id` int(11) DEFAULT NULL COMMENT 'ID del usuario si es envío individual',
  `destinatarios_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad total de destinatarios',
  `mes_periodo` int(11) NOT NULL COMMENT 'Mes del período de carga',
  `ano_periodo` int(11) NOT NULL COMMENT 'Año del período de carga',
  `correos_enviados` int(11) NOT NULL DEFAULT 0,
  `correos_fallidos` int(11) NOT NULL DEFAULT 0,
  `detalles_envio` text COMMENT 'JSON con detalles de cada envío',
  `fecha_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `enviado_por` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `plantilla_id` (`plantilla_id`),
  KEY `destinatario_id` (`destinatario_id`),
  KEY `enviado_por` (`enviado_por`),
  KEY `fecha_envio` (`fecha_envio`),
  CONSTRAINT `historial_envios_correo_ibfk_1` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas_correo` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_envios_correo_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historial_envios_correo_ibfk_3` FOREIGN KEY (`enviado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plantillas predeterminadas
INSERT INTO `plantillas_correo` (`tipo`, `asunto`, `cuerpo`, `variables_disponibles`, `activo`, `envio_automatico`) VALUES
('inicio_proceso', 'Inicio de Proceso de Carga - {mes_carga} {ano_carga}', '<h2>Estimado/a {nombre_usuario},</h2>\n\n<p>Le informamos que se ha <strong>iniciado el proceso de carga de información</strong> para el período <strong>{mes_carga} {ano_carga}</strong>.</p>\n\n<h3>Ítems Asignados:</h3>\n{items_asignados}\n\n<p><strong>Plazo de entrega:</strong> {plazo_dias} días hábiles (hasta el <strong>{fecha_limite}</strong>)</p>\n\n<p>Por favor, ingrese al sistema para cargar la información correspondiente.</p>\n\n<p>Saludos cordiales,<br>\n<strong>Municipalidad de Los Lagos</strong></p>', '{"nombre_usuario": "Nombre completo del usuario", "mes_carga": "Nombre del mes de carga", "ano_carga": "Año de carga", "mes_siguiente": "Mes siguiente al período", "items_asignados": "Lista HTML de ítems asignados", "plazo_dias": "Cantidad de días hábiles de plazo", "fecha_limite": "Fecha límite formateada"}', 1, 0);

INSERT INTO `plantillas_correo` (`tipo`, `asunto`, `cuerpo`, `variables_disponibles`, `activo`, `envio_automatico`) VALUES
('fin_proceso_cargadores', 'Recordatorio de Plazo - {mes_carga} {ano_carga}', '<h2>Estimado/a {nombre_usuario},</h2>\n\n<p>Le recordamos que el <strong>plazo de entrega</strong> para el proceso de carga de información del período <strong>{mes_carga} {ano_carga}</strong> está por vencer.</p>\n\n<h3>Estado Actual:</h3>\n{resumen_carga}\n\n<p><strong>Fecha límite:</strong> {fecha_limite}</p>\n\n<p>Si aún tiene ítems pendientes, por favor complete la carga lo antes posible.</p>\n\n<p>Saludos cordiales,<br>\n<strong>Municipalidad de Los Lagos</strong></p>', '{"nombre_usuario": "Nombre completo del usuario", "mes_carga": "Nombre del mes de carga", "ano_carga": "Año de carga", "resumen_carga": "Resumen HTML del estado de carga", "fecha_limite": "Fecha límite formateada"}', 1, 0);

INSERT INTO `plantillas_correo` (`tipo`, `asunto`, `cuerpo`, `variables_disponibles`, `activo`, `envio_automatico`) VALUES
('fin_proceso_general', 'Resumen General - Proceso {mes_carga} {ano_carga}', '<h2>Estimado/a Director/a,</h2>\n\n<p>Se adjunta el <strong>resumen general</strong> del proceso de carga y publicación correspondiente al período <strong>{mes_carga} {ano_carga}</strong>.</p>\n\n<h3>Estadísticas de Carga:</h3>\n{estadisticas_carga}\n\n<h3>Estadísticas de Publicación:</h3>\n{estadisticas_publicacion}\n\n<h3>Cumplimiento de Plazos:</h3>\n{cumplimiento_cargadores}\n{cumplimiento_publicadores}\n\n<p>Saludos cordiales,<br>\n<strong>Sistema de Transparencia Activa</strong></p>', '{"mes_carga": "Nombre del mes de carga", "ano_carga": "Año de carga", "estadisticas_carga": "Estadísticas HTML de carga", "estadisticas_publicacion": "Estadísticas HTML de publicación", "cumplimiento_cargadores": "Resumen HTML cumplimiento cargadores", "cumplimiento_publicadores": "Resumen HTML cumplimiento publicadores"}', 1, 0);
