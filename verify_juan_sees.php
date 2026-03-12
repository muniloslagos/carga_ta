<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'classes/Documento.php';

$db = new Database();
$c = $db->getConnection();
$doc = new Documento($c);

echo "=== VERIFICACIÓN: JUAN VE EL DOCUMENTO APROBADO ===\n\n";

// Item 20: Libro diario municipal, mes 11 (noviembre)
$result = $doc->getByItemFollowUpAprobados(20, 11, 2025);

echo "Documentos APROBADOS para Item 20 - Mes 11/2025:\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "✓ Documento ID {$row['documento_id']}: {$row['titulo']}\n";
        echo "  Usuario ID: {$row['usuario_id']}\n";
        echo "  Archivo: {$row['archivo']}\n";
    }
    echo "\n✓ Juan Fica (Publicador) ya puede verlo\n";
} else {
    echo "✗ No hay documentos aprobados\n";
}

?>
