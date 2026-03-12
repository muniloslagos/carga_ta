# SOLUCIÓN: Problema de Uploads en Producción

## Problema Identificado
Los documentos no se guardan en producción aunque muestre mensaje de éxito.

## Causas Posibles
1. **Permisos de carpeta uploads/** - El servidor no puede escribir archivos
2. **Límites de PHP** - upload_max_filesize muy bajo
3. **Falta registro en historial** - No se guardaba en tabla historial

## Cambios Realizados

### 1. Archivo `usuario/enviar_documento.php`
✅ Agregado registro en tabla `historial`
✅ Agregado logging de errores con `error_log()`
✅ Verificación de que el archivo existe después de upload
✅ Mejor manejo de errores con mensajes específicos
✅ Limpieza de archivos si falla inserción en BD

### 2. Archivo `diagnostico_uploads.php` (NUEVO)
Herramienta de diagnóstico para identificar problemas en producción.

**Uso:**
1. Subir a producción
2. Acceder a: http://app.muniloslagos.cl/carga_ta/diagnostico_uploads.php
3. Revisar qué muestra en rojo (❌)

### 3. Archivo `uploads/.htaccess` (NUEVO)
Configuración para aumentar límites de upload en Apache/cPanel.

## Pasos para Desplegar en Producción

### Paso 1: Subir archivos actualizados
```bash
# Desde tu repositorio local
git add usuario/enviar_documento.php
git add diagnostico_uploads.php
git add uploads/.htaccess
git commit -m "Fix uploads en produccion: agrega historial y diagnostico"
git push origin main
```

### Paso 2: Actualizar en cPanel
1. Ir a cPanel → File Manager
2. Navegar a `/home/munilosl/public_html/carga_ta/`
3. Usar GitHub sync o subir manualmente:
   - `usuario/enviar_documento.php`
   - `diagnostico_uploads.php`
   - `uploads/.htaccess`

### Paso 3: Verificar permisos de carpeta uploads
```bash
# Opción A: Via cPanel File Manager
1. Click derecho en carpeta "uploads"
2. Seleccionar "Change Permissions"
3. Establecer: 755 o 777
   - 755 = Lectura/Escritura dueño, Lectura otros (más seguro)
   - 777 = Lectura/Escritura todos (menos seguro pero más compatible)

# Opción B: Via SSH (si tienes acceso)
cd /home/munilosl/public_html/carga_ta/
chmod 755 uploads/
# o si 755 no funciona:
chmod 777 uploads/
```

### Paso 4: Ejecutar diagnóstico
1. Abrir navegador: http://app.muniloslagos.cl/carga_ta/diagnostico_uploads.php
2. Revisar resultados:
   - ✅ Verde = OK
   - ❌ Rojo = Problema que debe corregirse
   - ⚠️ Naranja = Advertencia

### Paso 5: Soluciones según diagnóstico

#### Si carpeta uploads no existe:
```bash
# Via SSH
cd /home/munilosl/public_html/carga_ta/
mkdir uploads
chmod 755 uploads
```

#### Si no es escribible:
```bash
chmod 755 uploads/
# Si no funciona, intentar:
chmod 777 uploads/
```

#### Si upload_max_filesize es muy bajo:
**Opción A: Via cPanel → Select PHP Version**
1. Ir a cPanel → Select PHP Version (o MultiPHP INI Editor)
2. Cambiar:
   - `upload_max_filesize` = 20M
   - `post_max_size` = 25M
   - `memory_limit` = 128M
   - `max_execution_time` = 120

**Opción B: Crear php.ini en raíz**
```ini
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 128M
max_execution_time = 120
```

### Paso 6: Verificar error logs
```bash
# Via cPanel → Errors
Revisar últimos errores del servidor para ver mensajes específicos
```

## Verificar que Funciona

1. Acceder a dashboard: http://app.muniloslagos.cl/carga_ta/usuario/dashboard.php
2. Intentar cargar un documento pequeño (PDF < 5MB)
3. Verificar:
   - ✅ Mensaje de éxito verde
   - ✅ Documento aparece en tabla (verde)
   - ✅ Historial muestra el movimiento
   - ✅ Archivo existe en carpeta uploads/

## Revisar Error Logs

### Via cPanel:
1. Ir a cPanel → Errors
2. Buscar entradas recientes con:
   - "Error upload documento"
   - "Error al crear documento en BD"
   - "Archivo no existe después de upload"

### Via SSH:
```bash
# Error log de PHP
tail -f /home/munilosl/public_html/carga_ta/error_log

# Error log de Apache (si tienes acceso)
tail -f /var/log/apache2/error.log
```

## Comandos Útiles cPanel

### Verificar permisos actuales:
```bash
ls -la uploads/
# Debe mostrar drwxr-xr-x (755) o drwxrwxrwx (777)
```

### Ver archivos recientes:
```bash
ls -lht uploads/ | head -10
```

### Espacio en disco:
```bash
df -h
du -sh uploads/
```

## Troubleshooting Adicional

### Si sigue sin funcionar después de todo:
1. Verificar que el propietario de uploads/ sea el usuario correcto:
   ```bash
   chown -R munilosl:munilosl uploads/
   ```

2. Verificar módulos PHP activos:
   - fileinfo (para verificar tipos de archivo)
   - mysqli (para base de datos)

3. Revisar configuración de SELinux (si aplica):
   ```bash
   setenforce 0  # Temporalmente desactivar para probar
   ```

## Contacto
Si después de estos pasos sigue sin funcionar, compartir:
1. Screenshot del diagnóstico completo
2. Últimas líneas del error log
3. Permisos actuales de uploads/ (ls -la)
