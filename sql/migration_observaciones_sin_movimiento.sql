-- Tabla de observaciones "Sin Movimiento" por item/período
CREATE TABLE IF NOT EXISTS `observaciones_sin_movimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL COMMENT 'Mes del período',
  `ano` int(11) NOT NULL COMMENT 'Año del período',
  `observacion` text NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `periodo` (`item_id`, `mes`, `ano`),
  CONSTRAINT `obs_sin_mov_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items_transparencia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `obs_sin_mov_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
