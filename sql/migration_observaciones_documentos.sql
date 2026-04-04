-- ==========================================
-- Migración: Sistema de Observaciones de Documentos
-- Fecha: 2026-04-04
-- Descripción: Agrega tabla para observaciones de publicador y plantilla de correo
-- ==========================================

-- 1. Crear tabla de observaciones de documentos
CREATE TABLE IF NOT EXISTS `observaciones_documentos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `documento_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `observado_por` INT(11) NOT NULL COMMENT 'ID del publicador que observó',
  `cargador_id` INT(11) NOT NULL COMMENT 'ID del usuario cargador',
  `observacion` TEXT NOT NULL COMMENT 'Texto de la observación',
  `mes` INT(11) NOT NULL,
  `ano` INT(11) NOT NULL,
  `resuelta` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pendiente, 1=resuelta',
  `fecha_observacion` DATETIME NOT NULL,
  `fecha_resolucion` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_documento` (`documento_id`),
  KEY `idx_item_mes_ano` (`item_id`, `mes`, `ano`),
  KEY `idx_cargador` (`cargador_id`),
  KEY `idx_resuelta` (`resuelta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Modificar el ENUM de plantillas_correo para agregar el nuevo tipo
ALTER TABLE `plantillas_correo` 
MODIFY COLUMN `tipo` ENUM(
  'inicio_proceso',
  'fin_proceso_cargadores',
  'fin_proceso_general',
  'documento_observado'
) NOT NULL;

-- 3. Insertar nueva plantilla de correo para documentos observados
INSERT INTO `plantillas_correo` (`tipo`, `asunto`, `cuerpo`, `variables_disponibles`, `activo`, `envio_automatico`) VALUES
('documento_observado', 
 'Documento Observado - {item_nombre} - {mes_carga} {ano_carga}', 
 '<h2>Estimado/a {nombre_cargador},</h2>\n\n<p>El documento que usted cargó para el ítem "<strong>{item_nombre}</strong>" correspondiente al período <strong>{mes_carga} {ano_carga}</strong> ha sido <strong style="color:#d9534f;">OBSERVADO</strong> por el publicador.</p>\n\n<h3 style="color:#d9534f;">⚠️ Observación del Publicador:</h3>\n<div style="background-color:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin:15px 0;">\n<p style="margin:0; color:#856404;">{observacion}</p>\n</div>\n\n<p>Por favor, <strong>corrija el documento</strong> según la observación indicada y vuelva a cargarlo a la brevedad en el sistema.</p>\n\n<p style="text-align:center; margin:25px 0;">\n<a href="{enlace_sistema}" style="display:inline-block; background-color:#007bff; color:white; padding:12px 30px; text-decoration:none; border-radius:5px; font-weight:bold;">Ir al Sistema</a>\n</p>\n\n<hr style="margin:30px 0; border:none; border-top:1px solid #dee2e6;">\n\n<p style="font-size:12px; color:#6c757d;">Este correo ha sido generado automáticamente por el Sistema de Transparencia de la Municipalidad de Los Lagos.</p>',
 'nombre_cargador, item_nombre, mes_carga, ano_carga, observacion, enlace_sistema',
 1,
 1
);