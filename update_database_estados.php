<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$c = $db->getConnection();

echo "=== ACTUALIZACIÓN DE BASE DE DATOS ===\n\n";

// Actualizar enum de estados
$sql = "ALTER TABLE documentos MODIFY COLUMN estado ENUM('Cargado', 'Publicado', 'pendiente', 'aprobado', 'rechazado') DEFAULT 'Cargado'";

if ($c->query($sql)) {
    echo "✓ Enum de estados actualizado correctamente\n";
    echo "  Nuevos valores: Cargado, Publicado, pendiente, aprobado, rechazado\n\n";
} else {
    echo "✗ Error al actualizar enum: " . $c->error . "\n\n";
}

// Listar documentos actuales
echo "DOCUMENTOS ACTUALES:\n\n";
$result = $c->query("SELECT id, titulo, usuario_id, item_id, estado, fecha_subida FROM documentos ORDER BY id DESC LIMIT 10");

while ($row = $result->fetch_assoc()) {
    echo "ID {$row['id']}: {$row['titulo']}\n";
    echo "  Estado actual: {$row['estado']}\n";
}

echo "\n✓ Base de datos lista para usar los nuevos estados\n";

?>
