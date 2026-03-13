<?php
/**
 * Script para remover restricción UNIQUE de numeracion
 * Los números de items pueden repetirse
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Removiendo restricción UNIQUE de items_transparencia.numeracion...\n\n";

try {
    // Verificar si existe la restricción
    $checkQuery = "SHOW INDEX FROM items_transparencia WHERE Key_name = 'numeracion'";
    $result = $conn->query($checkQuery);
    
    if ($result->num_rows > 0) {
        // Eliminar la restricción UNIQUE
        $sql = "ALTER TABLE items_transparencia DROP INDEX numeracion";
        
        if ($conn->query($sql)) {
            echo "✓ Restricción UNIQUE removida exitosamente\n";
            echo "✓ Ahora los números de items pueden repetirse\n";
        } else {
            echo "✗ Error al remover restricción: " . $conn->error . "\n";
        }
    } else {
        echo "✓ La restricción UNIQUE ya no existe\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nProceso completado.\n";
?>
