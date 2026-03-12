<?php
// Corregir perfil de Marianela Jaramillo
require_once 'config/config.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Actualizar Marianela a 'cargador_informacion' (usuario normal)
$sql = "UPDATE usuarios SET perfil = 'cargador_informacion' WHERE id = 6";

if ($db->query($sql)) {
    echo "✓ Marianela Jaramillo actualizada a perfil 'cargador_informacion'<br>";
} else {
    echo "✗ Error: " . $db->error;
}

// Ver resultado
$sql_view = "SELECT id, nombre, email, perfil FROM usuarios WHERE id IN (1, 6)";
$result = $db->query($sql_view);

echo "<h3>Usuarios Actualizados</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Perfil</th><th>Descripción</th></tr>";

while ($row = $result->fetch_assoc()) {
    $desc = '';
    if ($row['perfil'] === 'administrativo') {
        $desc = 'Acceso total a administración';
    } elseif ($row['perfil'] === 'cargador_informacion') {
        $desc = 'Carga documentos en dashboard';
    } elseif ($row['perfil'] === 'director_revisor') {
        $desc = 'Director con acceso administrativo';
    } elseif ($row['perfil'] === 'publicador') {
        $desc = 'Carga verificadores';
    }
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nombre']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['perfil']}</td>";
    echo "<td>{$desc}</td>";
    echo "</tr>";
}
echo "</table>";

$db->close();
?>
