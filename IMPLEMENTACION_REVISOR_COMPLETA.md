# Sistema de Revisión Previa de Documentos - Implementación Completa

## Descripción General

Se ha implementado un nuevo sistema de revisión previa de documentos con el perfil "Revisor". Este sistema es **completamente opcional** y se activa/desactiva desde la configuración general del sistema.

## Características Implementadas

### 1. Perfil Revisor
- Nuevo perfil de usuario: **Revisor de Documentos**
- Acceso exclusivo a dashboard de revisión
- Capacidad de aprobar o enviar observaciones a documentos

### 2. Configuración General
- Switch en `admin/configuracion` → Tab "General"
- **Activar proceso de revisión previa**: Habilita/deshabilita el sistema
- Estados del sistema:
  - **Desactivado** (valor: 0): Sistema funciona como antes, sin revisor
  - **Activado** (valor: 1): Los documentos pueden ser revisados antes de publicarse

### 3. Estados de Revisión

#### Documento Aprobado
- ✅ Badge verde: "Aprobado"
- Publicador puede cargar verificador normalmente
- Visible en el dashboard del publicador

#### Documento Observado
- ⚠️ Badge rojo: "Observado"
- Publicador NO puede cargar verificador
- Mensaje de bloqueo: "No se puede publicar: El documento tiene observaciones del revisor. Debe ser corregido y re-aprobado."
- Requiere corrección y nueva aprobación

#### Sin Revisar
- Sin badge especial
- Publicador puede cargar verificador (revisión es opcional)
- El proceso continúa normalmente

### 4. Flujo de Trabajo

```
Cargador de Información
       ↓
   [Sube documento]
       ↓
    Revisor ← (OPCIONAL, si está activado)
       ↓
  ¿Aprobado u Observado?
       ↓
   Publicador
       ↓
[Agrega Verificador]
       ↓
    Publicado
```

## Archivos Modificados

### Backend

1. **classes/Revisor.php** (NUEVO)
   - Clase completa con métodos:
     - `aprobar($documento_id)`: Aprueba un documento
     - `observar($documento_id, $observaciones)`: Marca como observado
     - `getDocumentosPendientes()`: Lista documentos para revisar
     - `puedePublicar($documento_id)`: Valida si se puede publicar (STATIC)
     - `estaActivado()`: Verifica si la revisión está activa (STATIC)

2. **admin/publicador/cargar_verificador.php**
   - Agregada validación con `Revisor::puedePublicar()`
   - Bloquea carga de verificador si documento está observado
   - Muestra mensaje específico de error

3. **admin/publicador/index.php**
   - Consulta estado de revisión en cada documento
   - Muestra badges "Aprobado" o "Observado" junto al botón "Ver"
   - Verde para aprobado, rojo para observado

4. **admin/configuracion/index.php**
   - Agregado switch "Activar proceso de revisión previa"
   - Guardado en tabla `configuracion` con clave `activar_revision_previa`
   - Información sobre cómo funciona el sistema

### Frontend

5. **usuario/dashboard_revisor.php** (NUEVO)
   - Dashboard completo para revisores
   - Estadísticas: Total, Pendientes, Aprobados, Observados
   - Filtros por estado de revisión
   - Tabla con documentos y acciones
   - Modales para aprobar y observar

6. **usuario/procesar_revision.php** (NUEVO)
   - Endpoint para procesar aprobaciones y observaciones
   - Valida perfil de revisor
   - Registra en base de datos
   - Mensajes de éxito/error

### Autenticación

7. **includes/check_auth.php**
   - Agregado routing para perfil 'revisor' → dashboard_revisor.php

8. **seleccionar_perfil.php**
   - Nombre cambiado: 'Administrativo' → 'Administrador'
   - Agregado perfil 'Revisor de Documentos' con icono clipboard-check

## Base de Datos

### Migraciones Requeridas

**IMPORTANTE**: Ejecutar las siguientes migraciones en orden:

1. **sql/migration_configuracion_general.sql**
   ```sql
   CREATE TABLE IF NOT EXISTS configuracion (
       id INT AUTO_INCREMENT PRIMARY KEY,
       clave VARCHAR(100) NOT NULL UNIQUE,
       valor TEXT NOT NULL,
       descripcion TEXT,
       fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
       modificado_por INT,
       FOREIGN KEY (modificado_por) REFERENCES usuarios(id)
   );
   
   INSERT INTO configuracion (clave, valor, descripcion) VALUES
   ('max_file_size_mb', '200', 'Tamaño máximo de archivo permitido en MB'),
   ('activar_revision_previa', '0', 'Activar proceso de revisión previa de documentos (0=No, 1=Sí)');
   ```

