-- Dump de Base de Datos - Sistema de Control de Transparencia Activa
-- Versión: 1.0.0
-- Fecha de Creación: Diciembre 2025

-- =====================================================
-- CREAR BASE DE DATOS
-- =====================================================

CREATE DATABASE IF NOT EXISTS cumplimiento_db 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cumplimiento_db;

-- =====================================================
-- TABLA: direcciones
-- =====================================================

CREATE TABLE IF NOT EXISTS `direcciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activa` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: usuarios
-- =====================================================

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `perfil` enum('administrativo','director_revisor','cargador_informacion','publicador') COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `direccion_id` (`direccion_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`direccion_id`) REFERENCES `direcciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: items_transparencia
-- =====================================================

CREATE TABLE IF NOT EXISTS `items_transparencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numeracion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `direccion_id` int(11) DEFAULT NULL,
  `periodicidad` enum('mensual','trimestral','semestral','anual','ocurrencia') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numeracion` (`numeracion`),
  KEY `direccion_id` (`direccion_id`),
  CONSTRAINT `items_transparencia_ibfk_1` FOREIGN KEY (`direccion_id`) REFERENCES `direcciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: item_usuarios
-- =====================================================

CREATE TABLE IF NOT EXISTS `item_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`item_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `item_usuarios_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items_transparencia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_usuarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: documentos
-- =====================================================

CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `comentarios_revision` text COLLATE utf8mb4_unicode_ci,
  `revisado_por` int(11) DEFAULT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_revision` timestamp NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `revisado_por` (`revisado_por`),
  CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items_transparencia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_ibfk_3` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: logs
-- =====================================================

CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR USUARIO ADMINISTRATIVO POR DEFECTO
-- =====================================================

INSERT IGNORE INTO `usuarios` (
  `nombre`, 
  `email`, 
  `password`, 
  `perfil`, 
  `activo`
) VALUES (
  'Administrador',
  'admin@cumplimiento.local',
  '$2y$10$YmlnIGZ1bmN0aW9uIHBhc3N3b3JkX2hhc2ggYXJndW1lbnRzIGNvbWVzIGhlcmU=',
  'administrativo',
  1
);

-- =====================================================
-- CREAR ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================

CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_perfil ON usuarios(perfil);
CREATE INDEX idx_usuarios_activo ON usuarios(activo);
CREATE INDEX idx_items_periodicidad ON items_transparencia(periodicidad);
CREATE INDEX idx_items_activo ON items_transparencia(activo);
CREATE INDEX idx_documentos_estado ON documentos(estado);
CREATE INDEX idx_documentos_fecha ON documentos(fecha_subida);
CREATE INDEX idx_logs_fecha ON logs(fecha);
CREATE INDEX idx_logs_usuario ON logs(usuario_id);

-- =====================================================
-- FIN DEL DUMP
-- =====================================================
-- Fecha: Diciembre 2025
-- Sistema: Control de Transparencia Activa v1.0.0
-- =====================================================
