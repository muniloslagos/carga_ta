<?php
echo "<h3>Limpieza de Cache PHP</h3>";
echo "<hr>";

// 1. Touch del archivo principal
$archivo = 'admin/publicador/index.php';
if (file_exists($archivo)) {
    touch($archivo);
    clearstatcache();
    echo "✓ Touch realizado en: $archivo<br>";
    echo "Nueva fecha modificación: " . date('d/m/Y H:i:s', filemtime($archivo)) . "<br>";
} else {
    echo "✗ No se encontró: $archivo<br>";
}

echo "<hr>";

// 2. Limpiar OPcache si está disponible
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    if ($result) {
        echo "✓ OPcache limpiado exitosamente<br>";
    } else {
        echo "⚠ No se pudo limpiar OPcache (puede requerir permisos)<br>";
    }
} else {
    echo "ℹ OPcache no está disponible o no está habilitado<br>";
}

echo "<hr>";

// 3. Invalidar cache de realpath
clearstatcache(true);
echo "✓ Cache de realpath limpiado<br>";

echo "<hr>";
echo "<strong>Cache limpiado. Ahora recarga el publicador:</strong><br>";
echo "<a href='admin/publicador/' target='_blank' style='display: inline-block; background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Abrir Publicador</a><br>";
echo "<br><small>Presiona Ctrl+Shift+R en el navegador para recargar sin cache del navegador</small>";
?>
