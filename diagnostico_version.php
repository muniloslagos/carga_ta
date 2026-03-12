<?php
/**
 * DIAGNOSTICO DE VERSION
 * Verifica qué versión del código está ejecutándose
 * y qué datos hay en la base de datos
 */

// Cargar configuración primero
require_once 'config/config.php';
require_once 'config/Database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico de Versión</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<h1>🔍 DIAGNÓSTICO DE VERSIÓN Y DATOS</h1>";
echo "<p>Fecha/Hora: " . date('Y-m-d H:i:s') . "</p>";

// ============================================
// 1. VERIFICAR VERSIÓN DEL CÓDIGO
// ============================================
echo "<div class='section'>";
echo "<h2>1️⃣ Versión del Código</h2>";

$dashboardFile = __DIR__ . '/usuario/dashboard.php';
$contenido = file_get_contents($dashboardFile);

// Buscar la línea del LEFT JOIN de documentos
if (strpos($contenido, 'LEFT JOIN documentos d ON i.id = d.item_id AND (d.usuario_id = ?') !== false) {
    echo "<p class='error'>❌ CÓDIGO ANTIGUO DETECTADO</p>";
    echo "<p>El dashboard.php tiene el <strong>filtro de usuario</strong> que esconde documentos.</p>";
    echo "<p>Línea encontrada: <code>LEFT JOIN documentos d ON i.id = d.item_id AND (d.usuario_id = ?...</code></p>";
    echo "<p><strong>ACCIÓN REQUERIDA:</strong> Actualizar código en producción con git pull</p>";
} elseif (strpos($contenido, 'LEFT JOIN documentos d ON i.id = d.item_id') !== false) {
    echo "<p class='ok'>✅ CÓDIGO ACTUALIZADO</p>";
    echo "<p>El dashboard.php tiene la versión sin filtro (transparencia completa).</p>";
    echo "<p>Commit esperado: <code>11f6a93</code></p>";
} else {
    echo "<p class='warning'>⚠️ NO SE ENCUENTRA EL PATRÓN ESPERADO</p>";
    echo "<p>Revisar manualmente el archivo dashboard.php líneas 30-75</p>";
}

echo "</div>";

