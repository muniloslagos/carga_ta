<?php
/**
 * Script para ejecutar la migración de configuración SMTP
 */
 
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== Migrando tabla de configuración SMTP ===\n\n";

// Leer el archivo SQL
$sql = file_get_contents(__DIR__ . '/sql/migration_smtp_config.sql');

// Ejecutar las consultas
$queries = explode(';', $sql);
$success = 0;
$errors = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    echo "Ejecutando query...\n";
    if ($conn->query($query)) {
        $success++;
        echo "✓ OK\n";
    } else {
        $errors++;
        echo "✗ ERROR: " . $conn->error . "\n";
    }
}

echo "\n=== Resultado ===\n";
echo "Exitosas: $success\n";
echo "Errores: $errors\n";

if ($errors === 0) {
    echo "\n✓ Migración completada exitosamente\n";
} else {
    echo "\n✗ Migración completada con errores\n";
}

$conn->close();
