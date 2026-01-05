<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$c = $db->getConnection();

echo "=== APROBANDO DOCUMENTO ===\n\n";

// Aprobar documento ID 15
$result = $c->query("UPDATE documentos SET estado = 'aprobado' WHERE id = 15");

if ($result) {
    echo "✓ Documento ID 15 aprobado exitosamente\n\n";
    
    // Verificar
    $check = $c->query("SELECT id, titulo, estado FROM documentos WHERE id = 15");
    $doc = $check->fetch_assoc();
    
    echo "Estado actual:\n";
    echo "  ID: {$doc['id']}\n";
    echo "  Título: {$doc['titulo']}\n";
    echo "  Estado: {$doc['estado']}\n\n";
    
    echo "Juan Fica (Publicador) ahora puede:\n";
    echo "  1. Ver el documento en /admin/publicador/\n";
    echo "  2. Cargar la imagen de verificación\n";
} else {
    echo "✗ Error al aprobar: " . $c->error . "\n";
}

?>