// ============================================
// 2. VERIFICAR BASE DE DATOS
// ============================================
echo "<div class='section'>";
echo "<h2>2️⃣ Estado de la Base de Datos</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Contar items activos
    $sql = "SELECT COUNT(*) as total FROM items_transparencia WHERE activo = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    echo "<p>📋 Items activos: <strong>" . $row['total'] . "</strong></p>";
    
    // Contar documentos totales
    $sql = "SELECT COUNT(*) as total FROM documentos";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    echo "<p>📄 Documentos totales: <strong>" . $row['total'] . "</strong></p>";
    
    // Documentos por usuario
    $sql = "SELECT 
                u.nombre, 
                u.email,
                u.perfil,
                COUNT(d.id) as total_docs
            FROM usuarios u
            LEFT JOIN documentos d ON u.id = d.usuario_id
            GROUP BY u.id
            ORDER BY total_docs DESC";
    $result = $conn->query($sql);
    
    echo "<h3>Documentos por Usuario:</h3>";
    echo "<table>";
    echo "<tr><th>Usuario</th><th>Email</th><th>Perfil</th><th>Documentos</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $class = $row['total_docs'] > 0 ? 'ok' : '';
        echo "<tr class='$class'>";
        echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['perfil']) . "</td>";
        echo "<td><strong>" . $row['total_docs'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar estructura de documento_seguimiento
    echo "<h3>Últimos 10 Documentos con Seguimiento:</h3>";
    $sql = "SELECT 
                d.id,
                d.titulo,
                d.usuario_id,
                u.nombre as usuario_nombre,
                ds.mes,
                ds.ano,
                ds.fecha_envio,
                d.fecha_subida
            FROM documentos d
            LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            ORDER BY d.fecha_subida DESC
            LIMIT 10";
    $result = $conn->query($sql);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Usuario</th><th>Mes</th><th>Año</th><th>Fecha</th></tr>";
    $documentos_validos = 0;
    $documentos_invalidos = 0;
    
    while ($row = $result->fetch_assoc()) {
        $valido = ($row['mes'] > 0 && $row['ano'] > 0);
        $class = $valido ? 'ok' : 'error';
        
        if ($valido) {
            $documentos_validos++;
        } else {
            $documentos_invalidos++;
        }
        
        echo "<tr class='$class'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['titulo'], 0, 40)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['usuario_nombre']) . "</td>";
        echo "<td>" . ($row['mes'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['ano'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['fecha_subida'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p>✅ Documentos válidos (mes>0, ano>0): <strong>$documentos_validos</strong></p>";
    if ($documentos_invalidos > 0) {
        echo "<p class='error'>❌ Documentos inválidos (mes=0 o NULL): <strong>$documentos_invalidos</strong></p>";
        echo "<p>Los documentos con mes=0 o año=0 NO se mostrarán en el dashboard.</p>";
    }
    
    // Verificar tabla usuarios tiene columna nombre
    $sql = "SHOW COLUMNS FROM usuarios LIKE 'nombre'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo "<p class='ok'>✅ Tabla usuarios tiene columna 'nombre'</p>";
    } else {
        echo "<p class='error'>❌ Tabla usuarios NO tiene columna 'nombre'</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================
// 3. VERIFICAR ARCHIVOS FÍSICOS
// ============================================
echo "<div class='section'>";
echo "<h2>3️⃣ Archivos Físicos en /uploads/</h2>";

$uploadsDir = __DIR__ . '/uploads/';
if (is_dir($uploadsDir)) {
    $files = array_diff(scandir($uploadsDir), array('.', '..', '.htaccess', 'index.php'));
    echo "<p>📁 Archivos encontrados: <strong>" . count($files) . "</strong></p>";
    
    if (count($files) > 0) {
        echo "<p>Últimos 10 archivos:</p>";
        echo "<ul>";
        $files = array_slice($files, -10);
        foreach ($files as $file) {
            $size = filesize($uploadsDir . $file);
            $size_kb = round($size / 1024, 2);
            echo "<li><code>$file</code> - {$size_kb} KB</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ No hay archivos en /uploads/</p>";
    }
    
    // Verificar permisos
    if (is_writable($uploadsDir)) {
        echo "<p class='ok'>✅ Directorio /uploads/ tiene permisos de escritura</p>";
    } else {
        echo "<p class='error'>❌ Directorio /uploads/ NO tiene permisos de escritura</p>";
    }
} else {
    echo "<p class='error'>❌ Directorio /uploads/ no existe</p>";
}

echo "</div>";

// ============================================
// 4. INFORMACIÓN DEL SERVIDOR
// ============================================
echo "<div class='section'>";
echo "<h2>4️⃣ Información del Servidor</h2>";
echo "<p>PHP Version: <strong>" . phpversion() . "</strong></p>";
echo "<p>Server Software: <strong>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</strong></p>";
echo "<p>Document Root: <strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong></p>";
echo "<p>Script Filename: <strong>" . __FILE__ . "</strong></p>";

// Verificar OPcache
if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    if ($opcache && $opcache['opcache_enabled']) {
        echo "<p class='warning'>⚠️ OPcache está ACTIVO</p>";
        echo "<p>Si actualizaste el código, puede estar sirviendo versión cacheada.</p>";
        echo "<p><strong>Solución:</strong> Reiniciar Apache o ejecutar <code>opcache_reset()</code></p>";
    } else {
        echo "<p class='ok'>✅ OPcache desactivado</p>";
    }
} else {
    echo "<p>ℹ️ OPcache no disponible</p>";
}

echo "</div>";

// ============================================
// 5. CONCLUSIÓN Y RECOMENDACIONES
// ============================================
echo "<div class='section'>";
echo "<h2>5️⃣ Conclusión y Acciones Recomendadas</h2>";

echo "<ol>";
echo "<li><strong>Si código es ANTIGUO:</strong>
        <ul>
            <li>Conectar por SSH al servidor: <code>ssh usuario@app.muniloslagos.cl</code></li>
            <li>Ir al directorio: <code>cd /var/www/html/carga_ta</code> (o donde esté)</li>
            <li>Actualizar código: <code>git pull origin main</code></li>
            <li>Verificar cambios: <code>git log --oneline -3</code></li>
            <li>Si OPcache activo: <code>sudo service apache2 restart</code></li>
        </ul>
    </li>";
echo "<li><strong>Si código es ACTUAL pero no funciona:</strong>
        <ul>
            <li>Verificar que documento_seguimiento tenga mes > 0 y ano > 0</li>
            <li>Revisar error_log de Apache: <code>tail -f /var/log/apache2/error.log</code></li>
            <li>Verificar permisos de /uploads/: <code>chmod 755 uploads/</code></li>
        </ul>
    </li>";
echo "<li><strong>Probar carga de documento nuevo:</strong>
        <ul>
            <li>Cargar un documento de prueba en producción</li>
            <li>Verificar que se guarde en BD con mes/ano correcto</li>
            <li>Verificar que el archivo físico se guarde en /uploads/</li>
            <li>Probar visualización desde otra cuenta de usuario</li>
        </ul>
    </li>";
echo "</ol>";

echo "</div>";

echo "<hr>";
echo "<p><strong>Ejecutar en:</strong> localhost y producción para comparar resultados</p>";
echo "<p><strong>URL:</strong> http://app.muniloslagos.cl/carga_ta/diagnostico_version.php</p>";

echo "</body></html>";
?>
