-- Migración: Permitir NULL en mes_periodo y ano_periodo del historial
-- Para correos que no están asociados a un período específico (ej: envío de contraseñas)

ALTER TABLE `historial_envios_correo`
MODIFY COLUMN `mes_periodo` INT(11) DEFAULT NULL COMMENT 'Mes del período de carga (NULL si no aplica)',
MODIFY COLUMN `ano_periodo` INT(11) DEFAULT NULL COMMENT 'Año del período de carga (NULL si no aplica)';
