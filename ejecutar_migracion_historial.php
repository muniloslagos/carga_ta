<?php
/**
 * MIGRACIÓN: Agregar tabla historial
 * Este script crea la tabla historial si no existe
 * 
 * EJECUTAR:
 * - Local: http://localhost/cumplimiento/ejecutar_migracion_historial.php
 * - Producción: http://app.muniloslagos.cl/carga_ta/ejecutar_migracion_historial.php
 */

require_once __DIR__ . '/config/config.php';

echo "<h1>Migración: Agregar tabla historial</h1>";
echo "<hr>";

// Conectar a base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<p style='color:red;'><strong>❌ Error de conexión:</strong> " . $conn->connect_error . "</p>");
}

echo "<p>✅ <strong>Conectado a base de datos:</strong> " . DB_NAME . "</p>";

// Verificar si la tabla ya existe
$check_table = $conn->query("SHOW TABLES LIKE 'historial'");

if ($check_table && $check_table->num_rows > 0) {
    echo "<p>⚠️ <strong>La tabla 'historial' ya existe.</strong> No se requiere migración.</p>";
    
    // Mostrar estructura de la tabla
    $estructura = $conn->query("DESCRIBE historial");
    if ($estructura) {
        echo "<h3>Estructura actual de la tabla:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($col = $estructura->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $col['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<p>🔧 <strong>Creando tabla 'historial'...</strong></p>";
    
    // SQL para crear la tabla
    $sql = "CREATE TABLE IF NOT EXISTS `historial` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `item_id` int(11) NOT NULL,
      `documento_id` int(11) DEFAULT NULL,
      `usuario_id` int(11) NOT NULL,
      `tipo` varchar(50) NOT NULL COMMENT 'documento_cargado, verificador_agregado, estado_cambio, etc.',
      `descripcion` text NOT NULL COMMENT 'Descripción breve del movimiento',
      `detalle` text DEFAULT NULL COMMENT 'Detalles adicionales del movimiento',
      `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `item_id` (`item_id`),
      KEY `documento_id` (`documento_id`),
      KEY `usuario_id` (`usuario_id`),
      KEY `idx_historial_fecha` (`fecha`),
      CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items_transparencia` (`id`) ON DELETE CASCADE,
      CONSTRAINT `historial_ibfk_2` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
      CONSTRAINT `historial_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'><strong>✅ ¡Tabla 'historial' creada exitosamente!</strong></p>";
        
        // Verificar que se creó correctamente
        $check_again = $conn->query("SHOW TABLES LIKE 'historial'");
        if ($check_again && $check_again->num_rows > 0) {
            echo "<p>✅ <strong>Verificación:</strong> La tabla existe en la base de datos.</p>";
            
            // Mostrar estructura
            $estructura = $conn->query("DESCRIBE historial");
            if ($estructura) {
                echo "<h3>Estructura de la tabla creada:</h3>";
                echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
                echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                while ($col = $estructura->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $col['Field'] . "</td>";
                    echo "<td>" . $col['Type'] . "</td>";
                    echo "<td>" . $col['Null'] . "</td>";
                    echo "<td>" . $col['Key'] . "</td>";
                    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . $col['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    } else {
        echo "<p style='color:red;'><strong>❌ Error al crear tabla:</strong> " . $conn->error . "</p>";
    }
}

$conn->close();

echo "<hr>";
echo "<h2>Siguiente Paso</h2>";
echo "<p>1. Probar carga de documento: <a href='usuario/dashboard.php'>Ir al Dashboard</a></p>";
echo "<p>2. Verificar que el historial se registra correctamente</p>";
echo "<p>3. Si todo funciona, <strong>eliminar este archivo</strong> por seguridad</p>";

echo "<br><p><small><strong>Fecha de ejecución:</strong> " . date('Y-m-d H:i:s') . "</small></p>";
?>
