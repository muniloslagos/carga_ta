-- Migración: Sistema de observaciones de documentos por publicador

-- Tabla para historial de observaciones a documentos
CREATE TABLE IF NOT EXISTS `observaciones_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documento_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `observado_por` int(11) NOT NULL COMMENT 'ID del publicador que observó',
  `cargador_id` int(11) NOT NULL COMMENT 'ID del cargador que debe corregir',
  `observacion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `mes` int(11) NOT NULL COMMENT 'Mes del documento observado',
  `ano` int(11) NOT NULL COMMENT 'Año del documento observado',
  `resuelta` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si el cargador ya subió documento corregido',
  `fecha_observacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documento_id` (`documento_id`),
  KEY `item_id` (`item_id`),
  KEY `observado_por` (`observado_por`),
  KEY `cargador_id` (`cargador_id`),
  KEY `resuelta` (`resuelta`),
  CONSTRAINT `observaciones_documentos_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `observaciones_documentos_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items_transparencia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `observaciones_documentos_ibfk_3` FOREIGN KEY (`observado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `observaciones_documentos_ibfk_4` FOREIGN KEY (`cargador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar nueva plantilla de correo para documentos observados
INSERT INTO `plantillas_correo` (`tipo`, `asunto`, `cuerpo`, `variables_disponibles`, `activo`, `envio_automatico`) VALUES
('documento_observado', 
 'Documento Observado - {item_nombre} - {mes_carga} {ano_carga}', 
 '<h2>Estimado/a {nombre_cargador},</h2>\n\n<p>El documento que usted cargó para el ítem "<strong>{item_nombre}</strong>" correspondiente al período <strong>{mes_carga} {ano_carga}</strong> ha sido <strong style="color:#d9534f;">OBSERVADO</strong> por el publicador.</p>\n\n<h3 style="color:#d9534f;">⚠️ Observación del Publicador:</h3>\n<div style="background-color:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin:15px 0;">\n<p style="margin:0; color:#856404;">{observacion}</p>\n</div>\n\n<p>Por favor, <strong>corrija el documento</strong> según la observación indicada y vuelva a cargarlo a la brevedad en el sistema.</p>\n\n<p style="text-align:center; margin:25px 0;">\n<a href="{enlace_sistema}" style="display:inline-block; padding:12px 24px; background-color:#0d6efd; color:#ffffff; text-decoration:none; border-radius:5px; font-weight:bold;">📁 Ir al Sistema para Cargar Documento Corregido</a>\n</p>\n\n<p><strong>Importante:</strong> El documento observado quedará visible en el sistema hasta que usted cargue el documento corregido.</p>\n\n<p>Saludos cordiales,<br>\n<strong>Municipalidad de Los Lagos</strong><br>\nUnidad de Transparencia</p>', 
 '{"nombre_cargador": "Nombre completo del cargador", "item_nombre": "Nombre del ítem", "item_numeracion": "Numeración del ítem", "mes_carga": "Nombre del mes", "ano_carga": "Año", "observacion": "Texto de la observación del publicador", "enlace_sistema": "URL directa al dashboard con el mes seleccionado", "observado_por": "Nombre del publicador que observó"}', 
 1, 
 0
)
ON DUPLICATE KEY UPDATE
  asunto = VALUES(asunto),
  cuerpo = VALUES(cuerpo),
  variables_disponibles = VALUES(variables_disponibles);

-- Modificar ENUM de plantillas_correo para incluir documento_observado
ALTER TABLE `plantillas_correo`
MODIFY COLUMN `tipo` ENUM('inicio_proceso','fin_proceso_cargadores','fin_proceso_general','documento_observado') NOT NULL;
