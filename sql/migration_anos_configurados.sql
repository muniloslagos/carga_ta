-- Migración: Tabla de años configurados para el sistema

-- Tabla para gestionar qué años están disponibles en el sistema
CREATE TABLE IF NOT EXISTS `anos_configurados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ano` (`ano`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `anos_configurados_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar año 2026 como activo por defecto
INSERT INTO `anos_configurados` (`ano`, `activo`, `creado_por`) 
VALUES (2026, 1, NULL);
