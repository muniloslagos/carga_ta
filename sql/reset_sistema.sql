-- ============================================================================
-- SCRIPT DE RESET DEL SISTEMA DE TRANSPARENCIA ACTIVA
-- ============================================================================
-- Este script elimina todos los datos operacionales manteniendo la configuración
-- del sistema (usuarios, direcciones, ítems, plantillas, etc.)
--
-- IMPORTANTE: 
-- - Ejecutar SOLO en ambiente de producción cuando se vaya a iniciar operación real
-- - Los archivos físicos en uploads/ deben eliminarse MANUALMENTE
-- - El historial de correos se MANTIENE para referencia
-- - Los tokens de resumen se ELIMINAN (se generan nuevos automáticamente)
--
-- NOTA TÉCNICA:
-- - Se usa DELETE en lugar de TRUNCATE por compatibilidad con phpMyAdmin
-- - Se ejecuta OPTIMIZE TABLE al final para liberar espacio en disco
--
-- Fecha: 2026-04-06
-- ============================================================================

-- ⚠️ ADVERTENCIA: Este script elimina permanentemente todos los documentos
-- Hacer un respaldo antes de ejecutar: mysqldump -u user -p db > backup.sql

-- Desactivar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. ELIMINAR OBSERVACIONES DE DOCUMENTOS
-- ============================================================================
-- Usando DELETE en lugar de TRUNCATE para compatibilidad con phpMyAdmin
DELETE FROM `observaciones_documentos`;
DELETE FROM `observaciones_sin_movimiento`;

-- ============================================================================
-- 2. ELIMINAR TODOS LOS DOCUMENTOS CARGADOS
-- ============================================================================
-- IMPORTANTE: Después de ejecutar esto, eliminar manualmente la carpeta uploads/
-- o su contenido para liberar espacio en disco

-- Primero eliminar los verificadores de publicación (tiene FK hacia documentos)
DELETE FROM `verificadores_publicador`;

-- Luego eliminar el seguimiento de documentos (tiene FK hacia documentos)
DELETE FROM `documento_seguimiento`;

-- Finalmente eliminar los documentos
DELETE FROM `documentos`;

-- ============================================================================
-- 3. ELIMINAR TOKENS DE RESUMEN PÚBLICO
-- ============================================================================
-- Los tokens apuntan a periodos con datos que ya no existen
-- Se generarán automáticamente nuevos tokens al acceder al sistema
DELETE FROM `resumen_publico_tokens`;

-- ============================================================================
-- RESUMEN DE LO QUE SE MANTIENE:
-- ============================================================================
-- ✓ Usuarios (con sus contraseñas actuales)
-- ✓ Direcciones
-- ✓ Directores
-- ✓ Items de transparencia (items_transparencia)
-- ✓ Plantillas de correo
-- ✓ Años configurados
-- ✓ Configuración del Alcalde
-- ✓ Historial de envíos de correo (para referencia histórica)

-- ============================================================================
-- RESUMEN DE LO QUE SE ELIMINÓ:
-- ============================================================================
-- ✗ Todos los documentos cargados (tabla documentos)
-- ✗ Todo el seguimiento de documentos (tabla documento_seguimiento)
-- ✗ Todos los verificadores de publicación (tabla verificadores_publicador)
-- ✗ Todas las observaciones de documentos
-- ✗ Todas las observaciones de items sin movimiento
-- ✗ Tokens de resumen público (se generarán nuevos automáticamente)

-- ============================================================================
-- VERIFICACIÓN POST-RESET
-- ============================================================================
-- Ejecutar estas consultas para verificar el reset:

SELECT 'Documentos restantes:' AS verificacion, COUNT(*) AS cantidad FROM documentos;
SELECT 'Seguimiento de documentos restantes:' AS verificacion, COUNT(*) AS cantidad FROM documento_seguimiento;
SELECT 'Verificadores de publicación restantes:' AS verificacion, COUNT(*) AS cantidad FROM verificadores_publicador;
SELECT 'Observaciones de documentos restantes:' AS verificacion, COUNT(*) AS cantidad FROM observaciones_documentos;
SELECT 'Observaciones sin movimiento restantes:' AS verificacion, COUNT(*) AS cantidad FROM observaciones_sin_movimiento;
SELECT 'Tokens de resumen público restantes:' AS verificacion, COUNT(*) AS cantidad FROM resumen_publico_tokens;

SELECT 'Usuarios activos:' AS verificacion, COUNT(*) AS cantidad FROM usuarios WHERE activo = 1;
SELECT 'Direcciones activas:' AS verificacion, COUNT(*) AS cantidad FROM direcciones WHERE activa = 1;
SELECT 'Items activos:' AS verificacion, COUNT(*) AS cantidad FROM items_transparencia WHERE activo = 1;
SELECT 'Correos en historial:' AS verificacion, COUNT(*) AS cantidad FROM historial_envios_correo;

-- ============================================================================
-- SIGUIENTE PASO MANUAL REQUERIDO:
-- ============================================================================
-- 1. En el servidor, ejecutar:
--    rm -rf /ruta/del/proyecto/uploads/*
--    o eliminar manualmente el contenido de la carpeta uploads/
--
-- 2. Opcional: Recrear estructura de carpetas si es necesario
--    mkdir -p /ruta/del/proyecto/uploads
--    chmod 755 /ruta/del/proyecto/uploads
--    chown www-data:www-data /ruta/del/proyecto/uploads
-- ============================================================================

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- Reiniciar contadores de auto_increment a 1
ALTER TABLE `documentos` AUTO_INCREMENT = 1;
ALTER TABLE `documento_seguimiento` AUTO_INCREMENT = 1;
ALTER TABLE `verificadores_publicador` AUTO_INCREMENT = 1;
ALTER TABLE `observaciones_documentos` AUTO_INCREMENT = 1;
ALTER TABLE `observaciones_sin_movimiento` AUTO_INCREMENT = 1;
ALTER TABLE `resumen_publico_tokens` AUTO_INCREMENT = 1;

-- Optimizar tablas para liberar espacio en disco
OPTIMIZE TABLE `documentos`;
OPTIMIZE TABLE `documento_seguimiento`;
OPTIMIZE TABLE `verificadores_publicador`;
OPTIMIZE TABLE `observaciones_documentos`;
OPTIMIZE TABLE `observaciones_sin_movimiento`;
OPTIMIZE TABLE `resumen_publico_tokens`;

-- ============================================================================
-- FIN DEL SCRIPT DE RESET
-- ============================================================================
SELECT 'Reset completado exitosamente. Sistema listo para iniciar desde cero.' AS mensaje;
