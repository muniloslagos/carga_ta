-- ============================================================================
-- FIX: Eliminar token antiguo del resumen público
-- ============================================================================
-- Este script elimina el token que se generó antes del reset
-- para que el sistema genere uno nuevo con datos actualizados
--
-- CUÁNDO USAR:
-- - Cuando el enlace "Resumen General" en el menú siempre muestra datos viejos
-- - Después de ejecutar reset_sistema.sql
--
-- IMPORTANTE:
-- - Esto eliminará TODOS los tokens existentes
-- - Los enlaces de correos enviados anteriormente dejarán de funcionar
-- - El sistema generará automáticamente nuevos tokens al acceder
--
-- Fecha: 2026-04-06
-- ============================================================================

-- Ver tokens existentes antes de eliminar
SELECT 'Tokens actuales:' AS info, token, mes, ano, fecha_creacion, creado_por 
FROM resumen_publico_tokens 
ORDER BY fecha_creacion DESC;

-- Eliminar todos los tokens antiguos
DELETE FROM `resumen_publico_tokens`;

-- Reiniciar contador
ALTER TABLE `resumen_publico_tokens` AUTO_INCREMENT = 1;

-- Verificación
SELECT 'Tokens restantes:' AS resultado, COUNT(*) AS cantidad 
FROM resumen_publico_tokens;

-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================
-- SIGUIENTE PASO:
-- 1. Recarga cualquier página del sistema (dashboard, admin, etc.)
-- 2. El sistema generará automáticamente un nuevo token
-- 3. El enlace "Resumen General" ahora mostrará datos actualizados
-- ============================================================================
SELECT 'Tokens eliminados. Recarga el sistema para generar nuevos tokens.' AS mensaje;
