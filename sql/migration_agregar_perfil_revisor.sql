-- ============================================================================
-- MIGRACIÓN: Agregar perfil 'revisor' al ENUM de tablas usuarios y usuario_perfiles
-- ============================================================================
-- Agrega el perfil 'revisor' a las columnas de perfil en ambas tablas
-- para permitir asignar el nuevo perfil "Revisor de Documentos"
--
-- PROBLEMA: Los ENUM actuales no incluyen 'revisor'
-- SOLUCIÓN: Modificar los ENUM para incluir todos los perfiles disponibles
--
-- Fecha: 2026-05-15
-- ============================================================================

-- 1. Modificar la columna perfil en tabla usuarios
ALTER TABLE `usuarios` 
MODIFY COLUMN `perfil` ENUM(
    'administrativo',
    'director_revisor',
    'cargador_informacion',
    'revisor',
    'publicador',
    'auditor'
) COLLATE utf8mb4_unicode_ci NOT NULL;

-- 2. Modificar la columna perfil en tabla usuario_perfiles
ALTER TABLE `usuario_perfiles` 
MODIFY COLUMN `perfil` ENUM(
    'administrativo',
    'director_revisor',
    'cargador_informacion',
    'revisor',
    'publicador',
    'auditor'
) COLLATE utf8mb4_unicode_ci NOT NULL;

-- 3. Verificar los cambios
SHOW COLUMNS FROM `usuarios` LIKE 'perfil';
SHOW COLUMNS FROM `usuario_perfiles` LIKE 'perfil';

-- ============================================================================
-- NOTA: Esta migración es segura
-- ============================================================================
-- - No afecta los datos existentes
-- - Solo amplía las opciones disponibles de los ENUM
-- - Mantiene todos los valores actuales funcionando
-- - Permite asignar el perfil 'revisor' a usuarios
-- ============================================================================

SELECT 'Perfil revisor agregado exitosamente a ambas tablas' AS resultado;
