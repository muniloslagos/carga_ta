-- =====================================================
-- MIGRACIÓN: Plazos de Envío y Publicación
-- Fecha: 2026-03
-- =====================================================

-- 1. Agregar campo cumplimiento al subir documento
ALTER TABLE `documentos`
    ADD COLUMN `cumple_plazo_envio` TINYINT(1) DEFAULT NULL
        COMMENT '1 = cargado en plazo, 0 = fuera de plazo, NULL = sin plazo calculado'
    AFTER `ano_carga`;

-- 2. Agregar campo cumplimiento al subir verificador
ALTER TABLE `verificadores_publicador`
    ADD COLUMN `cumple_plazo_publicacion` TINYINT(1) DEFAULT NULL
        COMMENT '1 = publicado en plazo, 0 = fuera de plazo, NULL = sin plazo calculado'
    AFTER `comentarios`;

-- 3. Verificar resultado (usa DESCRIBE, no information_schema)
-- DESCRIBE documentos;
-- DESCRIBE verificadores_publicador;
