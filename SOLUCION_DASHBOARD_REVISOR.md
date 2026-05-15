# Solución: Dashboard del Revisor Sin Documentos

## Problema
El dashboard del revisor (`usuario/dashboard_revisor.php`) no muestra documentos incluso con filtros aplicados.

## Cambios Realizados

### 1. Corrección de Referencias de Columnas ✅
- **Commit 1efa469**: Corregida referencia `fecha_carga` → `fecha_subida` en tabla de documentos
- Mejora en lógica de filtros para manejar correctamente los tres estados

### 2. Mejora en Sistema de Filtros ✅
- **Commit 17e770d**: 
  - Nuevo método `getTodosDocumentos()` para filtro "Todos"
  - Modificado `getDocumentosPendientes()` para filtrar solo documentos sin revisión (`rd.documento_id IS NULL`)
  - `getDocumentosRevisados()` solo documentos con revisión del revisor actual
  - Agregado contador de documentos encontrados en el header
  - Mensaje mejorado cuando no hay documentos, mostrando filtros activos y sugerencias

### 3. Herramienta de Diagnóstico ✅  
- **Commit d662465**: Creado `diagnostico_revisor.php`
  - Verifica configuración del sistema
  - Muestra estructura de tablas y conteo de registros
  - Documentos por año y mes
  - Estado de revisiones (aprobados/observados/sin revisar)
  - Últimos 10 documentos en el sistema
  - Recomendaciones automáticas según estado

## Cómo Usar la Herramienta de Diagnóstico

### Paso 1: Acceder a la Herramienta
En producción, accede a:
```
https://app.muniloslagos.cl/carga_ta/diagnostico_revisor.php
```

### Paso 2: Revisar la Información Mostrada
La herramienta mostrará:

#### 📊 Configuración del Sistema
- `activar_revision_previa`: Debe estar en **1** (activado)
- `max_file_size_mb`: Límite de tamaño de archivos

#### 🗄️ Estructura de Base de Datos
Verificará que existan estas tablas:
- `documentos` ✓
- `documento_seguimiento` ✓
- `items_transparencia` ✓
- `revisiones_documentos` ✓
- `usuarios` ✓

#### 📅 Documentos por Año
Mostrará cuántos documentos hay en cada año (2024, 2025, 2026, etc.)

#### 📆 Documentos por Mes (Año Actual)
Conteo de documentos para cada mes del año actual

#### ✅ Estado de Revisiones
- Total de documentos
- Documentos aprobados
- Documentos observados
- Documentos sin revisar

#### 📄 Últimos 10 Documentos
Tabla con los documentos más recientes y su estado

## Causas Comunes y Soluciones

### ❌ Causa 1: Revisión Desactivada
**Síntoma**: "Funcionalidad de Revisión Desactivada"
**Solución**: 
1. Ingresar como Administrador
2. Ir a **Configuración del Sistema > General**
3. Activar "Revisión Previa de Documentos"

### ❌ Causa 2: No Hay Documentos en el Año Actual (2026)
**Síntoma**: `diagnostico_revisor.php` muestra documentos en 2024/2025 pero no en 2026
**Solución**:
1. En `dashboard_revisor.php`, cambiar el filtro de año a **2025** o **2024**
2. Los documentos aparecerán para años anteriores
3. Esto es normal si el sistema comenzó a usarse en años anteriores

### ❌ Causa 3: Tablas No Migradas
**Síntoma**: `diagnostico_revisor.php` muestra que faltan tablas
**Solución**:
1. Ejecutar migraciones SQL pendientes:
   ```sql
   -- Ejecutar en orden:
   sql/migration_configuracion_general.sql
   sql/migration_revisor_perfil_safe.sql
   sql/migration_agregar_perfil_revisor.sql
   ```
2. Verificar nuevamente con `diagnostico_revisor.php`

