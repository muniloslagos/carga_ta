<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=UTF-8');

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db_conn->connect_error) {
    die("Error de conexión: " . $db_conn->connect_error);
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico Archivos</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .ok { background: #d4edda; color: green; }
        .error { background: #f8d7da; color: red; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔍 Diagnóstico de Archivos</h1>";

// 1. Verificar documentos en BD
$sql = "SELECT 
            d.id,
            d.titulo,
            d.archivo,
            i.numeracion,
            i.nombre as item_nombre
        FROM documentos d
        LEFT JOIN items_transparencia i ON d.item_id = i.id
        ORDER BY d.fecha_subida DESC
        LIMIT 30";

$result = $db_conn->query($sql);

echo "<div class='section'>";
echo "<h2>Últimos 30 Documentos en Base de Datos</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>Título</th><th>Item</th><th>Archivo (BD)</th><th>Existe Físicamente</th></tr>";

$uploadsDir = __DIR__ . '/uploads/';
$totalDocs = 0;
$archivosExisten = 0;
$archivosFaltan = 0;

while ($row = $result->fetch_assoc()) {
    $totalDocs++;
    $archivoPath = $uploadsDir . $row['archivo'];
    $existe = file_exists($archivoPath);
    
    if ($existe) {
        $archivosExisten++;
        $class = 'ok';
        $estado = '✅ Existe (' . round(filesize($archivoPath)/1024, 2) . ' KB)';
    } else {
        $archivosFaltan++;
        $class = 'error';
        $estado = '❌ NO EXISTE';
    }
    
    echo "<tr class='$class'>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['titulo'], 0, 40)) . "...</td>";
    echo "<td>" . htmlspecialchars($row['numeracion']) . "</td>";
    echo "<td><code>" . htmlspecialchars($row['archivo']) . "</code></td>";
    echo "<td><strong>$estado</strong></td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Total documentos: <strong>$totalDocs</strong></p>";
echo "<p class='ok'>✅ Archivos que existen: <strong>$archivosExisten</strong></p>";
if ($archivosFaltan > 0) {
    echo "<p class='error'>❌ Archivos faltantes: <strong>$archivosFaltan</strong></p>";
}
echo "</div>";

// 2. Archivos físicos en /uploads/
echo "<div class='section'>";
echo "<h2>Archivos Físicos en /uploads/</h2>";

if (is_dir($uploadsDir)) {
    $archivos = array_diff(scandir($uploadsDir), array('.', '..', '.htaccess', 'index.php'));
    echo "<p>Directorio: <code>" . $uploadsDir . "</code></p>";
    echo "<p>Total archivos: <strong>" . count($archivos) . "</strong></p>";
    
    if (count($archivos) > 0) {
        echo "<table>";
        echo "<tr><th>#</th><th>Archivo</th><th>Tamaño</th><th>¿En BD?</th></tr>";
        
        $contador = 0;
        $archivosOrfanos = 0;
        
        foreach ($archivos as $archivo) {
            if (is_file($uploadsDir . $archivo)) {
                $contador++;
                $size = filesize($uploadsDir . $archivo);
                $sizeKB = round($size / 1024, 2);
                
                // Verificar si está en BD
                $sql_check = "SELECT id FROM documentos WHERE archivo = ?";
                $stmt = $db_conn->prepare($sql_check);
                $stmt->bind_param("s", $archivo);
                $stmt->execute();
                $result_check = $stmt->get_result();
                $enBD = $result_check->num_rows > 0;
                
                if (!$enBD) {
                    $archivosOrfanos++;
                    $class = 'error';
                    $estadoBD = '❌ Huérfano (no en BD)';
                } else {
                    $class = 'ok';
                    $estadoBD = '✅ Registrado';
                }
                
                echo "<tr class='$class'>";
                echo "<td>$contador</td>";
                echo "<td><code>" . htmlspecialchars($archivo) . "</code></td>";
                echo "<td>" . $sizeKB . " KB</td>";
                echo "<td>$estadoBD</td>";
                echo "</tr>";
                
                if ($contador >= 20) {
                    echo "<tr><td colspan='4' class='text-center'>... (mostrando solo primeros 20)</td></tr>";
                    break;
                }
            }
        }
        
        echo "</table>";
        
        if ($archivosOrfanos > 0) {
            echo "<p class='error'>⚠️ Archivos huérfanos (no referenciados en BD): <strong>$archivosOrfanos</strong></p>";
            echo "<p><small>Estos archivos pueden eliminarse de forma segura.</small></p>";
        }
    } else {
        echo "<p class='error'>⚠️ No hay archivos en /uploads/</p>";
    }
    
    // Verificar permisos
    if (is_writable($uploadsDir)) {
        echo "<p class='ok'>✅ Directorio tiene permisos de escritura</p>";
    } else {
        echo "<p class='error'>❌ Directorio NO tiene permisos de escritura</p>";
    }
} else {
    echo "<p class='error'>❌ Directorio /uploads/ no existe</p>";
}

echo "</div>";

// 3. Recomendaciones
echo "<div class='section'>";
echo "<h2>📋 Recomendaciones</h2>";

if ($archivosFaltan > 0) {
    echo "<div class='error' style='padding: 10px; margin: 10px 0;'>";
    echo "<h3>⚠️ PROBLEMA DETECTADO</h3>";
    echo "<p>Hay <strong>$archivosFaltan documentos</strong> en la base de datos que no tienen archivo físico.</p>";
    echo "<p><strong>Posibles causas:</strong></p>";
    echo "<ul>";
    echo "<li>Los archivos fueron eliminados manualmente del servidor</li>";
    echo "<li>Error durante la migración de archivos entre servidores</li>";
    echo "<li>Permisos incorrectos impidieron la escritura</li>";
    echo "</ul>";
    echo "<p><strong>Solución:</strong></p>";
    echo "<ul>";
    echo "<li>Los usuarios deberán volver a cargar esos documentos</li>";
    echo "<li>O restaurar el backup de /uploads/ si existe</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<p class='ok'>✅ Todos los documentos en BD tienen su archivo físico correspondiente</p>";
}

echo "</div>";

echo "</body></html>";

$db_conn->close();
?>
