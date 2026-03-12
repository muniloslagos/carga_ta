-- =====================================================
-- MIGRACIÓN: Agregar tabla historial
-- Fecha: 2026-02-26
-- Descripción: Tabla para registrar historial de movimientos
-- =====================================================

-- Crear tabla historial si no existe
CREATE TABLE IF NOT EXISTS `historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `documento_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'documento_cargado, verificador_agregado, estado_cambio, etc.',
  `descripcion` text NOT NULL COMMENT 'Descripción breve del movimiento',
  `detalle` text DEFAULT NULL COMMENT 'Detalles adicionales del movimiento',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `documento_id` (`documento_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_historial_fecha` (`fecha`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items_transparencia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_ibfk_2` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar que se creó correctamente
SELECT 'Tabla historial creada exitosamente' AS resultado;
