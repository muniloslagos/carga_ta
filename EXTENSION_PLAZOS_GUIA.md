# 📅 GUÍA: Extensión de Plazos por Feriados

## 🎯 Objetivo

Permitir al administrador **ampliar manualmente los plazos** cuando existan feriados nacionales entre los días hábiles, documentando el motivo de la extensión.

---

## 🔧 Cambios Implementados

### 1. **Base de Datos**
- ✅ Nueva columna `motivo_extension` en tabla `item_plazos`
- ✅ Almacena justificación de extensiones (máx. 255 caracteres)
- ✅ Campo opcional

**Ejecutar migración:**
```bash
http://localhost/cumplimiento/sql/migration_extension_plazos.sql
```

### 2. **Clase ItemPlazo.php**
- ✅ Métodos `create()` y `update()` actualizados
- ✅ Manejo automático del campo `motivo_extension`

### 3. **Interfaz Admin (plazos.php)**
- ✅ Muestra **plazo automático calculado** (6° día hábil)
- ✅ Campo para ingresar **motivo de extensión**
- ✅ Visualización del motivo en la tabla cuando existe

---

## 📋 Cómo Usar

### **Caso 1: Items Mensual**

1. **Acceder**: `admin/items/plazos.php`
2. **Ir a sección**: "Items Mensual"
3. **Seleccionar año**: Dropdown superior
4. **Clic en "Editar"** para el mes deseado

**Modal mostrará:**
```
┌─ Configurar Plazo Mensual ─────────────────┐
│                                             │
│ Mes: Septiembre 2026                       │
│                                             │
│ [ℹ️ Plazo automático: 6 de octubre 2026]   │
│                                             │
│ Fecha de Vencimiento: [__________] *       │
│                                             │
│ Motivo de Extensión:                       │
│ [Ampliado por feriados 18 y 19 sept...]   │
│                                             │
│ [Cancelar] [Guardar]                       │
└─────────────────────────────────────────────┘
```

**Ejemplo práctico:**
- **Plazo automático**: 6 de octubre 2026
- **Feriados detectados**: 18-19 septiembre (Fiestas Patrias)
- **Acción**: Cambiar fecha a **8 de octubre 2026**
- **Motivo**: "Ampliado por feriados patrios 18 y 19 de septiembre"

---

### **Caso 2: Items Anual**

1. **Acceder**: `admin/items/plazos.php`
2. **Ir a sección**: "Items Anual"
3. **Buscar item** en la tabla
4. **Clic "Editar"** en el item deseado

**Modal mostrará:**
```
┌─ Configurar Plazo Anual ───────────────────┐
│                                             │
│ Item: Remuneraciones año anterior          │
│ Año: [2026 ▼]                              │
│                                             │
│ [ℹ️ Plazo automático: 6° día hábil feb]    │
│                                             │
│ Fecha de Vencimiento: [__________] *       │
│                                             │
│ Motivo de Extensión:                       │
│ [Ampliado por feriado año nuevo]          │
│                                             │
│ [Cancelar] [Guardar]                       │
└─────────────────────────────────────────────┘
```

---

## ✅ Visualización en Tabla

Cuando un plazo tiene **motivo de extensión**, se muestra así:

```
┌─────────────────────────────────────────────────┐
│ Mes            │ Fecha de Plazo               │
├─────────────────────────────────────────────────┤
│ Septiembre 2026│ ✅ 08/10/2026                │
│                │ ℹ️ Ampliado por feriados... │
└─────────────────────────────────────────────────┘
```

---

## 🔍 Casos de Uso Comunes

### **Feriados Nacionales Chile 2026**

| Feriado | Fecha | Impacto |
|---------|-------|---------|
| Año Nuevo | 1 enero | Enero-Febrero |
| Fiestas Patrias | 18-19 sept | Septiembre-Octubre |
| Día de las Glorias del Ejército | 19 sept | Septiembre-Octubre |
| Encuentro de Dos Mundos | 12 oct | Octubre-Noviembre |
| Día de Todos los Santos | 1 nov | Noviembre |
| Inmaculada Concepción | 8 dic | Diciembre |
| Navidad | 25 dic | Diciembre-Enero |

### **Ejemplo de Flujo Completo**

**Situación**: Configurar plazo para Septiembre 2026

1. **Sistema calcula**: 6° día hábil = **6 de octubre**
2. **Admin detecta**: Feriados 18-19 septiembre (pierden 2 días hábiles)
3. **Admin ajusta**: Extiende a **8 de octubre** (2 días adicionales)
4. **Admin documenta**: "Ampliado por feriados patrios 18 y 19 de septiembre"
5. **Sistema guarda**: Fecha + motivo
6. **Usuarios ven**: Nuevo plazo con justificación

---

## 🎯 Ventajas del Sistema

✅ **Flexibilidad**: Admin puede ajustar plazos según necesidad  
✅ **Transparencia**: Motivo documentado y visible  
✅ **Trazabilidad**: Histórico de extensiones justificadas  
✅ **Simplicidad**: No requiere tabla de feriados compleja  
✅ **Control**: Admin tiene última palabra en cada caso  

---

## ⚠️ Consideraciones Importantes

1. **Campo opcional**: Solo completar cuando se extiende el plazo
2. **Claridad**: Motivo debe ser específico (incluir fechas de feriados)
3. **Consistencia**: Aplicar mismo criterio para todos los items del mes
4. **Comunicación**: Notificar a usuarios sobre extensiones importantes

---

## 🔄 Proceso Recomendado

### **Inicio de Cada Año**

1. Revisar **calendario oficial de feriados** Chile
2. Identificar meses con **feriados en días hábiles**
3. **Pre-configurar plazos extendidos** para todo el año
4. Documentar motivos claramente

### **Revisión Mensual**

1. Verificar si hay **feriados adicionales** (fines de semana largo, etc)
2. Ajustar plazos si es necesario
3. Comunicar cambios a usuarios afectados

---

## 📞 Soporte

Si tienes dudas sobre:
- ❓ Cómo calcular el plazo correcto
- ❓ Cuántos días extender
- ❓ Qué escribir en el motivo

**Consulta con el administrador del sistema.**

---

## 📝 Notas Técnicas

### **Cálculo del 6° Día Hábil**
- Solo cuenta **lunes a viernes**
- **NO considera feriados** (por eso el admin debe ajustar)
- Comienza desde el día 1 del mes

### **Almacenamiento**
- `plazo_interno`: Fecha extendida (DATE)
- `motivo_extension`: Justificación (VARCHAR 255)
- Ambos se guardan juntos en tabla `item_plazos`

---

**Fecha de implementación**: 8 de abril de 2026  
**Versión**: 1.0  
**Estado**: ✅ Operativo
