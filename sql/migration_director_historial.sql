-- ============================================================================
-- MIGRACIÓN: Agregar columna director_destinatario_id a historial_envios_correo
-- ============================================================================
-- Esta migración permite registrar el historial de correos enviados a directores
-- individuales sin violar la FK constraint de destinatario_id
--
-- PROBLEMA RESUELTO:
-- - destinatario_id tiene FK a usuarios(id)
-- - Los directores están en tabla directores(id), no en usuarios
-- - Al enviar correo individual a director, se violaba FK constraint
--
-- SOLUCIÓN:
-- - Agregar columna director_destinatario_id con FK a directores(id)
-- - destinatario_id se usa para usuarios
-- - director_destinatario_id se usa para directores
--
-- Fecha: 2026-04-06
-- ============================================================================

-- Agregar nueva columna para directores
ALTER TABLE `historial_envios_correo` 
ADD COLUMN `director_destinatario_id` INT(11) NULL DEFAULT NULL 
AFTER `destinatario_id`;

-- Agregar FK constraint a tabla directores
ALTER TABLE `historial_envios_correo`
ADD CONSTRAINT `historial_envios_correo_ibfk_director` 
FOREIGN KEY (`director_destinatario_id`) 
REFERENCES `directores`(`id`) 
ON DELETE SET NULL;

-- Verificación
SELECT 'Migración completada. Verificando estructura:' AS resultado;

SHOW COLUMNS FROM historial_envios_correo LIKE '%destinatario%';

-- ============================================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================================
-- SIGUIENTE PASO:
-- Ejecutar en phpMyAdmin y luego actualizar el código PHP para usar esta columna
-- ============================================================================
SELECT 'Columna director_destinatario_id agregada exitosamente.' AS mensaje;
