<?php
echo "<h3>Verificación de Código Publicador</h3>";
echo "<hr>";

$archivo = 'admin/publicador/index.php';

if (!file_exists($archivo)) {
    die("ERROR: No existe $archivo");
}

$contenido = file_get_contents($archivo);

// Buscar la sección de sinMovimientoCache
echo "<strong>1. ¿Tiene pre-fetch de sinMovimientoCache?</strong><br>";
if (strpos($contenido, 'sinMovimientoCache') !== false) {
    echo "✓ SÍ - encontrado<br>";
    
    // Contar cuántas veces aparece
    $count = substr_count($contenido, 'sinMovimientoCache');
    echo "Aparece $count veces en el archivo<br>";
} else {
    echo "✗ NO - NO encontrado (PROBLEMA!)<br>";
}
echo "<hr>";

// Buscar la lógica de tieneSinMovimiento
echo "<strong>2. ¿Tiene lógica de tieneSinMovimiento?</strong><br>";
if (strpos($contenido, 'tieneSinMovimiento') !== false) {
    echo "✓ SÍ - encontrado<br>";
    $count = substr_count($contenido, 'tieneSinMovimiento');
    echo "Aparece $count veces en el archivo<br>";
} else {
    echo "✗ NO - NO encontrado (PROBLEMA!)<br>";
}
echo "<hr>";

// Buscar el badge "Sin Movimiento"
echo "<strong>3. ¿Tiene badge 'Sin Movimiento'?</strong><br>";
if (strpos($contenido, 'Sin Movimiento') !== false) {
    echo "✓ SÍ - encontrado<br>";
} else {
    echo "✗ NO - NO encontrado (PROBLEMA!)<br>";
}
echo "<hr>";

// Buscar el modal modalVerSinMovimiento
echo "<strong>4. ¿Tiene modalVerSinMovimiento?</strong><br>";
if (strpos($contenido, 'modalVerSinMovimiento') !== false) {
    echo "✓ SÍ - encontrado<br>";
} else {
    echo "✗ NO - NO encontrado (PROBLEMA!)<br>";
}
echo "<hr>";

// Mostrar fecha de última modificación
echo "<strong>5. Fecha de última modificación del archivo:</strong><br>";
echo date('d/m/Y H:i:s', filemtime($archivo)) . "<br>";
echo "<hr>";

// Mostrar tamaño del archivo
echo "<strong>6. Tamaño del archivo:</strong><br>";
echo number_format(filesize($archivo)) . " bytes<br>";
echo "<hr>";

echo "<strong>Conclusión:</strong><br>";
if (strpos($contenido, 'sinMovimientoCache') !== false && strpos($contenido, 'tieneSinMovimiento') !== false) {
    echo "<span style='color: green; font-weight: bold;'>✓ El código está actualizado correctamente</span><br>";
    echo "<br><strong>El problema es CACHE DE PHP (OPcache)</strong><br>";
    echo "<br><strong>Solución:</strong><br>";
    echo "1. Reiniciar PHP-FPM desde cPanel<br>";
    echo "2. O ejecutar: <code>touch admin/publicador/index.php</code> por SSH<br>";
    echo "3. O esperar ~5 minutos para que expire el cache automáticamente<br>";
} else {
    echo "<span style='color: red; font-weight: bold;'>✗ El código NO está actualizado</span><br>";
    echo "Necesitas hacer git pull nuevamente<br>";
}
?>
