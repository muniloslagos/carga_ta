# Instrucciones para Actualizar la Base de Datos

## Cambios Recientes (2026-05-15)

Se agregaron nuevas funcionalidades que requieren actualizar la base de datos:

### 1. Tabla de Configuración General
**Archivo:** `sql/migration_configuracion_general.sql`

Esta tabla almacena configuraciones generales del sistema, incluyendo:
- Tamaño máximo de archivos a cargar
- Otras configuraciones futuras

### 2. Verificar Tabla de Configuración SMTP
**Archivo:** `sql/migration_smtp_config.sql`

Si aún no ejecutaste esta migración, hazlo también para habilitar la configuración de correo electrónico.

---

## Cómo Ejecutar las Migraciones

### Opción 1: Desde phpMyAdmin
1. Abre phpMyAdmin en tu navegador: http://localhost/phpmyadmin
2. Selecciona la base de datos del sistema (ej: `cumplimiento`)
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de:
   - `sql/migration_configuracion_general.sql`
   - (Si no lo hiciste antes) `sql/migration_smtp_config.sql`
5. Click en "Continuar"

### Opción 2: Desde línea de comandos (MySQL)
```bash
# En XAMPP (Windows)
cd C:\xampp\htdocs\cumplimiento

# Ejecutar migración de configuración general
mysql -u root -p nombre_base_datos < sql/migration_configuracion_general.sql

# Ejecutar migración SMTP (si no lo hiciste antes)
mysql -u root -p nombre_base_datos < sql/migration_smtp_config.sql
```

### Opción 3: Desde el terminal de MySQL
```bash
# Conectar a MySQL
mysql -u root -p

# Seleccionar base de datos
USE nombre_base_datos;

# Ejecutar el contenido del archivo manualmente
SOURCE C:/xampp/htdocs/cumplimiento/sql/migration_configuracion_general.sql;
SOURCE C:/xampp/htdocs/cumplimiento/sql/migration_smtp_config.sql;
```

---

## Verificar que las Tablas se Crearon Correctamente

Ejecuta esta consulta en MySQL:

```sql
-- Verificar tabla configuracion
DESCRIBE configuracion;

-- Verificar tabla configuracion_smtp
DESCRIBE configuracion_smtp;

-- Ver configuración actual
SELECT * FROM configuracion;
SELECT * FROM configuracion_smtp;
```

Deberías ver:
- **Tabla `configuracion`**: Con el registro `max_file_size_mb = 200`
- **Tabla `configuracion_smtp`**: Con la estructura para configuración de correo

---

## Después de Ejecutar las Migraciones

1. **Verifica en el panel administrativo:**
   - Ve a https://app.muniloslagos.cl/carga_ta/admin/configuracion/
   - Deberías ver la pestaña "General" con la configuración de tamaño máximo de archivo
   - Deberías ver la pestaña "SMTP" con la configuración de correo

2. **Si hay errores:**
   - Revisa los logs de errores de PHP: `C:\xampp\apache\logs\error.log`
   - Verifica que las tablas se crearon correctamente
   - Asegúrate de que el usuario de la BD tiene permisos de CREATE TABLE

---

## Tablas Creadas

### Tabla: `configuracion`
```sql
- id (INT, PK, AUTO_INCREMENT)
- clave (VARCHAR(100), UNIQUE)
- valor (TEXT)
- descripcion (TEXT)
- fecha_modificacion (TIMESTAMP)
- modificado_por (INT, FK a usuarios)
```

### Tabla: `configuracion_smtp`
```sql
- id (INT, PK, AUTO_INCREMENT)
- smtp_host (VARCHAR(255))
- smtp_port (INT)
- smtp_usuario (VARCHAR(255))
- smtp_password (VARCHAR(255))
- smtp_encriptacion (ENUM)
- smtp_de_correo (VARCHAR(255))
- smtp_de_nombre (VARCHAR(255))
- smtp_activo (TINYINT)
- smtp_verificado (TINYINT)
- fecha_modificacion (TIMESTAMP)
- modificado_por (INT, FK a usuarios)
```

---

## Notas Importantes

- ⚠️ **Estas migraciones son seguras**: Usan `CREATE TABLE IF NOT EXISTS` e `INSERT ... ON DUPLICATE KEY UPDATE`, por lo que no sobrescribirán datos existentes.
- ✅ **Valores por defecto**: La configuración se inicializa con el tamaño máximo de archivo en 200 MB (el valor actual en el código).
- 🔄 **Puedes ejecutarlas varias veces**: No causarán errores si ya existen las tablas.
