# ✅ INSTRUCCIONES DE PRUEBA - DASHBOARD V2.0

## 📋 Antes de Empezar

Asegúrate que:
- [ ] XAMPP está corriendo (Apache + MySQL)
- [ ] Tienes acceso a http://localhost/cumplimiento
- [ ] Tienes usuario admin para configurar plazos
- [ ] Tienes usuario normal para usar dashboard

---

## 🧪 Test 1: Verificar Instalación

**Objetivo**: Confirmar que todas las componentes están instaladas

### Pasos:
1. Abre en navegador: http://localhost/cumplimiento/verificacion_sistema.php
2. Ingresa como ADMIN

### ✅ Esperado:
```
- ItemPlazo: ✅ Cargada correctamente
- ItemConPlazo: ✅ Cargada correctamente
- Item: ✅ Cargada correctamente
- Documento: ✅ Cargada correctamente

- item_plazos: ✅ Existe
- documento_seguimiento: ✅ Existe
- items: ✅ Existe
- documentos: ✅ Existe
- usuarios: ✅ Existe

- usuario/dashboard.php: ✅ OK
- usuario/enviar_documento.php: ✅ OK
- admin/items/plazos.php: ✅ OK
- classes/ItemPlazo.php: ✅ OK
- classes/ItemConPlazo.php: ✅ OK
```

❌ Si algo está rojo: Contacta soporte

---

## 🧪 Test 2: Dashboard Usuario (Sin Plazos)

**Objetivo**: Verificar que el dashboard carga sin errores

### Pasos:
1. **Logout** si estás en admin (esquina superior derecha)
2. **Login** como usuario normal
3. Abre: http://localhost/cumplimiento/usuario/dashboard.php

### ✅ Esperado:
```
- ✅ Página carga sin errores
- ✅ 5 pestañas visibles (Mensual, Trimestral, Semestral, Anual, Ocurrencia)
- ✅ Pestaña "Mensual" seleccionada por defecto
- ✅ Selector de Mes y Año visible
- ✅ Tabla con columnas: Numeración, Nombre, Mes Carga, Plazo, Fecha Envío, Portal
- ✅ Botones "Cargar" visibles en items asignados
- ✅ Si no hay items: "No hay items asignados"

❌ Si hay errores:
- Abre Verificación del Sistema (Test 1)
- Revisa que los items estén asignados al usuario
```

---

## 🧪 Test 3: Configurar Primer Plazo

**Objetivo**: Crear un plazo para testing

### Pasos:
1. **Logout** como usuario
2. **Login** como ADMIN
3. Abre: http://localhost/cumplimiento/admin/items/plazos.php
4. En dropdown "Item", selecciona el PRIMER item
5. En dropdown "Año", deja 2024 (o año actual)
6. En tabla, busca "Enero" y haz clic "Editar"
7. En modal:
   - Plazo Interno: **10/01/2024**
   - Fecha Carga Portal: **15/01/2024**
8. Haz clic "Guardar"

### ✅ Esperado:
```
- ✅ Modal se abre
- ✅ Campos se llenan
- ✅ Clic en Guardar funciona
- ✅ Página vuelve a cargar
- ✅ En tabla, Enero ahora muestra las fechas
```

### ❌ Si hay problemas:
- Verifica que hayas seleccionado Item y Año
- Verifica formato de fecha (DD/MM/YYYY)
- Revisa la consola del navegador (F12 → Console)

---

## 🧪 Test 4: Verificar Plazo en Dashboard

**Objetivo**: Confirmar que el plazo aparece en el dashboard del usuario

### Pasos:
1. **Logout** como admin
2. **Login** como usuario (el mismo del Test 2)
3. Abre: http://localhost/cumplimiento/usuario/dashboard.php
4. **Cambiar mes a Enero** (si no está):
   - Dropdown "Mes": Enero
   - Clic en Año: 2024
   - Se actualiza automáticamente
5. **Buscar el item que configuraste**
6. **Verificar la fila**:
   - Columna "Plazo Interno": **10/01/2024**
   - Columna "Fecha Envío": **Sin envío** (aún)

### ✅ Esperado:
```
Fila del Item:
┌─────────┬────────┬────────┬────────┬─────────┬────────┐
│Numerac. │ Nombre │ Enero  │10/01.. │Sin envío│15/01..│
├─────────┴────────┴────────┴────────┴─────────┴────────┤
│                              [Cargar]                  │
└────────────────────────────────────────────────────────┘
```

### ❌ Si no aparece:
- Verifica que el item esté asignado al usuario
- Verifica que configuraste enero (no otro mes)
- Recarga la página (Ctrl+F5)

---

## 🧪 Test 5: Cargar Documento

**Objetivo**: Probar carga de documento y registro automático de fecha

