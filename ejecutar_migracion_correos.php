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

// Separar queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

$exitosas = 0;
$errores = 0;

foreach ($queries as $query) {
    if (empty($query) || strpos($query, '--') === 0) {
        continue;
    }
    
    echo "Ejecutando query...\n";
    
    if ($conn->query($query)) {
        echo "✓ OK\n";
        $exitosas++;
    } else {
        echo "✗ Error: " . $conn->error . "\n";
        $errores++;
    }
}

echo "\n=== Resultado ===\n";
echo "Exitosas: $exitosas\n";
echo "Errores: $errores\n\n";

if ($errores === 0) {
    echo "✓ Migración completada exitosamente\n";
} else {
    echo "⚠ Migración completada con errores\n";
}