2. **sql/migration_revisor_perfil.sql**
   ```sql
   CREATE TABLE IF NOT EXISTS revisiones_documentos (
       id INT AUTO_INCREMENT PRIMARY KEY,
       documento_id INT NOT NULL,
       revisor_id INT NOT NULL,
       estado ENUM('aprobado', 'observado') NOT NULL,
       observaciones TEXT,
       fecha_revision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
       FOREIGN KEY (revisor_id) REFERENCES usuarios(id),
       INDEX idx_documento (documento_id),
       INDEX idx_revisor (revisor_id),
       INDEX idx_estado (estado)
   );
   
   -- Agregar revisor a perfiles existentes
   UPDATE usuarios SET perfiles = CONCAT(perfiles, ',revisor') WHERE id = [ID_USUARIO];
   ```

### Verificar Migraciones

```sql
-- Verificar tabla configuracion
SHOW TABLES LIKE 'configuracion';
SELECT * FROM configuracion WHERE clave IN ('max_file_size_mb', 'activar_revision_previa');

-- Verificar tabla revisiones_documentos
SHOW TABLES LIKE 'revisiones_documentos';
DESC revisiones_documentos;

-- Verificar usuarios con perfil revisor
SELECT id, usuario, nombre, perfiles FROM usuarios WHERE FIND_IN_SET('revisor', perfiles) > 0;
```

## Configuración de Usuarios

### Asignar Perfil Revisor

Para que un usuario pueda usar el sistema de revisión:

1. **Opción 1: SQL Directo**
   ```sql
   UPDATE usuarios 
   SET perfiles = CONCAT(perfiles, ',revisor') 
   WHERE id = [ID_USUARIO];
   ```

2. **Opción 2: Interface Administrativa** (si existe gestión de perfiles)
   - Ir a administración de usuarios
   - Editar usuario
   - Agregar perfil "Revisor"

## Testing

### Caso 1: Revisor Desactivado
1. Ir a `admin/configuracion` → Tab "General"
2. Desmarcar "Activar proceso de revisión previa"
3. Guardar
4. Verificar que publicador puede cargar verificadores normalmente
5. No debe haber badges de revisión

### Caso 2: Revisor Activado - Documento Aprobado
1. Activar revisión previa en configuración
2. Cargador sube un documento
3. Revisor ingresa a su dashboard
4. Aprobar el documento
5. Publicador ve badge verde "Aprobado"
6. Publicador puede cargar verificador normalmente

### Caso 3: Revisor Activado - Documento Observado
1. Revisor marca documento como "Observado"
2. Ingresa observaciones
3. Publicador ve badge rojo "Observado"
4. Publicador intenta cargar verificador
5. Sistema bloquea con mensaje de error
6. Cargador debe corregir y volver a subir
7. Revisor debe aprobar nuevamente

### Caso 4: Documento Sin Revisar
1. Cargador sube documento
2. Revisor NO lo revisa
3. Publicador puede cargar verificador (revisión es opcional)
4. Sin badges de revisión

## Mantenimiento

### Logs de Revisión
Todas las revisiones quedan registradas en `revisiones_documentos`:
```sql
SELECT 
    rd.id,
    d.titulo AS documento,
    u.nombre AS revisor,
    rd.estado,
    rd.observaciones,
    rd.fecha_revision
FROM revisiones_documentos rd
JOIN documentos d ON rd.documento_id = d.id
JOIN usuarios u ON rd.revisor_id = u.id
ORDER BY rd.fecha_revision DESC;
```

### Métricas
```sql
-- Total de documentos revisados
SELECT COUNT(*) FROM revisiones_documentos;

-- Por estado
SELECT estado, COUNT(*) as total 
FROM revisiones_documentos 
GROUP BY estado;

-- Por revisor
SELECT u.nombre, COUNT(*) as total_revisiones
FROM revisiones_documentos rd
JOIN usuarios u ON rd.revisor_id = u.id
GROUP BY u.id, u.nombre
ORDER BY total_revisiones DESC;
```

## Notas Importantes

1. **Retrocompatibilidad**: El sistema funciona sin problemas si las tablas no existen
2. **Opcional por Defecto**: La revisión está desactivada por defecto
3. **No Bloqueante**: Los documentos sin revisar se pueden publicar
4. **Auditable**: Todas las revisiones quedan registradas con fecha y usuario

## Próximos Pasos

1. Ejecutar migraciones SQL
2. Asignar perfiles de revisor a usuarios
3. Configurar sistema desde admin/configuracion
4. Realizar pruebas de flujo completo
5. Capacitar usuarios en nuevo proceso

---

**Fecha de Implementación**: 2025
**Versión**: 1.0
**Sistema**: Transparencia Activa - Gobierno Regional