### Pasos:
1. En dashboard (Test 4), haz clic en **[Cargar]**
2. Modal se abre con:
   - Título: "Cargar Documento: [Nombre del Item]"
   - Campos: Título, Descripción, Archivo
3. Completa:
   - **Título**: "Test Enero 2024"
   - **Descripción**: "Documento de prueba"
   - **Archivo**: Selecciona un PDF pequeño
4. Haz clic **[Enviar Documento]**

### ✅ Esperado:
```
- ✅ Modal se cierra
- ✅ Página muestra: "Documento cargado exitosamente" (verde)
- ✅ En la tabla, columna "Fecha Envío":
     Aparece: "09/01/2024 14:30" (o fecha/hora actual)
     En lugar de: "Sin envío"
```

### ❌ Si hay error:
```
"Error al cargar el documento. Verifique que el formato sea correcto"
- Verifica formato: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG
- Verifica tamaño: Máximo 10MB
- Verifica carpeta uploads/ tenga permisos de escritura
```

---

## 🧪 Test 6: Verificar en Base de Datos

**Objetivo**: Confirmar que se registró todo correctamente

### Pasos:
1. Abre phpMyAdmin: http://localhost/phpmyadmin
2. Selecciona BD: "cumplimiento"
3. **Tabla: documento_seguimiento**
   - Busca últimas filas
   - Verifica: 
     - documento_id: (número)
     - item_id: (el item testeado)
     - usuario_id: (usuario login)
     - ano: 2024
     - mes: 1 (enero)
     - fecha_envio: (fecha/hora actual)
     - estado: "pendiente"
4. **Tabla: documentos**
   - Verifica que se creó el documento
   - Verifica archivo está en uploads/

### ✅ Esperado:
```
documento_seguimiento:
├─ documento_id: 5 (o siguiente)
├─ item_id: 1 (o el item testeado)
├─ usuario_id: 1 (o el usuario actual)
├─ ano: 2024
├─ mes: 1
├─ fecha_envio: 2024-01-09 14:30:45
└─ estado: pendiente

documentos:
├─ id: 5
├─ titulo: "Test Enero 2024"
├─ ruta_archivo: "uploads/...pdf"
└─ usuario_id: 1
```

---

## 🧪 Test 7: Cambiar Mes

**Objetivo**: Verificar selector de mes funciona

### Pasos:
1. En dashboard, pestaña Mensual
2. Dropdown Mes: **Selecciona Febrero**
3. Año: 2024
4. Página se actualiza

### ✅ Esperado:
```
- ✅ Tabla cambia a mostrar datos de Febrero
- ✅ Columna "Mes Carga": "Febrero 2024"
- ✅ El item testeado aparece sin envío (no cargamos febrero)
```

---

## 🧪 Test 8: Verificar Otras Pestañas

**Objetivo**: Confirmar que otras periodicidades funcionan

### Pasos:
1. En dashboard, haz clic en **[Trimestral]**
2. Verifica: Items trimestrales aparecen sin selector mes
3. Haz clic en **[Semestral]**
4. Haz clic en **[Anual]**
5. Haz clic en **[Ocurrencia]**

### ✅ Esperado:
```
- ✅ Cada pestaña carga sin errores
- ✅ Solo Mensual tiene selector mes
- ✅ Otras pestañas usan período actual
- ✅ Botones "Cargar" funcionan en todas
```

---

## ✅ RESUMEN DE TESTS

| Test | Objetivo | Estado |
|------|----------|--------|
| 1 | Verificar instalación | ⬜ Pendiente |
| 2 | Dashboard carga | ⬜ Pendiente |
| 3 | Configurar plazo | ⬜ Pendiente |
| 4 | Plazo en dashboard | ⬜ Pendiente |
| 5 | Cargar documento | ⬜ Pendiente |
| 6 | Verificar BD | ⬜ Pendiente |
| 7 | Cambiar mes | ⬜ Pendiente |
| 8 | Otras pestañas | ⬜ Pendiente |

**Marca con ✅ mientras completas cada test**

---

## 🐛 Solución de Problemas

### "Página en blanco"
- Abre Herramientas de Desarrollo (F12)
- Busca errores en Console (Rojo)
- Verifica logs/error_log del servidor

### "No aparecen los items"
- Verifica en admin/items que items existan
- Verifica en item_usuarios que estén asignados
- Recarga página (Ctrl+F5)

### "Error al cargar archivo"
- Verifica formato: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG
- Verifica tamaño: Max 10MB
- Verifica permisos en carpeta uploads/

### "No aparece el plazo"
- Verifica en admin/items/plazos.php que esté configurado
- Verifica mes y año correctos
- Recarga página (Ctrl+F5)

---

## 📞 Contacto

Si algún test falla:
1. Anota qué test falló
2. Anota qué error viste
3. Abre verificacion_sistema.php
4. Verifica qué está en rojo
5. Contacta con la información

---

**¡Éxito en las pruebas!** 🎉
