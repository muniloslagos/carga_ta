<?php
/**
 * Script para crear la base de datos
 * Ejecutar en: http://localhost/cumplimiento/setup.php
 */

require_once __DIR__ . '/config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Crear base de datos
$sql_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if ($conn->query($sql_db) === TRUE) {
    echo "<p style='color:green;'>✓ Base de datos creada correctamente</p>";
} else {
    die("Error al crear la base de datos: " . $conn->error);
}

// Seleccionar base de datos
$conn->select_db(DB_NAME);

// Crear tabla de direcciones
$sql_direcciones = "CREATE TABLE IF NOT EXISTS direcciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    descripcion TEXT,
    activa BOOLEAN DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_direcciones) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'direcciones' creada correctamente</p>";
} else {
    die("Error al crear tabla 'direcciones': " . $conn->error);
}

// Crear tabla de usuarios
$sql_usuarios = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    perfil ENUM('administrativo', 'director_revisor', 'cargador_informacion', 'publicador') NOT NULL,
    direccion_id INT,
    activo BOOLEAN DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (direccion_id) REFERENCES direcciones(id) ON DELETE SET NULL,
    UNIQUE KEY email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_usuarios) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'usuarios' creada correctamente</p>";
} else {
    die("Error al crear tabla 'usuarios': " . $conn->error);
}

// Crear tabla de items de transparencia
$sql_items = "CREATE TABLE IF NOT EXISTS items_transparencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numeracion VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    direccion_id INT,
    periodicidad ENUM('mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia') NOT NULL,
    activo BOOLEAN DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (direccion_id) REFERENCES direcciones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_items) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'items_transparencia' creada correctamente</p>";
} else {
    die("Error al crear tabla 'items_transparencia': " . $conn->error);
}

// Crear tabla de asignación de usuarios a items
$sql_item_usuarios = "CREATE TABLE IF NOT EXISTS item_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items_transparencia(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (item_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_item_usuarios) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'item_usuarios' creada correctamente</p>";
} else {
    die("Error al crear tabla 'item_usuarios': " . $conn->error);
}

// Crear tabla de documentos
$sql_documentos = "CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    archivo VARCHAR(255) NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    comentarios_revision TEXT,
    revisado_por INT,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_revision TIMESTAMP NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items_transparencia(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (revisado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_documentos) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'documentos' creada correctamente</p>";
} else {
    die("Error al crear tabla 'documentos': " . $conn->error);
}

// Crear tabla de logs
$sql_logs = "CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(255),
    descripcion TEXT,
    ip_address VARCHAR(45),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_logs) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'logs' creada correctamente</p>";
} else {
    die("Error al crear tabla 'logs': " . $conn->error);
}

// Insertar usuario administrador de ejemplo
$admin_email = 'admin@cumplimiento.local';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);

$sql_insert_admin = "INSERT IGNORE INTO usuarios (nombre, email, password, perfil, activo) 
                     VALUES ('Administrador', '$admin_email', '$admin_password', 'administrativo', 1)";

if ($conn->query($sql_insert_admin) === TRUE) {
    echo "<p style='color:green;'>✓ Usuario administrador creado: $admin_email / admin123</p>";
} else {
    echo "<p style='color:orange;'>⚠ Usuario administrador ya existe</p>";
}

$conn->close();

echo "<hr>";
echo "<h2>✓ Base de datos configurada correctamente</h2>";
echo "<p><strong>Credenciales de acceso inicial:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@cumplimiento.local</li>";
echo "<li>Contraseña: admin123</li>";
echo "</ul>";
echo "<p><a href='login.php'>Ir a iniciar sesión</a></p>";
?>
