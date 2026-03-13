-- Remover restricción UNIQUE de la columna numeracion
-- Esto permite que múltiples items tengan el mismo número

-- Verificar si existe la restricción
SHOW INDEX FROM items_transparencia WHERE Key_name = 'numeracion';

-- Remover la restricción UNIQUE
ALTER TABLE items_transparencia DROP INDEX numeracion;

-- Verificar que se removió correctamente
SHOW INDEX FROM items_transparencia;
