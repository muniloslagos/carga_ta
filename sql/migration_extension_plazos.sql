-- ============================================================================
-- MIGRACIÓN: Extensión de plazos con motivo
-- ============================================================================
-- Permite al administrador extender plazos manualmente cuando hay feriados
-- y documentar el motivo de la extensión
--
-- CAMBIOS:
-- - Agrega columna motivo_extension a item_plazos
-- - Permite justificar ampliaciones de plazo
--
-- Fecha: 2026-04-08
-- ============================================================================

-- Agregar columna para documentar motivo de extensión de plazo
ALTER TABLE `item_plazos` 
ADD COLUMN `motivo_extension` VARCHAR(255) NULL 
COMMENT 'Justificación de extensión de plazo (ej: feriados)' 
AFTER `fecha_carga_portal`;

-- Verificar cambio
SHOW COLUMNS FROM `item_plazos` LIKE 'motivo_extension';

-- ============================================================================
-- NOTAS DE USO:
-- ============================================================================
-- 1. Campo opcional - solo se llena cuando hay extensión de plazo
-- 2. Ejemplos de uso:
--    - "Ampliado por feriados 18 y 19 de septiembre"
--    - "Extendido 2 días por feriado de año nuevo"
--    - "Ampliación por feriados patrios"
-- 3. Se muestra en interfaz admin para referencia histórica
-- ============================================================================

SELECT 'Columna motivo_extension agregada exitosamente' AS resultado;
