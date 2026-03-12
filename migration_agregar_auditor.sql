-- =====================================================
-- MIGRACIÓN: Agregar perfil 'auditor'
-- Fecha: 2026-03-04
-- =====================================================

-- 1. Agregar 'auditor' al ENUM de perfil en la tabla usuarios
ALTER TABLE `usuarios` 
MODIFY COLUMN `perfil` ENUM('administrativo','director_revisor','cargador_informacion','publicador','auditor') 
COLLATE utf8mb4_unicode_ci NOT NULL;

-- 2. Crear usuario auditor
-- Contraseña: 5802863aA$ (hash bcrypt)
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `perfil`, `direccion_id`, `activo`)
VALUES (
    'Auditor',
    'auditor@muniloslagos.cl',
    '$2y$10$akXqaIpCh.2Cw7nSRF6OjetgsL/cBKbTrAessSqfssyoEaT92ob/u',
    'auditor',
    NULL,
    1
);
