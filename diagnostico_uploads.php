<?php
/**
 * DIAGNOSTICO DE UPLOADS
 * Archivo para diagnosticar problemas de uploads en producción
 * Subir a: http://app.muniloslagos.cl/carga_ta/diagnostico_uploads.php
 */

// Verificar autenticación (opcional - comentar estas líneas si da problemas)
session_start();
if (!isset($_SESSION['user_id'])) {
    // echo "Advertencia: No estás autenticado. Algunos tests pueden fallar.<br><br>";
}

echo "<h1>Diagnóstico de Sistema de Uploads</h1>";
echo "<hr>";

// 1. VERIFICAR VERSIÓN DE PHP
echo "<h2>1. Versión de PHP</h2>";
echo "Versión actual: <strong>" . phpversion() . "</strong><br>";
echo "Versión mínima requerida: 7.4<br>";
echo "✅ Estado: " . (version_compare(phpversion(), '7.4.0', '>=') ? '<span style="color:green;">OK</span>' : '<span style="color:red;">ACTUALIZAR PHP</span>') . "<br><br>";

// 2. VERIFICAR CONFIGURACIÓN DE UPLOADS
echo "<h2>2. Configuración de Uploads PHP</h2>";
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');
$max_execution = ini_get('max_execution_time');

echo "upload_max_filesize: <strong>$upload_max</strong> (Recomendado: 20M o más)<br>";
echo "post_max_size: <strong>$post_max</strong> (Recomendado: 25M o más)<br>";
echo "memory_limit: <strong>$memory_limit</strong> (Recomendado: 128M o más)<br>";
echo "max_execution_time: <strong>{$max_execution}s</strong> (Recomendado: 60s o más)<br><br>";

// 3. VERIFICAR CARPETA UPLOADS
echo "<h2>3. Carpeta Uploads</h2>";
$uploads_dir = __DIR__ . '/uploads/';
echo "Ruta completa: <strong>$uploads_dir</strong><br>";

if (file_exists($uploads_dir)) {
    echo "✅ Carpeta existe: <span style='color:green;'>SÍ</span><br>";
} else {
    echo "❌ Carpeta existe: <span style='color:red;'>NO - CREAR CARPETA</span><br>";
}

if (is_dir($uploads_dir)) {
    echo "✅ Es directorio: <span style='color:green;'>SÍ</span><br>";
} else {
    echo "❌ Es directorio: <span style='color:red;'>NO</span><br>";
}

// 4. VERIFICAR PERMISOS DE ESCRITURA
echo "<h2>4. Permisos de Escritura</h2>";
if (is_writable($uploads_dir)) {
    echo "✅ Carpeta escribible: <span style='color:green;'>SÍ</span><br>";
} else {
    echo "❌ Carpeta escribible: <span style='color:red;'>NO - CAMBIAR PERMISOS A 755 o 777</span><br>";
}

// Intentar crear archivo de prueba
$test_file = $uploads_dir . 'test_' . time() . '.txt';
$write_test = @file_put_contents($test_file, 'Test de escritura');

if ($write_test !== false) {
    echo "✅ Test de escritura: <span style='color:green;'>OK - Archivo creado exitosamente</span><br>";
    @unlink($test_file); // Eliminar archivo de prueba
} else {
    echo "❌ Test de escritura: <span style='color:red;'>FALLÓ - No se puede escribir en uploads/</span><br>";
}

// 5. PERMISOS DETALLADOS (UNIX/LINUX)
if (function_exists('fileperms')) {
    $perms = fileperms($uploads_dir);
    $perms_octal = substr(sprintf('%o', $perms), -4);
    echo "Permisos actuales (octal): <strong>$perms_octal</strong><br>";
    echo "Recomendado: 0755 (lectura/escritura dueño, lectura otros) o 0777 (lectura/escritura todos)<br>";
}