### ❌ Causa 4: No Hay Documentos Cargados
**Síntoma**: Todas las tablas están vacías
**Solución**:
1. Ingresar con perfil **Cargador de Información**
2. Cargar documentos para items de transparencia
3. Los documentos aparecerán en el dashboard del revisor

### ❌ Causa 5: Filtros Demasiado Restrictivos
**Síntoma**: El contador muestra "0 documento(s) encontrado(s)"
**Solución**:
1. Cambiar filtro de estado a **"Todos"**
2. Seleccionar **"Todos los meses"** en vez de un mes específico
3. Probar con años anteriores (2024, 2025)

## Verificación Rápida desde SQL

Si tienes acceso a phpMyAdmin o consola MySQL, ejecuta estas queries:

```sql
-- 1. Verificar configuración
SELECT * FROM configuracion WHERE clave = 'activar_revision_previa';
-- Resultado esperado: valor = '1'

-- 2. Contar documentos por año
SELECT ds.ano, COUNT(DISTINCT d.id) as total 
FROM documentos d
INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
WHERE d.estado IN ('pendiente', 'aprobado')
GROUP BY ds.ano
ORDER BY ds.ano DESC;
-- Esto mostrará cuántos documentos hay en cada año

-- 3. Ver documentos del año actual con todas las columnas necesarias
SELECT d.id, d.titulo, ds.mes, ds.ano, i.nombre as item_nombre, 
       d.estado, d.fecha_subida, rd.estado as estado_revision
FROM documentos d
INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
INNER JOIN items_transparencia i ON d.item_id = i.id
LEFT JOIN revisiones_documentos rd ON d.id = rd.documento_id
WHERE ds.ano = 2026
ORDER BY d.fecha_subida DESC
LIMIT 10;
-- Si esto devuelve 0 filas, no hay documentos en 2026

-- 4. Verificar tabla revisiones_documentos existe
SHOW TABLES LIKE 'revisiones_documentos';
-- Debe devolver 1 fila
```

## Mejoras Implementadas en el Dashboard

### Contador de Documentos
El header del card ahora muestra:
```
📋 Documentos Pendientes de Revisión - Todos los meses 2026
[12 documento(s) encontrado(s)]
```

### Mensaje Mejorado Sin Documentos
Cuando no hay documentos, se muestra:
```
📥 No hay documentos para mostrar

Filtros actuales: Año 2026 - Todos los meses - Estado Todos

💡 Intenta cambiar el año, seleccionar "Todos los meses" o cambiar el filtro de estado
```

### Filtros Corregidos
- **Todos**: Muestra TODOS los documentos (con y sin revisión)
- **Pendientes**: Solo documentos SIN revisar
- **Revisados**: Solo documentos que YA fueron revisados por el revisor actual

## Próximos Pasos Recomendados

1. **Ejecutar `diagnostico_revisor.php` en producción** para identificar la causa exacta
2. **Revisar el año con documentos** según lo que muestre el diagnóstico
3. **Verificar configuración** si está desactivada
4. **Ejecutar migraciones** si faltan tablas
5. **Contactar a soporte** si después de estos pasos el problema persiste

## Archivos Modificados

### Código PHP
- `classes/Revisor.php`: Métodos de filtrado corregidos
- `usuario/dashboard_revisor.php`: UI mejorada con contador y mensajes
- `diagnostico_revisor.php`: Nueva herramienta de diagnóstico

### Commits
- `1efa469`: fix: Corregir referencia fecha_carga y logica de filtros
- `17e770d`: feat: Mejorar filtros revisor y agregar contador de documentos  
- `d662465`: feat: Agregar herramienta de diagnostico para sistema revisor
- **Todos pusheados a GitHub** ✅

## Soporte
Si después de seguir estos pasos el problema persiste:
1. Ejecutar `diagnostico_revisor.php` y capturar pantalla
2. Ejecutar las queries SQL de verificación y capturar resultados
3. Compartir la información para análisis adicional

---

**Última actualización**: 2026-01-19
**Estado**: ✅ Commits pusheados a GitHub - Listo para deployment
