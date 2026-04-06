<?php
/**
 * Diagnóstico de directores - Verificar correos
 */
require_once dirname(__DIR__) . '/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Diagnóstico de Directores</h2>";

// 1. Total de directores
$total = $conn->query("SELECT COUNT(*) as total FROM directores");
$total_row = $total->fetch_assoc();
echo "<p><strong>Total de directores:</strong> {$total_row['total']}</p>";

// 2. Directores activos
$activos = $conn->query("SELECT COUNT(*) as total FROM directores WHERE activo = 1");
$activos_row = $activos->fetch_assoc();
echo "<p><strong>Directores activos:</strong> {$activos_row['total']}</p>";

// 3. Directores con correo
$con_correo = $conn->query("SELECT COUNT(*) as total FROM directores WHERE correo IS NOT NULL AND correo != ''");
$con_correo_row = $con_correo->fetch_assoc();
echo "<p><strong>Directores con correo (no vacío):</strong> {$con_correo_row['total']}</p>";

// 4. Directores activos CON correo (el query que usa la página de correos)
$query_correos = $conn->query("SELECT COUNT(*) as total FROM directores WHERE activo = 1 AND correo IS NOT NULL AND correo != ''");
$query_correos_row = $query_correos->fetch_assoc();
echo "<p><strong>Directores activos CON correo:</strong> {$query_correos_row['total']}</p>";

// 5. Lista de todos los directores
echo "<h3>Lista de Directores:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombres</th><th>Apellidos</th><th>Correo</th><th>Activo</th></tr>";

$directores = $conn->query("SELECT * FROM directores ORDER BY apellidos, nombres");
while ($dir = $directores->fetch_assoc()) {
    $correo_val = empty($dir['correo']) ? '<em style="color:red;">VACÍO</em>' : htmlspecialchars($dir['correo']);
    $activo_val = $dir['activo'] ? '✓' : '✗';
    echo "<tr>
        <td>{$dir['id']}</td>
        <td>{$dir['nombres']}</td>
        <td>{$dir['apellidos']}</td>
        <td>{$correo_val}</td>
        <td>{$activo_val}</td>
    </tr>";
}
echo "</table>";

echo "<h3>Solución:</h3>";
echo "<p>Si ves directores sin correo, debes agregarlos en:</p>";
echo "<p><a href='admin/directores/'>Admin → Directores → Editar</a></p>";

$conn->close();
?>
