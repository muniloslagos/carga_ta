<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test 1: Inicio</h1>";

echo "<h2>Test 2: Incluyendo config...</h2>";
require_once '../../config/config.php';
echo "✓ Config OK<br>";

echo "<h2>Test 3: Incluyendo Database...</h2>";
require_once '../../config/Database.php';
$database = new Database();
$db = $database;
echo "✓ Database OK<br>";

echo "<h2>Test 4: Iniciando sesión...</h2>";
session_start();
echo "✓ Session OK<br>";
echo "Usuario en sesión: " . ($_SESSION['usuario_id'] ?? 'NO HAY') . "<br>";
echo "Perfil: " . ($_SESSION['perfil'] ?? 'NO HAY') . "<br>";

echo "<h2>Test 5: Incluyendo check_auth...</h2>";
try {
    require_once '../../includes/check_auth.php';
    echo "✓ check_auth incluido<br>";
} catch (Exception $e) {
    echo "✗ ERROR en check_auth: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 6: Verificando perfil...</h2>";
try {
    require_role('administrativo');
    echo "✓ Perfil verificado<br>";
} catch (Exception $e) {
    echo "✗ ERROR en require_role: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 7: Incluyendo header...</h2>";
try {
    require_once '../../includes/header.php';
    echo "✓ Header incluido<br>";
} catch (Exception $e) {
    echo "✗ ERROR en header: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 8: Incluyendo Direccion class...</h2>";
try {
    require_once '../../classes/Direccion.php';
    echo "✓ Clase Direccion incluida<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 9: Instanciando clase...</h2>";
try {
    $direccionClass = new Direccion($db->getConnection());
    echo "✓ Clase instanciada<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 10: Obteniendo direcciones...</h2>";
try {
    $direcciones = $direccionClass->getAll();
    echo "✓ getAll() ejecutado<br>";
    echo "Direcciones encontradas: " . $direcciones->num_rows . "<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>✅ TODOS LOS TESTS PASARON</h2>";
echo "<p>El problema NO está en los includes ni en la clase. Revisar lógica del index.php</p>";

require_once '../../includes/footer.php';
?>
