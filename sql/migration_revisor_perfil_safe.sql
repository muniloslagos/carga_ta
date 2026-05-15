-- ============================================================================
-- MIGRACIÓN: Perfil Revisor y Sistema de Revisión de Documentos (VERSIÓN SEGURA)
-- ============================================================================
-- Implementa un nuevo perfil "revisor" que puede:
-- - Revisar documentos cargados antes de la publicación
-- - Aprobar o Observar documentos
-- - Funcionalidad opcional (se activa/desactiva en configuración)
--
-- Esta versión usa procedimientos almacenados para evitar errores si los objetos ya existen
--
-- Fecha: 2026-05-15
-- ============================================================================

-- ============================================================================
-- 1. Crear tabla de revisiones de documentos
-- ============================================================================

CREATE TABLE IF NOT EXISTS `revisiones_documentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `documento_id` INT NOT NULL,
    `revisor_id` INT NOT NULL,
    `estado` ENUM('aprobado', 'observado') NOT NULL COMMENT 'Estado de la revisión',
    `observaciones` TEXT NULL COMMENT 'Observaciones del revisor',
    `fecha_revision` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`documento_id`) REFERENCES `documentos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`revisor_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    INDEX `idx_documento` (`documento_id`),
    INDEX `idx_revisor` (`revisor_id`),
    INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. Agregar configuración para activar/desactivar revisión previa
-- ============================================================================

INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) 
VALUES ('activar_revision_previa', '0', 'Activar proceso de revisión previa por revisor antes de publicación (0=No, 1=Sí)')
ON DUPLICATE KEY UPDATE valor = valor;

-- ============================================================================
-- 3. Crear índice adicional en documentos de forma segura
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS agregar_indice_documentos$$

CREATE PROCEDURE agregar_indice_documentos()
BEGIN
    DECLARE indice_existe INT DEFAULT 0;
    
    -- Verificar si el índice ya existe
    SELECT COUNT(*) INTO indice_existe
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'documentos'
    AND index_name = 'idx_item_mes_ano';
    
    -- Si no existe, crearlo
    IF indice_existe = 0 THEN
        ALTER TABLE `documentos` 
        ADD INDEX `idx_item_mes_ano` (`item_id`, `mes`, `ano`);
        SELECT 'Índice idx_item_mes_ano creado exitosamente' AS Resultado;
    ELSE
        SELECT 'Índice idx_item_mes_ano ya existe, no se creó' AS Resultado;
    END IF;
END$$

DELIMITER ;

-- Ejecutar el procedimiento
CALL agregar_indice_documentos();

-- Eliminar el procedimiento después de usarlo
DROP PROCEDURE IF EXISTS agregar_indice_documentos;

-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
-- 
-- FLUJO DE REVISIÓN (cuando está activado):
-- ========================================
-- 1. Cargador sube documento → estado normal
-- 2. Revisor puede:
--    a) APROBAR → estado 'aprobado' → Publicador PUEDE cargar verificador
--    b) OBSERVAR → estado 'observado' → Publicador NO PUEDE cargar verificador
-- 3. Si no ha sido revisado → Publicador PUEDE cargar verificador (opcional)
-- 
-- COMPORTAMIENTO:
-- ===============
-- - Si activar_revision_previa = 0: Sistema funciona como siempre (sin cambios)
-- - Si activar_revision_previa = 1: Se activa la capa de revisión
-- - Los documentos observados deben corregirse y re-aprobarse
-- - El revisor NO bloquea, solo informa (excepto cuando observa)
-- 
-- ============================================================================
