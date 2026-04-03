-- Migración: Tabla de tokens públicos para resumen municipal y actualización de plantilla fin_proceso_general

-- Tabla para tokens de acceso público al resumen
CREATE TABLE IF NOT EXISTS `resumen_publico_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `resumen_tokens_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar plantilla fin_proceso_general con nuevo texto y variables
UPDATE `plantillas_correo` SET 
  asunto = 'Cierre del Proceso de Carga - Transparencia Activa - {mes_carga} {ano_carga}',
  cuerpo = '<h2>Estimado/a Director/a,</h2>\n\n<p>Por medio del presente, le informamos que el día de hoy, <strong>{fecha_cierre}</strong>, correspondiente al <strong>10° día hábil del mes</strong>, se ha dado por <strong>finalizado el proceso de carga de información</strong> en el Portal de Transparencia Activa para el período <strong>{mes_carga} {ano_carga}</strong>, conforme a los plazos establecidos por la Ley N° 20.285 sobre Acceso a la Información Pública.</p>\n\n<h3>⚠️ Importante:</h3>\n<p>A partir de esta fecha, toda información que <strong>no haya sido cargada oportunamente</strong> en el portal se considera fuera de plazo. El incumplimiento en la entrega oportuna de la información de Transparencia Activa podría constituir una <strong>infracción a la Ley de Transparencia</strong>, susceptible de ser sancionada por el Consejo para la Transparencia conforme a los artículos 45 y siguientes de la referida ley.</p>\n\n<p>Si existen documentos pendientes de carga correspondientes a este período, le solicitamos que sean <strong>ingresados a la brevedad posible</strong> para regularizar la situación y minimizar eventuales riesgos de incumplimiento normativo.</p>\n\n<h3>Resumen del Período {mes_carga} {ano_carga}:</h3>\n{resumen_general}\n\n<p>Puede consultar el resumen completo de todos los ítems del municipio en el siguiente enlace:</p>\n<p style=\"text-align:center;\"><a href=\"{enlace_resumen}\" style=\"display:inline-block; padding:12px 24px; background-color:#0d6efd; color:#ffffff; text-decoration:none; border-radius:5px; font-weight:bold;\">📊 Ver Resumen Municipal Completo</a></p>\n\n<p>Saludos cordiales,<br>\n<strong>Municipalidad de Los Lagos</strong><br>\nUnidad de Transparencia</p>',
  variables_disponibles = '{"mes_carga": "Nombre del mes", "ano_carga": "Año", "fecha_cierre": "Fecha del cierre (10° día hábil)", "resumen_general": "Tabla resumen de todos los ítems", "enlace_resumen": "URL pública al resumen municipal completo"}'
WHERE tipo = 'fin_proceso_general';
