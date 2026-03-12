-- =====================================================
-- Actualización: Sistema de Numeración con Letras
-- Sistema de Numeración - Municipalidad de Los Lagos
-- =====================================================

-- Agregar configuraciones para el sistema de letras + números
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('letra_maxima', 'A', 'Letra máxima del sistema de numeración (A-G). Al llegar a esta letra con el número máximo, se resetea a A1'),
('numero_maximo', '99', 'Número máximo por cada letra (ejemplo: 99 para A1-A99)')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Agregar campo para almacenar letra actual global
ALTER TABLE configuracion ADD COLUMN IF NOT EXISTS letra_actual VARCHAR(1) DEFAULT 'A';

-- Crear tabla para almacenar el estado global de numeración
CREATE TABLE IF NOT EXISTS numeracion_global (
    id INT AUTO_INCREMENT PRIMARY KEY,
    letra_actual VARCHAR(1) NOT NULL DEFAULT 'A',
    numero_actual INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar registro inicial si no existe
INSERT INTO numeracion_global (letra_actual, numero_actual) 
SELECT 'A', 0
WHERE NOT EXISTS (SELECT 1 FROM numeracion_global LIMIT 1);
