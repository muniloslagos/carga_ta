-- Plazos base diferenciados por ítem.
-- NULL significa que el ítem hereda el plazo general (6.º/10.º día hábil).

ALTER TABLE `items_transparencia`
    ADD COLUMN `plazo_dias_cargador` TINYINT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Día hábil base para el cargador; NULL hereda 6'
        AFTER `mes_carga_anual`,
    ADD COLUMN `plazo_dias_publicador` TINYINT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Día hábil base para el publicador; NULL hereda 10'
        AFTER `plazo_dias_cargador`;

