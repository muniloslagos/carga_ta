<?php
/**
 * Script de Despliegue Automático
 * GitHub Webhook → cPanel
 * 
 * INSTRUCCIONES:
 * 1. Sube este archivo FUERA de public_html (ej: /home/usuario/deploy.php)
 * 2. Cambia el valor de $secret por uno único
 * 3. Ajusta las rutas según tu servidor
 * 4. Dale permisos: chmod 755 deploy.php
 * 5. Crea el webhook en GitHub apuntando a: https://tudominio.com/ruta/deploy.php
 */

// ===========================
// CONFIGURACIÓN
// ===========================

// Secreto compartido con GitHub (cámbialo)
$secret = 'lnwZc4FYK9hE65DX1VABJIjbi2kfzvtr';

// Rutas en el servidor (ajusta según tu servidor)
$repo_path = '/home/appmuniloslagos/repositories/carga_ta';  // Donde está clonado el repo
$deploy_path = '/home/appmuniloslagos/public_html/carga_ta';  // Donde se despliega
$log_file = __DIR__ . '/deploy.log';

// Rama a desplegar
$branch = 'main';

// ===========================
// NO MODIFICAR ABAJO DE AQUÍ
// ===========================

// Función para escribir en el log
function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Validar que la petición viene de GitHub
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($signature) {
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($expected_signature, $signature)) {
        http_response_code(403);
        write_log('ERROR: Firma inválida - Acceso denegado');
        die('Acceso denegado');
    }
} else {
    http_response_code(403);
    write_log('ERROR: Sin firma - Acceso denegado');
    die('Acceso denegado');
}

// Decodificar payload
$data = json_decode($payload, true);
$pushed_branch = str_replace('refs/heads/', '', $data['ref'] ?? '');

// Verificar que es la rama correcta
if ($pushed_branch !== $branch) {
    write_log("INFO: Push ignorado - Rama $pushed_branch no coincide con $branch");
    die("Push ignorado - Rama incorrecta");
}

write_log("========================================");
write_log("INICIO DE DESPLIEGUE");
write_log("Commit: " . ($data['head_commit']['id'] ?? 'unknown'));
write_log("Mensaje: " . ($data['head_commit']['message'] ?? 'sin mensaje'));
write_log("Autor: " . ($data['head_commit']['author']['name'] ?? 'unknown'));

// Cambiar al directorio del repositorio
if (!is_dir($repo_path)) {
    http_response_code(500);
    write_log("ERROR: El directorio del repositorio no existe: $repo_path");
    die('Error: Directorio del repositorio no encontrado');
}

chdir($repo_path);

// Ejecutar git pull
write_log("Ejecutando: git pull origin $branch");
exec("git pull origin $branch 2>&1", $output, $return_code);

if ($return_code !== 0) {
    write_log("ERROR: git pull falló con código $return_code");
    write_log("Output: " . implode("\n", $output));
    http_response_code(500);
    die('Error en git pull');
}

write_log("Git pull exitoso");
foreach ($output as $line) {
    write_log("  $line");
}

// Crear directorio de despliegue si no existe
if (!is_dir($deploy_path)) {
    mkdir($deploy_path, 0755, true);
    write_log("Directorio de despliegue creado: $deploy_path");
}

// Copiar archivos (excluyendo .git y otros)
write_log("Copiando archivos a $deploy_path");
exec("rsync -av --exclude='.git' --exclude='*.md' --exclude='test_*.php' --exclude='*.txt' $repo_path/ $deploy_path/ 2>&1", $output, $return_code);

if ($return_code !== 0) {
    write_log("ERROR: rsync falló con código $return_code");
    write_log("Output: " . implode("\n", $output));
    
    // Intentar con cp como fallback
    write_log("Intentando con cp...");
    exec("cp -r $repo_path/* $deploy_path/ 2>&1", $output, $return_code);
    
    if ($return_code !== 0) {
        write_log("ERROR: cp también falló");
        http_response_code(500);
        die('Error al copiar archivos');
    }
}

write_log("Archivos copiados exitosamente");

// Asegurar permisos correctos en uploads
$uploads_path = $deploy_path . '/uploads';
if (is_dir($uploads_path)) {
    chmod($uploads_path, 0777);
    write_log("Permisos actualizados en $uploads_path");
}

write_log("DESPLIEGUE COMPLETADO EXITOSAMENTE");
write_log("========================================\n");

// Respuesta exitosa
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Despliegue completado',
    'timestamp' => date('Y-m-d H:i:s'),
    'commit' => substr($data['head_commit']['id'] ?? '', 0, 7)
]);
?>
