-- Migration: Agregar columna mes_carga_anual a items_transparencia
-- Permite configurar en qué mes del año se debe cargar un item anual
-- Si es NULL, se usa el valor por defecto (1 = Enero)

ALTER TABLE items_transparencia 
ADD COLUMN mes_carga_anual TINYINT UNSIGNED DEFAULT NULL AFTER periodicidad;

-- Actualizar items anuales existentes a Enero por defecto
UPDATE items_transparencia SET mes_carga_anual = 1 WHERE periodicidad = 'anual' AND mes_carga_anual IS NULL;
