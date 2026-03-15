-- =====================================================
-- MIGRACIÓN: Agregar perfil 'auditor' al ENUM de usuarios
-- Ejecutar en phpMyAdmin en producción
-- =====================================================

ALTER TABLE `usuarios`
MODIFY COLUMN `perfil` ENUM(
    'administrativo',
    'director_revisor',
    'cargador_informacion',
    'publicador',
    'auditor'
) COLLATE utf8mb4_unicode_ci NOT NULL;
