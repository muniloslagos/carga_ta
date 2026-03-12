<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$c = $db->getConnection();

echo "=== SOLUCIÓN: REORGANIZAR PERFILES CON ENUM EXISTENTES ===\n\n";

// El ENUM actual solo acepta: administrativo, director_revisor, cargador_informacion, publicador
// Por lo tanto:
// - administrativo → Admin (acceso panel admin)
// - director_revisor → Director revisor
// - cargador_informacion → Usuarios que cargan información
// - publicador → Publicador

echo "ESTRUCTURA ACTUAL DEL ENUM EN BD:\n";
echo "- administrativo (ID 1: Admin)\n";
echo "- director_revisor (para futuros directores)\n";
echo "- cargador_informacion (usuarios regulares como Marianela, Juan)\n";
echo "- publicador (para verificadores)\n\n";

// Actualizar perfiles
echo "APLICANDO CAMBIOS:\n";

// ID 1 → administrativo (ya debería estarlo)
$c->query("UPDATE usuarios SET perfil = 'administrativo' WHERE id = 1");
echo "✓ ID 1 (Administrador) → administrativo\n";

// ID 6 (Marianela) → cargador_informacion
$c->query("UPDATE usuarios SET perfil = 'cargador_informacion' WHERE id = 6");
echo "✓ ID 6 (Marianela) → cargador_informacion\n";

// ID 8 (Juan) → cargador_informacion
$c->query("UPDATE usuarios SET perfil = 'cargador_informacion' WHERE id = 8");
echo "✓ ID 8 (Juan) → cargador_informacion\n";

echo "\n=== USUARIOS FINALES ===\n\n";

$result = $c->query('SELECT id, nombre, email, perfil FROM usuarios ORDER BY id');

while($row = $result->fetch_assoc()) {
    $perfil = $row['perfil'] ?: '(NULL)';
    echo "ID {$row['id']}: {$row['nombre']}\n  Email: {$row['email']}\n  Perfil: {$perfil}\n\n";
}
?>
