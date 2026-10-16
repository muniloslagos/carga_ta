-- Alternativa manual a la actualización 1.3.0.
ALTER TABLE items_transparencia
    ADD COLUMN plantilla_archivo VARCHAR(500) NULL DEFAULT NULL,
    ADD COLUMN plantilla_nombre_original VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN plantilla_subida_por INT NULL DEFAULT NULL,
    ADD COLUMN plantilla_fecha_actualizacion TIMESTAMP NULL DEFAULT NULL;
