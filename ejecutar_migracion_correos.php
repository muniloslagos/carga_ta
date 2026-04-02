<?php
/**
 * Ejecutar migración de plantillas de correo
 * Crear tablas: plantillas_correo, historial_envios_correo
 */

require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== Migrando tablas de plantillas de correo ===\n\n";

// Leer archivo SQL
$sql_file = __DIR__ . '/sql/migration_plantillas_correo.sql';
if (!file_exists($sql_file)) {
    die("Error: No se encontró el archivo $sql_file\n");
}

$sql = file_get_contents($sql_file);

echo "Ejecutando migraciones...\n\n";

// Ejecutar usando multi_query
if ($conn->multi_query($sql)) {
    $exitosas = 0;
    $errores = 0;
    
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
        
        if ($conn->errno) {
            echo "✗ Error: " . $conn->error . "\n";
            $errores++;
        } else {
            echo "✓ Query ejecutada\n";
            $exitosas++;
        }
        
    } while ($conn->more_results() && $conn->next_result());
    
    echo "\n=== Resultado ===\n";
    echo "Exitosas: $exitosas\n";
    echo "Errores: $errores\n\n";
    
    if ($errores === 0) {
        echo "✓ Migración completada exitosamente\n";
    } else {
        echo "⚠ Migración completada con errores\n";
    }
} else {
    echo "✗ Error al ejecutar migración: " . $conn->error . "\n";
    exit(1);
}
} else {
    echo "⚠ Migración completada con errores\n";
}
