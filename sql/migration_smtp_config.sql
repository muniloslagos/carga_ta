-- Tabla de configuración SMTP para el sistema de notificaciones
CREATE TABLE IF NOT EXISTS configuracion_smtp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(255) NOT NULL DEFAULT 'smtp.gmail.com',
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_usuario VARCHAR(255) NOT NULL DEFAULT '',
    smtp_password VARCHAR(255) NOT NULL DEFAULT '',
    smtp_encriptacion ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
    smtp_de_correo VARCHAR(255) NOT NULL DEFAULT '',
    smtp_de_nombre VARCHAR(255) NOT NULL DEFAULT 'Sistema de Transparencia',
    smtp_activo TINYINT(1) DEFAULT 0,
    smtp_verificado TINYINT(1) DEFAULT 0,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modificado_por INT,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración inicial
INSERT INTO configuracion_smtp (
    smtp_host, 
    smtp_port, 
    smtp_usuario, 
    smtp_password, 
    smtp_encriptacion, 
    smtp_de_correo, 
    smtp_de_nombre, 
    smtp_activo
) VALUES (
    'smtp.gmail.com',
    587,
    '',
    '',
    'tls',
    '',
    'Sistema de Transparencia Activa - Municipalidad de Los Lagos',
    0
);
