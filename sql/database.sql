-- =====================================================
-- Sistema de Numeración - Municipalidad de Los Lagos
-- Base de Datos MySQL
-- =====================================================

CREATE DATABASE IF NOT EXISTS numeracion_muni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE numeracion_muni;

-- -----------------------------------------------------
-- Tabla: usuarios
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'girador', 'emisor') NOT NULL DEFAULT 'girador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: categorias
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    prefijo VARCHAR(10) NOT NULL UNIQUE,
    descripcion TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    activa TINYINT(1) NOT NULL DEFAULT 1,
    numero_actual INT NOT NULL DEFAULT 0,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: modulos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL UNIQUE,
    nombre_funcionario VARCHAR(100),
    usuario_id INT,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    estado ENUM('disponible', 'ocupado', 'pausado', 'inactivo') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: modulo_categorias (relación muchos a muchos)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS modulo_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo_id INT NOT NULL,
    categoria_id INT NOT NULL,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_modulo_categoria (modulo_id, categoria_id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: turnos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    numero_completo VARCHAR(20) NOT NULL,
    categoria_id INT NOT NULL,
    modulo_id INT,
    estado ENUM('esperando', 'llamado', 'atendiendo', 'atendido', 'cancelado', 'no_presentado') DEFAULT 'esperando',
    fecha DATE NOT NULL,
    hora_emision TIME NOT NULL,
    hora_llamado TIME,
    hora_atencion_inicio TIME,
    hora_atencion_fin TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL,
    INDEX idx_fecha_estado (fecha, estado),
    INDEX idx_categoria_fecha (categoria_id, fecha)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: llamados_actuales (para pantalla)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS llamados_actuales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NOT NULL,
    modulo_id INT NOT NULL,
    categoria_id INT NOT NULL,
    numero_completo VARCHAR(20) NOT NULL,
    nombre_funcionario VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: historial_llamados (últimos llamados para pantalla)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS historial_llamados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NOT NULL,
    modulo_id INT NOT NULL,
    categoria_id INT NOT NULL,
    numero_completo VARCHAR(20) NOT NULL,
    nombre_funcionario VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: configuracion
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: perfiles_pantalla
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS perfiles_pantalla (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    categorias_ids TEXT,
    mostrar_todas TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES
('admin', '$2y$10$8K1p/a0dL1LXMw8i0FzQeOQZ0k5s5p5s5p5s5p5s5p5s5p5s5p5s5', 'Administrador', 'admin');

-- Módulos iniciales (10 módulos)
INSERT INTO modulos (numero, nombre_funcionario, activo) VALUES
(1, 'Funcionario 1', 1),
(2, 'Funcionario 2', 1),
(3, 'Funcionario 3', 1),
(4, 'Funcionario 4', 0),
(5, 'Funcionario 5', 0),
(6, 'Funcionario 6', 0),
(7, 'Funcionario 7', 0),
(8, 'Funcionario 8', 0),
(9, 'Funcionario 9', 0),
(10, 'Funcionario 10', 0);

-- Categorías iniciales
INSERT INTO categorias (nombre, prefijo, descripcion, color, activa, orden) VALUES
('Permisos de Circulación', 'PC', 'Trámites de permisos de circulación vehicular', '#28a745', 1, 1),
('Asistencia Social', 'AS', 'Atención del departamento social', '#17a2b8', 1, 2),
('Atención Salud', 'SA', 'Servicios de salud municipal', '#dc3545', 1, 3),
('Tesorería', 'TE', 'Pagos y trámites de tesorería', '#ffc107', 0, 4);

-- Configuración inicial
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('nombre_municipalidad', 'Municipalidad de Los Lagos', 'Nombre de la municipalidad'),
('audio_activo', '1', 'Activar audio en pantalla'),
('tiempo_rellamado', '30', 'Segundos para re-llamar automáticamente'),
('mostrar_ultimos', '4', 'Cantidad de últimos llamados a mostrar'),
('voz_velocidad', '1', 'Velocidad de la voz (0.5 a 2)'),
('voz_tono', '1', 'Tono de la voz (0 a 2)');

-- Asignar categorías a módulos (por defecto todos los módulos atienden todas las categorías activas)
INSERT INTO modulo_categorias (modulo_id, categoria_id)
SELECT m.id, c.id FROM modulos m CROSS JOIN categorias c WHERE m.activo = 1 AND c.activa = 1;

-- Perfiles de pantalla
INSERT INTO perfiles_pantalla (nombre, slug, categorias_ids, mostrar_todas) VALUES
('General', 'general', NULL, 1),
('Permisos de Circulación', 'permisos', '1', 0),
('Asistencia Social', 'social', '2', 0),
('Salud', 'salud', '3', 0);
