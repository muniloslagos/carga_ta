# Configuración de Límites de Carga de Archivos

## Límite Actual
- **Tamaño máximo por archivo:** 100 MB
- Configurado en: `classes/Documento.php` línea 233

## Para Aumentar el Límite de Carga

Si necesita permitir archivos más grandes, debe modificar:

### 1. Configuración PHP (php.ini)

Editar el archivo `php.ini` (en XAMPP: `C:\xampp\php\php.ini` o en Linux: `/etc/php/8.x/apache2/php.ini`):

```ini
; Tamaño máximo de archivos subidos
upload_max_filesize = 50M

; Tamaño máximo de POST (debe ser mayor o igual a upload_max_filesize)
post_max_size = 50M

; Tiempo máximo de ejecución (segundos)
max_execution_time = 300

; Memoria máxima
memory_limit = 256M
```

**Importante:** Después de editar `php.ini`, debe reiniciar el servidor Apache.

### 2. Configuración en el Código

#### A. Modificar `classes/Documento.php` (línea 233):

```php
$maxSize = 100 * 1024 * 1024; // 100MB (actualmente configurado)
```

#### B. Modificar `usuario/dashboard.php`:

1. Cambiar la constante JavaScript (línea ~2530):
```javascript
const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB (actualmente configurado)
```

2. Actualizar los mensajes en los formularios:
```html
<small class="text-muted">✓ Tamaño máximo: <strong>100 MB</strong></small>
```

#### C. Modificar `usuario/modificar_sin_movimiento.php` (línea 129):

```php
$maxSize = 100 * 1024 * 1024; // 100MB (actualmente configurado)
```

### 3. Configuración Apache (opcional)

Si usa Apache, puede agregar en `.htaccess`:

```apache
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value max_input_time 300
```

## Verificar Configuración Actual

Para ver la configuración actual de PHP, acceder a:
- Archivo de prueba: `check.php` en la raíz del proyecto
- O crear un archivo `phpinfo.php` con: `<?php phpinfo(); ?>`

Buscar:
- `upload_max_filesize`
- `post_max_size`
- `memory_limit`
- `max_execution_time`

## Recomendaciones

1. **Producción:** No exceder de 50-100 MB para evitar problemas de memoria
2. **Servidor:** Asegurar que el servidor tiene suficiente espacio en disco
3. **Base de Datos:** Los archivos se guardan en sistema de archivos, no en BD
4. **Seguridad:** Mantener validaciones de tipo de archivo activas

## Archivos Modificados

- `classes/Documento.php` - Validación backend
- `classes/Verificador.php` - Validación para verificadores
- `usuario/dashboard.php` - Validación frontend y UI
- `usuario/modificar_sin_movimiento.php` - Validación sin movimiento
- `usuario/enviar_documento.php` - Mensajes de error

## Límites Actuales por Tipo

| Tipo de Carga | Límite Actual |
|---------------|---------------|
| Documentos (cargadores) | 100 MB |
| Verificadores (publicadores) | 100 MB |
| Sin Movimiento (reemplazo) | 100 MB |

**Nota:** Todos los límites han sido configurados a 100 MB para permitir archivos más grandes.
