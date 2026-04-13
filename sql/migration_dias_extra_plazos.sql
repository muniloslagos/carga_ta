-- ============================================================================
-- MIGRACIÓN: Días extra independientes para cargador y publicador
-- ============================================================================
-- Cambia el sistema de plazos: en vez de guardar fechas fijas, se guardan
-- días hábiles adicionales que se suman a la base automática (6 y 10).
--
-- CAMBIOS:
-- - Agrega columna dias_extra_cargador (INT DEFAULT 0)
-- - Agrega columna dias_extra_publicador (INT DEFAULT 0)
--
-- Fecha: 2026-04-13
-- ============================================================================

-- Agregar columnas de días extra
ALTER TABLE `item_plazos`
ADD COLUMN `dias_extra_cargador` INT NOT NULL DEFAULT 0
COMMENT 'Días hábiles adicionales al plazo base del cargador (6)'
AFTER `fecha_carga_portal`;

ALTER TABLE `item_plazos`
ADD COLUMN `dias_extra_publicador` INT NOT NULL DEFAULT 0
COMMENT 'Días hábiles adicionales al plazo base del publicador (10)'
AFTER `dias_extra_cargador`;

-- Verificar cambios
SHOW COLUMNS FROM `item_plazos`;
