<?php
require_once 'config/config.php';
require_once 'config/Database.php';
$db = new Database();
$conn = $db->getConnection();
$r = $conn->query("SELECT * FROM usuarios WHERE perfil='auditor'");
echo "<pre>";
while($row = $r->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
// También buscar por nombre
$r2 = $conn->query("SELECT * FROM usuarios ORDER BY id");
echo "<h3>Todos los usuarios:</h3><pre>";
while($row = $r2->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
