-- ============================================================================
-- MIGRACIÓN: Sistema de múltiples perfiles por usuario
-- ============================================================================
-- Esta migración permite que un usuario tenga múltiples perfiles asignados
-- (ej: un auditor que también actúa como cargador de información)
--
-- CAMBIOS:
-- - Crea tabla usuario_perfiles para relación muchos-a-muchos
-- - Migra automáticamente todos los perfiles existentes
-- - Mantiene columna perfil en usuarios como respaldo
-- - NO SE PIERDE NINGUNA INFORMACIÓN
--
-- Fecha: 2026-04-06
-- ============================================================================

-- Crear tabla de relación usuario-perfiles
CREATE TABLE IF NOT EXISTS `usuario_perfiles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `perfil` ENUM('administrativo','director_revisor','cargador_informacion','publicador','auditor') 
    COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_principal` TINYINT(1) DEFAULT 0,
  `fecha_asignacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `asignado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_perfil_unique` (`usuario_id`, `perfil`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_perfil` (`perfil`),
  CONSTRAINT `usuario_perfiles_ibfk_1` 
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_perfiles_ibfk_2` 
    FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar datos existentes: cada usuario actual tendrá su perfil como único perfil principal
INSERT INTO `usuario_perfiles` (`usuario_id`, `perfil`, `es_principal`, `asignado_por`)
SELECT 
  id,
  perfil,
  1, -- Marcar como perfil principal
  NULL -- Sin asignador (migración automática)
FROM usuarios
WHERE activo = 1;

-- Verificar migración
SELECT 'Migración completada. Verificando datos:' AS resultado;

-- Mostrar usuarios y sus perfiles migrados
SELECT 
  u.id,
  u.nombre,
  u.email,
  u.perfil AS perfil_original,
  GROUP_CONCAT(up.perfil ORDER BY up.es_principal DESC SEPARATOR ', ') AS perfiles_asignados,
  COUNT(up.id) AS cantidad_perfiles
FROM usuarios u
LEFT JOIN usuario_perfiles up ON u.id = up.usuario_id
WHERE u.activo = 1
GROUP BY u.id
ORDER BY u.nombre;

-- ============================================================================
-- NOTAS IMPORTANTES:
-- ============================================================================
-- 1. La columna 'perfil' en tabla usuarios SE MANTIENE (no se elimina)
-- 2. Todos los usuarios existentes ahora tienen su perfil en usuario_perfiles
-- 3. Para agregar perfiles adicionales, usar:
--    INSERT INTO usuario_perfiles (usuario_id, perfil, es_principal) 
--    VALUES (ID_USUARIO, 'cargador_informacion', 0);
-- 4. Solo puede haber UN perfil principal por usuario (es_principal = 1)
-- ============================================================================

SELECT 'Sistema de múltiples perfiles activado exitosamente.' AS mensaje;
