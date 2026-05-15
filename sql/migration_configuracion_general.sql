-- ============================================================================
-- MIGRACIÓN: Tabla de configuración general del sistema
-- ============================================================================
-- Almacena configuraciones generales del sistema como:
-- - Tamaño máximo de archivos a cargar
-- - Otras configuraciones del sistema
--
-- Fecha: 2026-05-15
-- ============================================================================

CREATE TABLE IF NOT EXISTS `configuracion` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Clave única de configuración',
    `valor` TEXT NOT NULL COMMENT 'Valor de la configuración',
    `descripcion` TEXT NULL COMMENT 'Descripción de la configuración',
    `fecha_modificacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por` INT NULL,
    INDEX `idx_clave` (`clave`),
    FOREIGN KEY (`modificado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Insertar configuración inicial
-- ============================================================================

-- Tamaño máximo de archivo en MB (valor por defecto: 200 MB)
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) 
VALUES ('max_file_size_mb', '200', 'Tamaño máximo de archivo permitido para carga (en MB)')
ON DUPLICATE KEY UPDATE valor = valor;

-- Nota: El ON DUPLICATE KEY UPDATE valor = valor evita sobrescribir 
-- el valor si ya existe la configuración
