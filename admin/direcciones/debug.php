<?php
// Activar errores temporalmente
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico - Direcciones</h1>";

// 1. Verificar includes
echo "<h2>1. Verificando archivos...</h2>";
$files_to_check = [
    '../../config/config.php',
    '../../config/Database.php',
    '../../classes/Direccion.php'
];

foreach ($files_to_check as $file) {
    echo $file . ": ";
    if (file_exists($file)) {
        echo "✓ Existe<br>";
    } else {
        echo "✗ NO EXISTE<br>";
    }
}

// 2. Intentar incluir config
echo "<h2>2. Cargando configuración...</h2>";
try {
    require_once '../../config/config.php';
    echo "✓ Config cargado<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "SITE_URL: " . SITE_URL . "<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    die();
}

// 3. Intentar conectar a base de datos
echo "<h2>3. Conectando a base de datos...</h2>";
try {
    require_once '../../config/Database.php';
    $database = new Database();
    $db = $database;
    $conn = $db->getConnection();
    echo "✓ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    die();
}

// 4. Verificar tabla direcciones
echo "<h2>4. Verificando tabla direcciones...</h2>";
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM direcciones");
    $row = $result->fetch_assoc();
    echo "✓ Total de direcciones: " . $row['total'] . "<br>";
    
    $result = $conn->query("SELECT COUNT(*) as total FROM direcciones WHERE activa = 1");
    $row = $result->fetch_assoc();
    echo "✓ Direcciones activas: " . $row['total'] . "<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
}

// 5. Probar clase Direccion
echo "<h2>5. Probando clase Direccion...</h2>";
try {
    require_once '../../classes/Direccion.php';
    $direccionClass = new Direccion($conn);
    echo "✓ Clase instanciada<br>";
    
    $direcciones = $direccionClass->getAll();
    echo "✓ Método getAll() ejecutado<br>";
    echo "Tipo de retorno: " . gettype($direcciones) . "<br>";
    
    if ($direcciones) {
        echo "Número de filas: " . $direcciones->num_rows . "<br>";
        
        echo "<h3>Direcciones encontradas:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Activa</th></tr>";
        while ($dir = $direcciones->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $dir['id'] . "</td>";
            echo "<td>" . htmlspecialchars($dir['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($dir['descripcion'] ?? '') . "</td>";
            echo "<td>" . ($dir['activa'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "✗ getAll() retornó FALSE o NULL<br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
}

echo "<h2>Diagnóstico completado</h2>";
?>
