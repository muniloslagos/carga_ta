-- ============================================================================
-- FIX: Eliminar verificadores de publicación huérfanos
-- ============================================================================
-- Este script elimina solo la tabla verificadores_publicador
-- para solucionar el problema de documentos "fantasma" en el resumen público
--
-- CUÁNDO USAR:
-- - Cuando ya ejecutaste reset_sistema.sql pero el resumen público sigue
--   mostrando documentos publicados
-- - Los verificadores tienen documento_id que ya no existen
--
-- IMPORTANTE:
-- - Ejecutar SOLO si ya eliminaste los documentos con reset_sistema.sql
-- - Esto eliminará todos los registros de publicación en el portal
--
-- Fecha: 2026-04-06
-- ============================================================================

-- Eliminar todos los verificadores de publicación
DELETE FROM `verificadores_publicador`;

-- Reiniciar contador
ALTER TABLE `verificadores_publicador` AUTO_INCREMENT = 1;

-- Optimizar tabla para liberar espacio
OPTIMIZE TABLE `verificadores_publicador`;

-- Verificación
SELECT 'Verificadores restantes:' AS resultado, COUNT(*) AS cantidad 
FROM verificadores_publicador;

-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================
SELECT 'Verificadores eliminados exitosamente. Recarga el resumen público (Ctrl+F5).' AS mensaje;
