# Guía de Despliegue - cPanel + GitHub

## Configuración Inicial en cPanel

### 1. Acceder a Git Version Control
1. Ingresa a tu cPanel
2. Busca "Git™ Version Control" en la sección "Files"
3. Click en "Create"

### 2. Clonar el Repositorio
```
Repository URL: https://github.com/muniloslagos/carga_ta.git
Repository Path: /home/usuario/carga_ta
Repository Name: carga_ta
```

### 3. Configurar Token de Acceso
- En "Clone URL" usa: `https://TOKEN@github.com/muniloslagos/carga_ta.git`
- Reemplaza TOKEN con tu Personal Access Token de GitHub

### 4. Configurar Deployment Path
- Click en "Manage" del repositorio
- En "Deployment Path" pon: `/home/usuario/public_html/cumplimiento`
- Click en "Update"

## Despliegue Manual

Cada vez que quieras actualizar el servidor:

1. Ve a cPanel → Git™ Version Control
2. Click en "Manage" del repositorio `carga_ta`
3. Click en "Pull or Deploy" → "Update from Remote"
4. Click en "Deploy HEAD Commit"

## Despliegue Automático (Webhook)

### 1. Crear Script de Despliegue

Crea el archivo `deploy.php` en el servidor (fuera de public_html):

```php
<?php
// Validar que la petición viene de GitHub
$secret = 'tu_secreto_aqui'; // Cambia esto
$payload = file_get_contents('php://input');
$signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($signature, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '')) {
    http_response_code(403);
    die('Acceso denegado');
}

// Log del despliegue
$log_file = __DIR__ . '/deploy.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Iniciando despliegue\n", FILE_APPEND);

// Ejecutar git pull
$output = shell_exec('cd /home/usuario/carga_ta && git pull origin main 2>&1');
file_put_contents($log_file, $output . "\n", FILE_APPEND);

// Copiar archivos a public_html
$output = shell_exec('cd /home/usuario/carga_ta && cp -r * /home/usuario/public_html/cumplimiento/ 2>&1');
file_put_contents($log_file, $output . "\n", FILE_APPEND);

file_put_contents($log_file, date('Y-m-d H:i:s') . " - Despliegue completado\n\n", FILE_APPEND);

echo "Despliegue exitoso";
?>
```

### 2. Configurar Webhook en GitHub

1. Ve a: https://github.com/muniloslagos/carga_ta/settings/hooks
2. Click "Add webhook"
3. Payload URL: `https://tudominio.com/deploy.php`
4. Content type: `application/json`
5. Secret: El mismo secreto que pusiste en `deploy.php`
6. Events: "Just the push event"
7. Click "Add webhook"

## Configuración del Servidor

### Archivos que NO se deben subir (ya en .gitignore):
- `config/config.php` (crear manualmente en servidor con credenciales de producción)
- `uploads/*` (contenido de uploads)

### Crear config.php en producción:

```php
<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_bd_produccion');
define('DB_PASS', 'password_produccion');
define('DB_NAME', 'nombre_bd_produccion');

// Configuración general
define('SITE_URL', 'https://tudominio.com/cumplimiento/');
define('SITE_NAME', 'Administración de Carga Unificada y Control de Transparencia');

// Timezone
date_default_timezone_set('America/Santiago');

// Errores (DESACTIVAR en producción)
ini_set('display_errors', 0);
error_reporting(0);
```

### Permisos necesarios:
```bash
chmod 755 /home/usuario/public_html/cumplimiento
chmod 777 /home/usuario/public_html/cumplimiento/uploads
```

## Comandos Útiles

### Verificar estado del repositorio:
```bash
cd /home/usuario/carga_ta
git status
git log --oneline -5
```

### Actualizar manualmente:
```bash
cd /home/usuario/carga_ta
git pull origin main
cp -r * /home/usuario/public_html/cumplimiento/
```

## Troubleshooting

### Error: Permission denied
```bash
chmod +x deploy.php
```

### Error: Git pull fails
Verifica que el token de GitHub tenga permisos de lectura

### Webhook no funciona
- Verifica que la URL sea accesible públicamente
- Revisa el log en GitHub: Settings → Webhooks → Recent Deliveries
- Revisa deploy.log en el servidor
