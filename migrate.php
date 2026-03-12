<?php
/**
 * Script de migración para agregar plazos internos y fechas de carga
 * Ejecutar en: http://localhost/cumplimiento/migrate.php
 */

require_once __DIR__ . '/config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$conn->select_db(DB_NAME);

echo "<h2>Ejecutando Migración...</h2>";

// 1. Crear tabla item_plazos - Almacena plazo interno y fecha de carga al portal
$sql_item_plazos = "CREATE TABLE IF NOT EXISTS item_plazos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    ano INT NOT NULL,
    mes INT NOT NULL,
    plazo_interno DATE,
    fecha_carga_portal DATE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_item_mes (item_id, ano, mes),
    FOREIGN KEY (item_id) REFERENCES items_transparencia(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_item_plazos) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'item_plazos' creada correctamente</p>";
} else {
    die("Error al crear tabla 'item_plazos': " . $conn->error);
}

// 2. Crear tabla documento_seguimiento - Para rastrear envíos y cargas
$sql_doc_seguimiento = "CREATE TABLE IF NOT EXISTS documento_seguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    item_id INT NOT NULL,
    usuario_id INT NOT NULL,
    ano INT NOT NULL,
    mes INT NOT NULL,
    fecha_envio DATETIME,
    fecha_carga_portal DATETIME,
    estado ENUM('pendiente', 'enviado', 'cargado_portal', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items_transparencia(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_doc_seguimiento) === TRUE) {
    echo "<p style='color:green;'>✓ Tabla 'documento_seguimiento' creada correctamente</p>";
} else {
    die("Error al crear tabla 'documento_seguimiento': " . $conn->error);
}

echo "<h2 style='color:green;'>✓ Migración completada exitosamente</h2>";
echo "<p><a href='admin/items/index.php'>Ir a Gestión de Items</a></p>";

$conn->close();
?>
