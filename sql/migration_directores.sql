-- Migración: Crear tabla directores y agregar director_id a direcciones

CREATE TABLE IF NOT EXISTS `directores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(255) NOT NULL,
  `apellidos` varchar(255) NOT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna director_id a direcciones (un director puede tener múltiples direcciones)
ALTER TABLE `direcciones` ADD COLUMN `director_id` int(11) DEFAULT NULL AFTER `descripcion`;
ALTER TABLE `direcciones` ADD KEY `director_id` (`director_id`);
ALTER TABLE `direcciones` ADD CONSTRAINT `direcciones_director_fk` FOREIGN KEY (`director_id`) REFERENCES `directores` (`id`) ON DELETE SET NULL;
