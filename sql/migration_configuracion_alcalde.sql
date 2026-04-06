-- Tabla de configuración del Alcalde y Subrogantes
-- Permite configurar datos del alcalde y hasta 3 subrogantes para envío de informes

CREATE TABLE IF NOT EXISTS `configuracion_alcalde` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre del alcalde',
  `apellidos` varchar(100) NOT NULL COMMENT 'Apellidos del alcalde',
  `correo` varchar(150) NOT NULL COMMENT 'Correo electrónico del alcalde',
  `subrogante_1_id` int(11) DEFAULT NULL COMMENT 'ID del director subrogante 1',
  `subrogante_2_id` int(11) DEFAULT NULL COMMENT 'ID del director subrogante 2',
  `subrogante_3_id` int(11) DEFAULT NULL COMMENT 'ID del director subrogante 3',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int(11) DEFAULT NULL COMMENT 'Usuario que modificó',
  PRIMARY KEY (`id`),
  KEY `subrogante_1_id` (`subrogante_1_id`),
  KEY `subrogante_2_id` (`subrogante_2_id`),
  KEY `subrogante_3_id` (`subrogante_3_id`),
  KEY `modificado_por` (`modificado_por`),
  CONSTRAINT `configuracion_alcalde_ibfk_1` FOREIGN KEY (`subrogante_1_id`) REFERENCES `directores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `configuracion_alcalde_ibfk_2` FOREIGN KEY (`subrogante_2_id`) REFERENCES `directores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `configuracion_alcalde_ibfk_3` FOREIGN KEY (`subrogante_3_id`) REFERENCES `directores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `configuracion_alcalde_ibfk_4` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solo puede haber una configuración activa
ALTER TABLE `configuracion_alcalde` ADD UNIQUE KEY `activo_unico` (`activo`);
