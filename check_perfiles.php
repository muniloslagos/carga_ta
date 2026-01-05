<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$connection = $db->getConnection();

echo "=== USUARIOS Y PERFILES ACTUALES ===\n\n";

$result = $connection->query('SELECT id, nombre, email, perfil FROM usuarios ORDER BY id');

while($row = $result->fetch_assoc()) {
    $perfil = $row['perfil'] ?? '(NULL)';
    echo "ID {$row['id']}: {$row['nombre']} ({$row['email']}) - Perfil: {$perfil}\n";
}

echo "\n=== ESTRUCTURA DE PERFILES NECESARIA ===\n";
echo "- administrador: Acceso total al panel admin\n";
echo "- administrativo: Carga información (como usuario regular)\n";
echo "- director_revisor: Revisión de documentos\n";
echo "- publicador: Carga verificadores\n";
?>
