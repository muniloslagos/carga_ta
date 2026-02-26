<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Item.php';

$db = new Database();
$itemClass = new Item($db->getConnection());

echo "<h1>Debug Dashboard</h1>";

// Verificar sesión
echo "<h2>1. Sesión</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$user_id = $_SESSION['user']['id'] ?? null;
$perfil = $_SESSION['profile'] ?? null;

echo "<p>User ID: $user_id</p>";
echo "<p>Perfil: $perfil</p>";

// Verificar items totales
echo "<h2>2. Items totales en la BD</h2>";
$sql = "SELECT COUNT(*) as total FROM items_transparencia WHERE activo = 1";
$result = $db->getConnection()->query($sql);
$row = $result->fetch_assoc();
echo "<p>Total items activos: " . $row['total'] . "</p>";

// Verificar items del usuario
echo "<h2>3. Items asignados al usuario</h2>";
$sql = "SELECT i.* FROM items_transparencia i 
        INNER JOIN usuarios u ON u.direccion_id = i.direccion_id 
        WHERE u.id = ? AND i.activo = 1";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<p>Total items asignados: " . $result->num_rows . "</p>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Periodicidad</th><th>Dirección</th></tr>";
    while ($item = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $item['id'] . "</td>";
        echo "<td>" . htmlspecialchars($item['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($item['periodicidad']) . "</td>";
        echo "<td>" . $item['direccion_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No hay items asignados a este usuario</p>";
}

// Verificar dirección del usuario
echo "<h2>4. Dirección del usuario</h2>";
$sql = "SELECT direccion_id FROM usuarios WHERE id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
echo "<p>Dirección ID: " . ($userData['direccion_id'] ?? 'NULL') . "</p>";

// Si no tiene dirección
if (!$userData['direccion_id']) {
    echo "<p style='color:red; font-weight:bold;'>ERROR: El usuario NO tiene dirección asignada</p>";
    echo "<p>Solución: Asignar el usuario a una dirección desde el panel administrativo</p>";
}

echo "<h2>Diagnóstico completado</h2>";
?>
