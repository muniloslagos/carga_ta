-- ==========================================
-- Migración: Sistema de Asignación de Publicadores a Items
-- Fecha: 2026-04-04
-- Descripción: Permite asignar uno o más publicadores a cada item para cargar verificadores
-- ==========================================

-- Crear tabla de asignación de publicadores a items
CREATE TABLE IF NOT EXISTS `item_publicadores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) NOT NULL,
  `usuario_id` INT(11) NOT NULL COMMENT 'ID del publicador',
  `fecha_asignacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `asignado_por` INT(11) DEFAULT NULL COMMENT 'ID del usuario que hizo la asignación',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item_publicador` (`item_id`, `usuario_id`),
  KEY `idx_item` (`item_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_asignado_por` (`asignado_por`),
  CONSTRAINT `fk_item_publicadores_item` FOREIGN KEY (`item_id`) REFERENCES `items_transparencia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_publicadores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_publicadores_asignador` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
