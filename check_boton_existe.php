<?php
/**
 * Verificar si el botón "Eliminar" existe en admin/publicador/index.php
 */

$archivo = 'admin/publicador/index.php';
$rutaCompleta = __DIR__ . '/' . $archivo;

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Verificación Botón Eliminar</title>";
echo "<style>
body { font-family: monospace; margin: 20px; background: #f5f5f5; }
.ok { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { background: #e7f3ff; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; }
pre { background: #f8f9fa; padding: 15px; overflow-x: auto; border: 1px solid #ddd; }
</style></head><body>";

echo "<h1>🔍 Verificación: Botón Eliminar en index.php</h1>";
echo "<p><strong>Archivo:</strong> {$archivo}</p>";
echo "<hr>";

if (!file_exists($rutaCompleta)) {
    echo "<p class='error'>❌ ERROR: El archivo no existe en: {$rutaCompleta}</p>";
    echo "</body></html>";
    exit;
}

// Leer contenido del archivo
$contenido = file_get_contents($rutaCompleta);

// Buscar patrones específicos
$patronesABuscar = [
    'btn-danger.*Eliminar' => 'Botón "Eliminar" con clase btn-danger',
    'modalEliminarVerificador' => 'Modal de eliminación de verificador',
    'eliminarVerificador\(' => 'Función JavaScript eliminarVerificador()',
    'eliminar_verificador\.php' => 'Action del form hacia eliminar_verificador.php',
    'verificador_id.*name.*motivo' => 'Campos del formulario de eliminación'
];

echo "<h2>📋 Resultados de búsqueda:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; background: white;'>";
echo "<tr style='background: #f0f0f0;'><th>Patrón</th><th>Descripción</th><th>Estado</th><th>Líneas encontradas</th></tr>";

$todosCumplidos = true;

foreach ($patronesABuscar as $patron => $descripcion) {
    preg_match_all("/$patron/i", $contenido, $matches, PREG_OFFSET_CAPTURE);
    $encontrado = count($matches[0]) > 0;
    
    if (!$encontrado) {
        $todosCumplidos = false;
    }
    
    $estado = $encontrado ? "<span class='ok'>✅ ENCONTRADO</span>" : "<span class='error'>❌ NO ENCONTRADO</span>";
    $lineas = $encontrado ? count($matches[0]) . " coincidencia(s)" : "—";
    
    echo "<tr>";
    echo "<td><code>{$patron}</code></td>";
    echo "<td>{$descripcion}</td>";
    echo "<td>{$estado}</td>";
    echo "<td>{$lineas}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>📊 Diagnóstico Final:</h2>";

if ($todosCumplidos) {
    echo "<div class='info'>";
    echo "<h3 class='ok'>✅ EL ARCHIVO SÍ TIENE EL BOTÓN ELIMINAR</h3>";
    echo "<p>Todos los componentes necesarios están presentes en el archivo.</p>";
    echo "<p><strong>Si aún no ve el botón:</strong></p>";
    echo "<ol>";
    echo "<li>Limpie caché del navegador: <kbd>Ctrl + Shift + Delete</kbd></li>";
    echo "<li>O use modo incógnito: <kbd>Ctrl + Shift + N</kbd></li>";
    echo "<li>O pruebe en otro navegador</li>";
    echo "<li>Verifique que está en el mes/año correcto donde están los verificadores</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h3 class='error'>❌ EL ARCHIVO NO TIENE EL BOTÓN ELIMINAR (O ESTÁ INCOMPLETO)</h3>";
    echo "<p>Faltan algunos componentes. <strong>El archivo necesita ser actualizado.</strong></p>";
    echo "<p><strong>Solución:</strong></p>";
    echo "<ol>";
    echo "<li>El problema es que el archivo <code>admin/publicador/index.php</code> no se actualizó correctamente</li>";
    echo "<li>Aunque el commit 24360a9 está en el servidor, ese commit NO incluye cambios en index.php</li>";
    echo "<li>Los commits que SÍ tienen el botón son: <code>3dca391</code> y <code>238649b</code></li>";
    echo "<li><strong>Solución manual:</strong> Descargue el archivo desde GitHub:</li>";
    echo "<li style='margin-left: 30px;'>URL: <a href='https://raw.githubusercontent.com/muniloslagos/carga_ta/main/admin/publicador/index.php' target='_blank'>Ver en GitHub</a></li>";
    echo "<li style='margin-left: 30px;'>Reemplace el archivo en el servidor</li>";
    echo "</ol>";
    echo "</div>";
}

// Mostrar fragmento de código con el botón (si existe)
if (preg_match('/btn-danger.*?data-bs-target=".*?modalEliminarVerificador.*?<\/a>/s', $contenido, $fragmento)) {
    echo "<hr>";
    echo "<h3>📝 Fragmento de código encontrado:</h3>";
    echo "<pre>" . htmlspecialchars($fragmento[0]) . "</pre>";
}

// Información del archivo
echo "<hr>";
echo "<h3>ℹ️ Información del archivo:</h3>";
echo "<ul>";
echo "<li><strong>Tamaño:</strong> " . number_format(filesize($rutaCompleta)) . " bytes</li>";
echo "<li><strong>Última modificación:</strong> " . date('d/m/Y H:i:s', filemtime($rutaCompleta)) . "</li>";
echo "<li><strong>Permisos:</strong> " . substr(sprintf('%o', fileperms($rutaCompleta)), -4) . "</li>";
echo "</ul>";

// Verificar commits
echo "<hr>";
echo "<h3>📦 Verificar commits en servidor:</h3>";
echo "<div class='info'>";
echo "<p>Para verificar qué commits tiene el servidor, ejecute en SSH:</p>";
echo "<pre>cd /home/appmuniloslagos/public_html/carga_ta\ngit log --oneline -10 --all</pre>";
echo "<p><strong>Commits que deben estar presentes:</strong></p>";
echo "<ul>";
echo "<li>✓ <code>3dca391</code> - Feature: Eliminar y retrotraer verificadores</li>";
echo "<li>✓ <code>238649b</code> - Fix: Escape caracteres especiales</li>";
echo "<li>✓ <code>24360a9</code> - Fix: Script diagnóstico (YA ESTÁ)</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='check_verificadores.php'>← Volver al diagnóstico de verificadores</a></p>";
echo "</body></html>";
?>
