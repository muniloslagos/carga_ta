-- ============================================================================
-- MIGRACIÓN: Notas administrativas para items
-- ============================================================================
-- Permite agregar notas/anotaciones a los items de transparencia con:
-- - Texto de la nota
-- - Archivo adjunto opcional
-- - Fecha de registro automática
-- - Usuario que creó la nota
--
-- Fecha: 2026-04-14
-- ============================================================================

CREATE TABLE IF NOT EXISTS `item_notas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `nota` TEXT NOT NULL,
    `archivo` VARCHAR(255) NULL COMMENT 'Nombre del archivo adjunto',
    `archivo_original` VARCHAR(255) NULL COMMENT 'Nombre original del archivo',
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `items_transparencia`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    INDEX `idx_item_notas_item` (`item_id`),
    INDEX `idx_item_notas_fecha` (`fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
