-- =====================================================
-- MIGRACIÓN: Plazos de Envío y Publicación
-- Fecha: 2026-03
-- =====================================================

-- 1. Agregar campo cumplimiento al subir documento
ALTER TABLE `documentos`
    ADD COLUMN IF NOT EXISTS `cumple_plazo_envio` TINYINT(1) DEFAULT NULL
        COMMENT '1 = cargado en plazo, 0 = fuera de plazo, NULL = sin plazo calculado'
    AFTER `ano_carga`;

-- 2. Agregar campo cumplimiento al subir verificador
ALTER TABLE `verificadores_publicador`
    ADD COLUMN IF NOT EXISTS `cumple_plazo_publicacion` TINYINT(1) DEFAULT NULL
        COMMENT '1 = publicado en plazo, 0 = fuera de plazo, NULL = sin plazo calculado'
    AFTER `comentarios`;

-- 3. Verificar resultado
SELECT 'documentos' AS tabla,
       COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'documentos'
  AND COLUMN_NAME  = 'cumple_plazo_envio'

UNION ALL

SELECT 'verificadores_publicador' AS tabla,
       COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'verificadores_publicador'
  AND COLUMN_NAME  = 'cumple_plazo_publicacion';
