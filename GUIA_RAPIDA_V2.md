# ⚡ GUÍA RÁPIDA - DASHBOARD V2.0

## 🚀 Acceso Inmediato

### Para Usuarios
- **URL**: http://localhost/cumplimiento/usuario/dashboard.php
- **Requisito**: Estar logueado como usuario
- **Qué verás**: 
  - 5 pestañas (Mensual, Trimestral, Semestral, Anual, Ocurrencia)
  - Tabla con items asignados
  - Botón "Cargar Documento" en cada fila

### Para Administradores
- **URL**: http://localhost/cumplimiento/admin/items/plazos.php
- **Requisito**: Estar logueado como admin
- **Qué verás**:
  - Selector de item y año
  - Tabla con 12 meses
  - Botón "Editar" para cada mes

### Diagnóstico
- **URL**: http://localhost/cumplimiento/verificacion_sistema.php
- **Para**: Verificar que todo está instalado correctamente

---

## 📋 Checklist de Funcionalidades

### ✅ Completado
- [x] Dashboard rediseñado con nuevas columnas
- [x] Selector de mes para items mensuales
- [x] Auto-selección de mes anterior
- [x] Tabla con 6 columnas de datos
- [x] Panel administrativo de plazos
- [x] Base de datos con nuevas tablas
- [x] Carga de documentos con registro automático
- [x] Validación de archivo + seguridad
- [x] Código sin errores de sintaxis

### 📋 Tablas de Datos

#### item_plazos
```
item_id  |  ano  |  mes  |  plazo_interno  |  fecha_carga_portal
---------|-------|-------|-----------------|--------------------
1        |  2024 |  1    |  2024-01-10    |  2024-01-15
1        |  2024 |  2    |  2024-02-10    |  2024-02-15
```

#### documento_seguimiento
```
documento_id  |  item_id  |  usuario_id  |  ano  |  mes  |  fecha_envio
--------------|-----------|--------------|-------|-------|------------------
1             |  1        |  1           |  2024 |  1    |  2024-01-09 14:30
2             |  1        |  1           |  2024 |  2    |  2024-02-08 10:15
```

---

## 🎯 Flujo de Uso Típico

### Caso 1: Usuario Cargando Documento

1. Entra a http://localhost/cumplimiento/usuario/dashboard.php
2. Ve pestaña "Mensual" (automática)
3. Mes seleccionado: Mes anterior (ej: Nov si es Dic)
4. Ve items con sus plazos
5. Haz clic "Cargar Documento"
6. Modal se abre con item pre-llenado
7. Sube título + archivo
8. Se registra fecha automáticamente

### Caso 2: Admin Configurando Plazo

1. Entra a http://localhost/cumplimiento/admin/items/plazos.php
2. Selecciona "Item 1" en dropdown
3. Selecciona "2024" en dropdown
4. Ve tabla con 12 meses
5. Haz clic "Editar" en Enero
6. Completa:
   - Plazo Interno: 10/01/2024
   - Fecha Carga Portal: 15/01/2024
7. Haz clic "Guardar"
8. Vuelve al dashboard → aparece el plazo

---

## 🔑 Claves del Sistema

### Mes Anterior (Auto-cálculo)
```
Hoy = 15 de Diciembre 2024
Mes Anterior = Noviembre 2024
Mostrar "Noviembre 2024" en dashboard
```

### Plazo Interno
- Lo configura el ADMIN
- Es una fecha límite para el usuario
- Aparece en dashboard automáticamente
- No se modifica por usuario

### Fecha Envío
- Se registra automáticamente cuando:
  1. Usuario sube documento
  2. Sistema captura la hora actual
  3. Se guarda en documento_seguimiento

### Fecha Carga Portal
- Lo configura el ADMIN
- Es cuándo se cargó al portal externo
- Información histórica/referencias

---

## 📊 Ejemplo Práctico

### Escenario: Item de Transparencia Mensual

**Item**: "Remuneraciones" (Item 1)
**Periodicidad**: Mensual
**Hoy**: 15 de Diciembre 2024

**Lo que ve el usuario**:
```
Dashboard → Mensual

Mes Carga: [Noviembre ▼] 2024

Numeración │ Nombre       │ Mes Carga  │ Plazo I.    │ Fecha Envío      │ Portal
-----------|--------------|------------|-------------|------------------|--------
1          │ Remuneracion │ Noviembre  │ 10/11/2024  │ 08/11/2024 14:30 │ 15/11
           │              │            │             │                  │
           │ Acción: [Cargar]
```

**Proceso**:
1. Admin configuró en plazos:
   - Item 1, Nov 2024, Plazo: 10/11, Portal: 15/11
2. Usuario sube documento el 08/11 a las 14:30
3. Dashboard registra automáticamente la fecha
4. Admin después puede cambiar "Fecha Carga Portal" si es diferente

---

## 🛠️ Mantenimiento

### Respaldo
```
Tablas importantes:
- item_plazos
- documento_seguimiento
- documentos (ya existía)
```

### Limpieza
```
No elimines tablas, solo datos antiguos:
- DELETE FROM item_plazos WHERE ano < 2024
- DELETE FROM documento_seguimiento WHERE ano < 2024
```

### Monitoreo
```
Acceso: verificacion_sistema.php
- Verifica que tablas existan
- Verifica que clases cargen
- Verifica que archivos estén
```

---

## ❓ Preguntas Frecuentes

**P: ¿Cómo cambio el mes del dashboard?**
R: En la pestaña "Mensual" hay un selector de mes/año

**P: ¿Quién configura los plazos?**
R: Solo los administradores, en admin/items/plazos.php

**P: ¿Se registra automáticamente la fecha de envío?**
R: Sí, cuando el usuario sube un documento

**P: ¿Puedo cambiar la fecha de envío manualmente?**
R: No, se registra automáticamente con la hora actual

**P: ¿Qué significa "Plazo Interno"?**
R: Es la fecha límite que configura el admin para que el usuario envíe

**P: ¿Qué significa "Fecha Carga Portal"?**
R: Cuándo se cargó el documento al portal externo (info histórica)

---

## 📱 Compatibilidad

- ✅ Chrome/Edge (últimas versiones)
- ✅ Firefox (últimas versiones)
- ✅ Safari (últimas versiones)
- ✅ Responsive (adaptable a móvil)
- ✅ PHP 7.4+
- ✅ MySQL 5.7+

---

## 📞 Contacto/Soporte

Si algo no funciona:
1. Abre verificacion_sistema.php
2. Verifica que todo esté en verde
3. Si hay un problema, aparecerá en rojo

---

**¡Listo para usar!** 🎉
