# Dashboard Rediseñado - Manual de Uso

## Cambios Realizados

### 1. **Nueva Estructura de Base de Datos**

Se han creado dos nuevas tablas:

#### `item_plazos`
Almacena los plazos internos y fechas de carga al portal por item, mes y año:
- `item_id`: ID del item
- `ano`: Año (ej: 2024)
- `mes`: Mes (1-12)
- `plazo_interno`: Fecha límite interna para envío
- `fecha_carga_portal`: Fecha en que se cargó al portal externo

**Llave única**: `(item_id, ano, mes)` - Asegura un solo plazo por item/mes/año

#### `documento_seguimiento`
Registra el seguimiento de cada documento enviado:
- `documento_id`: ID del documento
- `item_id`: ID del item
- `usuario_id`: Usuario que envió
- `ano`: Año del período
- `mes`: Mes del período
- `fecha_envio`: Cuándo se envió
- `fecha_carga_portal`: Cuándo se cargó al portal
- `estado`: Estado (pendiente, aprobado, rechazado)

### 2. **Dashboard del Usuario - Cambios Principales**

#### Antes
- Pestañas por periodicidad (Mensual, Trimestral, Semestral, Anual, Ocurrencia)
- Items listados sin información de plazos
- Sin selector de mes

#### Ahora
- **Pestaña Mensual**: Incluye selector de mes y año
  - Muestra automáticamente el mes anterior como "Mes de Carga"
  - Puede cambiarse el mes con el selector
  
- **Columnas del Listado**:
  1. **Numeración**: Código del item (ej: 1, 1.1, 1.2, 2, etc.)
  2. **Nombre Item**: Nombre descriptivo
  3. **Mes Carga**: Mes actual seleccionado (ej: "Noviembre 2024")
  4. **Plazo Interno**: Fecha límite configurada por administrador
  5. **Fecha Envío**: Cuándo se envió el documento (se auto-llena al cargar)
  6. **Carga Portal**: Cuándo se cargó al portal (configurado por admin)
  7. **Acciones**: Botón "Cargar" para subir documento

- **Otras Pestañas** (Trimestral, Semestral, Anual, Ocurrencia):
  - Listadas sin selector de mes
  - Se usan fechas de último envío del período actual

#### Modal de Carga
- Título: Nombre del item
- Campos:
  - Título del Documento (obligatorio)
  - Descripción (opcional)
  - Archivo (PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG)
- **Para items mensuales**: Se auto-selecciona el mes seleccionado del dashboard

### 3. **Panel Administrativo - Nueva Sección "Gestión de Plazos"**

Nueva página: `/admin/items/plazos.php`

**Funcionamiento**:
1. Selecciona un Item en el lado izquierdo
2. Selecciona el Año
3. Tabla mostrará todos los 12 meses del año
4. Haz clic en "Editar" para cada mes
5. Configura:
   - **Plazo Interno**: Fecha límite para que el usuario envíe
   - **Fecha Carga Portal**: Cuándo se cargó al portal externo

**Acceso**: Solo administradores

### 4. **Lógica de "Mes de Carga" (Mes Anterior)**

Para items **mensuales**:
- Automáticamente muestra el mes anterior al actual
- Ejemplo: Si estamos en Diciembre, muestra Noviembre como "Mes Carga"
- El usuario puede seleccionar otros meses con el selector

La lógica:
```
Mes actual - 1 = Mes de carga
Si enero (1) - 1 = 0 → Diciembre del año anterior
```

### 5. **Flujo de Carga de Documentos**

1. Usuario entra al Dashboard
2. Selecciona un mes (para items mensuales)
3. Ve el listado con plazos configurados
4. Hace clic en "Cargar Documento"
5. Se abre modal con el item pre-seleccionado
6. Sube archivo con título y descripción
7. Sistema registra:
   - Documento en tabla `documentos`
   - Seguimiento en `documento_seguimiento` con fecha_envio
   - El "Fecha Envío" aparece automáticamente en el dashboard

### 6. **Archivos Modificados**

- ✅ `usuario/dashboard.php` - Completamente rediseñado
- ✅ `usuario/enviar_documento.php` - Actualizado para registrar en documento_seguimiento
- ✅ `admin/items/plazos.php` - NUEVO archivo para gestión de plazos
- ✅ `migrate.php` - Crea las nuevas tablas
- ✅ `classes/ItemPlazo.php` - NUEVO para gestionar plazos
- ✅ `classes/ItemConPlazo.php` - NUEVO para recuperar datos consolidados

## Instalación/Actualización

1. **Ejecutar migración** (ya ejecutada):
   ```
   php migrate.php
   ```
   Esto crea las tablas `item_plazos` y `documento_seguimiento`

2. **Acceder al nuevo dashboard**:
   ```
   http://localhost/cumplimiento/usuario/dashboard.php
   ```

3. **Configurar plazos** (como administrador):
   ```
   http://localhost/cumplimiento/admin/items/plazos.php
   ```

## Funcionalidades Clave

### Para Usuarios
- ✅ Ver items con plazos internos configurados
- ✅ Cargar documentos por item/mes
- ✅ Ver historial de envíos (fecha envío, estado)
- ✅ Selector de mes para items mensuales

### Para Administradores
- ✅ Configurar plazo interno por item/mes/año
- ✅ Registrar fecha de carga al portal
- ✅ Ver historial de documento_seguimiento (próximo)

### Sistema
- ✅ Base de datos con seguimiento completo
- ✅ Cálculo automático de mes anterior
- ✅ Registro de todas las fechas relevantes
- ✅ Estados de documento (pendiente, aprobado, rechazado)

## Próximas Mejoras (Sugeridas)

1. Panel administrativo para:
   - Ver documentos pendientes de aprobación
   - Cambiar estado (aprobado/rechazado)
   - Ver historial de documento_seguimiento

2. Reportes de:
   - Documentos por periodo
   - Cumplimiento de plazos
   - Documentos pendientes por usuario

3. Notificaciones de:
   - Próximos plazos internos
   - Documentos rechazados
   - Cambios de estado

---

**Fecha de implementación**: 2024
**Versión**: 2.0