// 6. VERIFICAR PROPIEDAD DE ARCHIVOS (UNIX/LINUX)
if (function_exists('posix_getpwuid')) {
    $owner_info = posix_getpwuid(fileowner($uploads_dir));
    echo "<br>Propietario de carpeta: <strong>" . $owner_info['name'] . "</strong><br>";
    
    if (function_exists('posix_getpwuid')) {
        $process_user = posix_getpwuid(posix_geteuid());
        echo "Usuario del proceso PHP: <strong>" . $process_user['name'] . "</strong><br>";
        
        if ($owner_info['name'] == $process_user['name']) {
            echo "✅ Propiedad: <span style='color:green;'>OK - Mismo usuario</span><br>";
        } else {
            echo "⚠️ Propiedad: <span style='color:orange;'>ADVERTENCIA - Usuarios diferentes, verificar permisos</span><br>";
        }
    }
}

echo "<br>";

// 7. LISTAR ARCHIVOS EN UPLOADS
echo "<h2>5. Archivos en Uploads (últimos 10)</h2>";
if (is_dir($uploads_dir)) {
    $files = array_diff(scandir($uploads_dir), array('.', '..'));
    if (count($files) > 0) {
        $files_sorted = array_slice($files, -10);
        echo "<ul>";
        foreach ($files_sorted as $file) {
            $filepath = $uploads_dir . $file;
            $size = filesize($filepath);
            $size_kb = round($size / 1024, 2);
            $perms_file = substr(sprintf('%o', fileperms($filepath)), -4);
            echo "<li><strong>$file</strong> - {$size_kb}KB - Permisos: $perms_file</li>";
        }
        echo "</ul>";
        echo "Total de archivos: <strong>" . count($files) . "</strong><br>";
    } else {
        echo "⚠️ No hay archivos en la carpeta uploads/<br>";
    }
} else {
    echo "❌ No se puede leer la carpeta<br>";
}

echo "<br>";

// 8. VERIFICAR CONEXIÓN A BASE DE DATOS
echo "<h2>6. Conexión a Base de Datos</h2>";
require_once __DIR__ . '/config/config.php';
$db_test = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db_test->connect_error) {
    echo "❌ Conexión: <span style='color:red;'>FALLÓ - " . $db_test->connect_error . "</span><br>";
} else {
    echo "✅ Conexión: <span style='color:green;'>OK</span><br>";
    
    // Verificar tablas
    $tables = ['documentos', 'documento_seguimiento', 'historial', 'items_transparencia', 'usuarios'];
    echo "<br>Tablas existentes:<br>";
    foreach ($tables as $table) {
        $result = $db_test->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "✅ $table: <span style='color:green;'>Existe</span><br>";
        } else {
            echo "❌ $table: <span style='color:red;'>NO EXISTE</span><br>";
        }
    }
    
    $db_test->close();
}

echo "<br>";

// 9. VERIFICAR ERROR LOG
echo "<h2>7. Configuración de Error Log</h2>";
echo "display_errors: <strong>" . ini_get('display_errors') . "</strong> (Producción: Off)<br>";
echo "log_errors: <strong>" . ini_get('log_errors') . "</strong> (Recomendado: On)<br>";
echo "error_log: <strong>" . ini_get('error_log') . "</strong><br>";

echo "<br><hr>";
echo "<h2>RESUMEN DE ACCIONES RECOMENDADAS</h2>";
echo "<ol>";
echo "<li>Si la carpeta uploads/ no existe: Crear con <code>mkdir uploads && chmod 755 uploads</code></li>";
echo "<li>Si no es escribible: Cambiar permisos con <code>chmod 755 uploads</code> o <code>chmod 777 uploads</code></li>";
echo "<li>Si upload_max_filesize es menor a 20M: Editar php.ini o .htaccess</li>";
echo "<li>Verificar que el usuario del servidor web (www-data, apache, nginx) tenga permisos</li>";
echo "<li>Revisar error logs del servidor en /var/log/apache2/ o /var/log/nginx/</li>";
echo "</ol>";

echo "<br><p><strong>Fecha de diagnóstico:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
