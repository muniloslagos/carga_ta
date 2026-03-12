<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$connection = $db->getConnection();

$result = $connection->query('SELECT id, nombre, perfil FROM usuarios WHERE id=1');
$row = $result->fetch_assoc();

echo "ID 1:\n";
echo "Nombre: " . $row['nombre'] . "\n";
echo "Perfil value: [" . $row['perfil'] . "]\n";
echo "Perfil length: " . strlen($row['perfil']) . "\n";
echo "Perfil type: " . gettype($row['perfil']) . "\n";

if (empty($row['perfil'])) {
    echo "\nPerfil está vacío, actualizando...\n";
    $connection->query("UPDATE usuarios SET perfil = 'administrador' WHERE id = 1");
    
    $result2 = $connection->query('SELECT perfil FROM usuarios WHERE id=1');
    $row2 = $result2->fetch_assoc();
    echo "Nuevo perfil: [" . $row2['perfil'] . "]\n";
}
?>
