-- =====================================================
-- MIGRACIÓN: Agregar mes_carga y ano_carga a documentos
-- Ejecutar en phpMyAdmin en producción
-- =====================================================

ALTER TABLE `documentos`
    ADD COLUMN `mes_carga` tinyint(2) NULL DEFAULT NULL COMMENT 'Mes al que corresponde el documento (1-12)' AFTER `archivo`,
    ADD COLUMN `ano_carga` year NULL DEFAULT NULL COMMENT 'Año al que corresponde el documento' AFTER `mes_carga`;

-- Índice para búsquedas por mes/año
ALTER TABLE `documentos`
    ADD INDEX `idx_mes_ano` (`item_id`, `mes_carga`, `ano_carga`);

-- Actualizar documentos existentes usando fecha_subida como referencia
UPDATE `documentos`
SET
    `mes_carga` = MONTH(`fecha_subida`),
    `ano_carga` = YEAR(`fecha_subida`)
WHERE `mes_carga` IS NULL;
