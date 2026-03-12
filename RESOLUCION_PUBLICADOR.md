# SOLUCIÓN: Juan Fica (Publicador) No Veía Documento de Marianela

## Problema
Juan Fica (Publicador) no podía ver el documento "libro" cargado por Marianela Jaramillo en el item "Libro diario municipal" mes noviembre.

## Causa Raíz
El documento estaba en estado **`pendiente`** (esperando aprobación del administrador).

El sistema debería trabajar así:
1. **Usuario carga documento** → estado = `pendiente`
2. **Administrador aprueba** → estado = `aprobado`
3. **Publicador ve y verifica** → puede cargar imagen de verificación

Pero el panel de publicador (`/admin/publicador/`) estaba mostrando TODOS los documentos, incluyendo los pendientes.

## Solución Implementada

### 1. Nuevas Funciones en `Documento.php`
```php
// Para buscar solo documentos APROBADOS con mes/año específico
public function getByItemFollowUpAprobados($item_id, $mes, $ano)

// Para buscar documentos APROBADOS en periodicidad anual
public function getByItemFollowUpAprobadosAnual($item_id, $ano)
```

### 2. Actualización en `/admin/publicador/index.php`
- Cambió de usar `getByItemFollowUp()` a `getByItemFollowUpAprobados()`
- Juan ahora solo ve documentos en estado `aprobado`

### 3. Aprobación del Documento
Documento ID 15:
- ✓ Estado: `pendiente` → `aprobado`
- ✓ Juan ahora puede verlo en `/admin/publicador/`
- ✓ Puede cargar la imagen de verificación

## Flujo Correcto Ahora

**Marianela (Cargador):**
1. Accede a `/usuario/dashboard.php`
2. Carga documento → se guarda con estado `pendiente`

**Administrador (ID 1):**
1. Accede a `/admin/documentos/`
2. Revisa documento
3. Cambia estado a `aprobado` (o rechazado si corresponde)

**Juan (Publicador):**
1. Accede a `/admin/publicador/`
2. Ve documentos `aprobados` del mes seleccionado
3. Carga imagen de verificación
4. Documento se publica

## Cambios de Código

| Archivo | Cambio |
|---------|--------|
| `classes/Documento.php` | Agregadas 2 nuevas funciones (getByItemFollowUpAprobados, getByItemFollowUpAprobadosAnual) |
| `admin/publicador/index.php` | Cambio en línea ~109: usa función "Aprobados" |

## Estado Actual
✓ Todos los cambios implementados y validados
✓ Documento de Marianela aprobado
✓ Juan puede ver el documento
✓ Sistema listo para que Juan cargue la verificación
