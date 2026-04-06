-- ============================================================================
-- SCRIPT DE RESET DEL SISTEMA DE TRANSPARENCIA ACTIVA
-- ============================================================================
-- Este script elimina todos los datos operacionales manteniendo la configuración
-- del sistema (usuarios, direcciones, ítems, plantillas, etc.)
--
-- IMPORTANTE: 
-- - Ejecutar SOLO en ambiente de producción cuando se vaya a iniciar operación real
-- - Los archivos físicos en uploads/ deben eliminarse MANUALMENTE
-- - El historial de correos y tokens públicos se MANTIENEN
--
-- Fecha: 2026-04-06
-- ============================================================================

-- Desactivar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. ELIMINAR OBSERVACIONES DE DOCUMENTOS
-- ============================================================================
TRUNCATE TABLE `observaciones_documentos`;
TRUNCATE TABLE `observaciones_sin_movimiento`;

-- ============================================================================
-- 2. ELIMINAR TODOS LOS DOCUMENTOS CARGADOS
-- ============================================================================
-- IMPORTANTE: Después de ejecutar esto, eliminar manualmente la carpeta uploads/
-- o su contenido para liberar espacio en disco

-- Primero eliminar el seguimiento de documentos (tiene FK hacia documentos)
TRUNCATE TABLE `documento_seguimiento`;

-- Luego eliminar los documentos
TRUNCATE TABLE `documentos`;

-- ============================================================================
-- RESUMEN DE LO QUE SE MANTIENE:
-- ============================================================================
-- ✓ Usuarios (con sus contraseñas actuales)
-- ✓ Direcciones
-- ✓ Directores
-- ✓ Items de transparencia
-- ✓ Plantillas de correo
-- ✓ Años configurados
-- ✓ Configuración del Alcalde
-- ✓ Historial de envíos de correo (para referencia)
-- ✓ Tokens de resumen público (enlaces de correos siguen funcionando)

-- ============================================================================
-- RESUMEN DE LO QUE SE ELIMINÓ:
-- ============================================================================
-- ✗ Todos los documentos cargados (tabla documentos)
-- ✗ Todo el seguimiento de documentos (tabla documento_seguimiento)
-- ✗ Todas las observaciones de documentos
-- ✗ Todas las observaciones de items sin movimiento

-- ============================================================================
-- VERIFICACIÓN POST-RESET
-- ============================================================================
-- Ejecutar estas consultas para verificar el reset:

SELECT 'Documentos restantes:' AS verificacion, COUNT(*) AS cantidad FROM documentos;
SELECT 'Seguimiento de documentos restantes:' AS verificacion, COUNT(*) AS cantidad FROM documento_seguimiento;
SELECT 'Observaciones de documentos restantes:' AS verificacion, COUNT(*) AS cantidad FROM observaciones_documentos;
SELECT 'Observaciones sin movimiento restantes:' AS verificacion, COUNT(*) AS cantidad FROM observaciones_sin_movimiento;

SELECT 'Usuarios activos:' AS verificacion, COUNT(*) AS cantidad FROM usuarios WHERE activo = 1;
SELECT 'Direcciones activas:' AS verificacion, COUNT(*) AS cantidad FROM direcciones WHERE activa = 1;
SELECT 'Items activos:' AS verificacion, COUNT(*) AS cantidad FROM items WHERE activo = 1;
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

-- ============================================================================
-- FIN DEL SCRIPT DE RESET
-- ============================================================================
SELECT 'Reset completado exitosamente. Sistema listo para iniciar desde cero.' AS mensaje;
