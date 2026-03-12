<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$connection = $db->getConnection();

echo "=== ACTUALIZANDO PERFILES ===\n\n";

// 1. Cambiar ID 1 a 'administrador'
$query1 = "UPDATE usuarios SET perfil = 'administrador' WHERE id = 1";
if($connection->query($query1)) {
    echo "✓ ID 1 (Administrador) → perfil='administrador'\n";
} else {
    echo "✗ Error actualizando ID 1: " . $connection->error . "\n";
}

// 2. Cambiar ID 6 (Marianela) a 'cargador_informacion'
$query2 = "UPDATE usuarios SET perfil = 'cargador_informacion' WHERE id = 6";
if($connection->query($query2)) {
    echo "✓ ID 6 (Marianela) → perfil='cargador_informacion'\n";
} else {
    echo "✗ Error actualizando ID 6: " . $connection->error . "\n";
}

echo "\n=== USUARIOS DESPUÉS DE ACTUALIZACIÓN ===\n\n";

$result = $connection->query('SELECT id, nombre, email, perfil FROM usuarios ORDER BY id');

while($row = $result->fetch_assoc()) {
    $perfil = $row['perfil'] ?? '(NULL)';
    echo "ID {$row['id']}: {$row['nombre']} - Perfil: {$perfil}\n";
}

echo "\n✓ Perfiles reorganizados correctamente\n";
echo "- administrador: Acceso al panel admin\n";
echo "- cargador_informacion: Cargar documentos (usuarios regulares)\n";
?>
