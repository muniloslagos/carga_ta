-- ==========================================
-- Migración: Agregar estado 'reemplazado' a documentos
-- Fecha: 2026-04-04
-- Descripción: Permite marcar documentos que fueron corregidos/reemplazados después de una observación
-- ==========================================

-- Modificar el ENUM de documentos.estado para agregar 'reemplazado' y 'Publicado'
ALTER TABLE `documentos` 
MODIFY COLUMN `estado` ENUM(
  'pendiente',
  'aprobado',
  'rechazado',
  'reemplazado',
  'Publicado'
) COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente';
