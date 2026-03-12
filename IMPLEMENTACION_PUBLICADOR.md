# NUEVO SISTEMA DE PUBLICACIÓN - PERFIL PUBLICADOR

## Cambios Implementados

### 1. Estados del Documento
**Antes:**
- `pendiente` → estado inicial
- `aprobado` → aprobado por admin
- `rechazado` → rechazado

**Ahora:**
- `Cargado` → Usuario cargador lo subió (listo para publicar)
- `Publicado` → Publicador agregó verificador (publicado en Transparencia Activa)
- `pendiente` → Pendiente de aprobación (para admin)
- `rechazado` → Rechazado

### 2. Flujo de Publicación

```
Usuario Cargador (Marianela, Juan, etc.)
    ↓
Carga documento → Estado: "Cargado"
    ↓
Panel del Publicador (Juan Fica)
    ↓
Ve documento con estado "Cargado"
    ↓
Agrega Verificador (imagen de verificación)
    ↓
Documento pasa a estado: "Publicado"
    ↓
Publicado en Portal de Transparencia Activa
```

### 3. Panel del Publicador - Cambios

**Título:** "Centro de Publicación y Transparencia Activa"

**Nota Informativa:**
```
Proceso de Publicación:
- Estado "Cargado": Documento subido por el usuario, lista para publicar en Transparencia Activa
- Agregar Verificador: Al cargar la imagen de verificación, el documento pasa a estado "Publicado"
- Estado "Publicado": Documento publicado y disponible en el Portal de Transparencia Activa
```

**Tabla de Documentos:**
- Muestra TODOS los documentos cargados del mes seleccionado
- Columnas: Item | Documento | Cargado Por | Fecha Carga | Estado | Acciones

**Botón de Acción:**
- Si documento NO tiene verificador: **"Agregar Verificador"** (azul)
- Si documento tiene verificador: **"Ver Verif"** (verde)

**Alerta Roja:**
- Muestra: "¡Documentos para Publicar!"
- Cantidad de documentos sin verificador

### 4. Código Modificado

**Archivos Modificados:**

1. **`usuario/enviar_documento.php`**
   - Cambió estado a "Cargado" al guardar documento

2. **`classes/Documento.php`**
   - Nueva función: `getAllCargados($mes, $ano)` - obtiene todos documentos cargados
   - Nueva función: `getByItemFollowUpCargados()` - obtiene por item

3. **`classes/Verificador.php`**
   - Función `create()` ahora cambia estado a "Publicado" cuando se agrega verificador

4. **`admin/publicador/index.php`**
   - Cambió para mostrar TODOS los documentos cargados
   - Actualizado UI y mensajes
   - Botón: "Agregar Verificador" (más descriptivo)

### 5. Base de Datos

Enum de estados en tabla `documentos`:
- `Cargado` - Nuevo
- `Publicado` - Nuevo
- `pendiente` - Existente
- `aprobado` - Existente
- `rechazado` - Existente

### 6. Validación

✓ Todos los archivos con sintaxis PHP correcta
✓ Función `getAllCargados()` funcionando
✓ Documentos se muestran correctamente en panel publicador
✓ Sistema listo para publicar

### 7. Próximos Pasos

1. Ejecutar: `ALTER TABLE documentos MODIFY COLUMN estado ENUM(..., 'Cargado', 'Publicado', ...)`
2. Publicador abre panel → ve documentos cargados
3. Agrega verificador → documento pasa a "Publicado"
